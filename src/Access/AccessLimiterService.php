<?php

namespace Drupal\content_access_limiter\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Flood\FloodInterface;

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
   * The flood service.
   *
   * @var \Drupal\Core\Flood\FloodInterface
   */
  protected $flood;

  /**
   * Constructs a new AccessLimiterService.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Flood\FloodInterface $flood
   *   The flood service.
   */
  public function __construct(Connection $database, ConfigFactoryInterface $config_factory, FloodInterface $flood) {
    $this->database = $database;
    $this->configFactory = $config_factory;
    $this->flood = $flood;
  }

  /**
   * Checks if a user can access a node based on their daily limit.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account.
   * @param \Drupal\node\NodeInterface $node
   *   The node being accessed.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   */
  public function checkAccess(AccountInterface $account, NodeInterface $node) {
    // Check if user has bypass role
    $bypass_roles = $this->configFactory->get('content_access_limiter.settings')->get('bypass_roles') ?? [];
    foreach ($bypass_roles as $role) {
      if ($account->hasRole($role)) {
        return AccessResult::allowed();
      }
    }

    // Get daily limit
    $daily_limit = $this->configFactory->get('content_access_limiter.settings')->get('daily_limit') ?? 10;

    // Count today's accesses
    $today = strtotime('today');
    $query = $this->database->select('content_access_limiter_access_log', 'log')
      ->condition('uid', $account->id())
      ->condition('access_time', $today, '>=')
      ->countQuery();
    $count = $query->execute()->fetchField();

    // If under limit, allow access and log it
    if ($count < $daily_limit) {
      $this->logAccess($account->id(), $node->id());
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Logs a content access.
   *
   * @param int $uid
   *   The user ID.
   * @param int $nid
   *   The node ID.
   */
  private function logAccess($uid, $nid) {
    $this->database->insert('content_access_limiter_access_log')
      ->fields([
        'uid' => $uid,
        'nid' => $nid,
        'access_time' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();
  }

} 