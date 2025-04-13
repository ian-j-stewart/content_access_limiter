<?php

namespace Drupal\content_access_limiter\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;

/**
 * Service for handling content access limiting.
 */
class AccessLimiterService {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AccessLimiterService.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory) {
    $this->database = $database;
    $this->configFactory = $config_factory;
  }

  /**
   * Checks if a user can access a node based on daily limits.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The node to check access for.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to check.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(NodeInterface $node, AccountInterface $account) {
    // Check if this content type is enabled for access limiting.
    $enabled_types = $this->configFactory->get('content_access_limiter.settings')->get('enabled_types') ?? [];
    if (!isset($enabled_types[$node->bundle()]) || !$enabled_types[$node->bundle()]) {
      return AccessResult::allowed();
    }

    // Check if user has a bypass role.
    $bypass_roles = $this->configFactory->get('content_access_limiter.settings')->get('bypass_roles');
    foreach ($account->getRoles() as $role) {
      if (isset($bypass_roles[$role]) && $bypass_roles[$role]) {
        return AccessResult::allowed();
      }
    }

    // Get daily limit from config.
    $daily_limit = $this->configFactory->get('content_access_limiter.settings')->get('daily_limit') ?? 10;

    // Get today's access count for this user.
    $today = strtotime('today');
    $query = $this->database->select('content_access_limiter_access_log', 'log')
      ->condition('uid', $account->id())
      ->condition('access_time', $today, '>=')
      ->countQuery();
    $count = $query->execute()->fetchField();

    // If user has reached their daily limit, deny access.
    if ($count >= $daily_limit) {
      return AccessResult::forbidden();
    }

    // Log this access.
    $this->database->insert('content_access_limiter_access_log')
      ->fields([
        'uid' => $account->id(),
        'nid' => $node->id(),
        'access_time' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    return AccessResult::allowed();
  }

} 