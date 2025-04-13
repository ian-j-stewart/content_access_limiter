<?php

namespace Drupal\content_access_limiter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;
use Drupal\user\Entity\User;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;

/**
 * Controller for generating content access reports.
 */
class ReportController extends ControllerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a new ReportController.
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
   * Generates a report of content access statistics.
   *
   * @return array
   *   A render array for the report page.
   */
  public function generateReport() {
    $build = [];

    $build['description'] = [
      '#markup' => Markup::create('<p>This report shows content access statistics and usage patterns.</p>'),
    ];

    // Get all users.
    $users = User::loadMultiple();
    $rows = [];

    foreach ($users as $user) {
      // Get user's roles.
      $roles = $user->getRoles();
      $role_names = [];
      foreach ($roles as $role) {
        $role_names[] = $this->t($role);
      }

      // Get today's access count.
      $today = strtotime('today');
      $query = $this->database->select('content_access_limiter_access_log', 'log')
        ->condition('uid', $user->id())
        ->condition('access_time', $today, '>=')
        ->countQuery();
      $access_count = $query->execute()->fetchField();

      // Get last access time.
      $query = $this->database->select('content_access_limiter_access_log', 'log')
        ->fields('log', ['access_time'])
        ->condition('uid', $user->id())
        ->orderBy('access_time', 'DESC')
        ->range(0, 1);
      $last_access = $query->execute()->fetchField();
      $last_access_time = $last_access ? $this->t('@time ago', [
        '@time' => \Drupal::service('date.formatter')->formatTimeDiffSince($last_access),
      ]) : $this->t('Never');

      // Create reset button with CSRF token.
      $reset_url = Url::fromRoute('content_access_limiter.reset_count', ['uid' => $user->id()]);
      $reset_link = [
        '#type' => 'link',
        '#title' => $this->t('Reset'),
        '#url' => $reset_url,
        '#attributes' => [
          'class' => ['button', 'button--small'],
        ],
      ];

      $rows[] = [
        $user->getDisplayName(),
        implode(', ', $role_names),
        $access_count,
        $last_access_time,
        ['data' => $reset_link],
      ];
    }

    $build['report'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('User'),
        $this->t('Roles'),
        $this->t('Access Count'),
        $this->t('Last Access'),
        $this->t('Actions'),
      ],
      '#rows' => $rows,
      '#empty' => $this->t('No users found.'),
    ];

    return $build;
  }

  /**
   * Resets the access count for a user.
   *
   * @param int $uid
   *   The user ID to reset.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The current request.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirects back to the report page.
   */
  public function resetCount($uid, Request $request) {
    // Verify user exists.
    $user = User::load($uid);
    if (!$user) {
      $this->messenger()->addError($this->t('User not found.'));
      \Drupal::logger('content_access_limiter')->warning('Attempt to reset non-existent user @uid by user @current_uid', [
        '@uid' => $uid,
        '@current_uid' => $this->currentUser()->id(),
      ]);
      return $this->redirect('content_access_limiter.report');
    }

    // Delete today's access logs.
    $today = strtotime('today');
    $this->database->delete('content_access_limiter_access_log')
      ->condition('uid', $uid)
      ->condition('access_time', $today, '>=')
      ->execute();

    // Log the reset action.
    $this->database->insert('content_access_limiter_reset_log')
      ->fields([
        'uid' => $uid,
        'reset_by' => $this->currentUser()->id(),
        'reset_time' => \Drupal::time()->getRequestTime(),
      ])
      ->execute();

    \Drupal::logger('content_access_limiter')->info('Access count reset for user @target_name by user @current_name', [
      '@target_name' => $user->getDisplayName(),
      '@current_name' => $this->currentUser()->getDisplayName(),
    ]);

    $this->messenger()->addStatus($this->t('Access count has been reset for user @name.', [
      '@name' => $user->getDisplayName(),
    ]));

    return $this->redirect('content_access_limiter.report');
  }

}