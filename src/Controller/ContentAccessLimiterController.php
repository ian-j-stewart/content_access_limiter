<?php

namespace Drupal\content_access_limiter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for content access limiter functionality.
 */
class ContentAccessLimiterController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new ContentAccessLimiterController.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
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
    // Check if user has a bypass role.
    $bypass_roles = $this->config('content_access_limiter.settings')->get('bypass_roles');
    foreach ($account->getRoles() as $role) {
      if (isset($bypass_roles[$role]) && $bypass_roles[$role]) {
        return AccessResult::allowed();
      }
    }

    // Get daily limit from config.
    $daily_limit = $this->config('content_access_limiter.settings')->get('daily_limit') ?? 10;

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

  /**
   * Returns the current user's daily access count.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with access count.
   */
  public function getAccessCount() {
    $account = $this->currentUser();
    $today = strtotime('today');
    
    $query = $this->database->select('content_access_limiter_access_log', 'log')
      ->condition('uid', $account->id())
      ->condition('access_time', $today, '>=')
      ->countQuery();
    $count = $query->execute()->fetchField();

    return new JsonResponse(['count' => $count]);
  }
}
