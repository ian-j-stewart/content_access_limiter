<?php

namespace Drupal\content_access_limiter\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\User;

/**
 * Determines access to the reset functionality.
 */
class ResetAccessCheck implements AccessInterface {

  /**
   * Checks access to the reset functionality.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param int $uid
   *   The user ID to reset.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(AccountInterface $account, $uid) {
    // Check if the user exists.
    $user = User::load($uid);
    if (!$user) {
      return AccessResult::forbidden('User does not exist.');
    }

    // Check if the current user has permission to reset counts.
    if (!$account->hasPermission('reset content access counts')) {
      return AccessResult::forbidden('User does not have permission to reset counts.');
    }

    // Allow access if all checks pass.
    return AccessResult::allowed();
  }

} 