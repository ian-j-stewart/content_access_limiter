<?php

namespace Drupal\content_access_limiter\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Render\Markup;

/**
 * Controller for the access limit reached page.
 */
class LimitReachedController extends ControllerBase {

  /**
   * Displays the access limit reached page.
   *
   * @return array
   *   A render array for the page.
   */
  public function content() {
    $daily_limit = $this->config('content_access_limiter.settings')->get('daily_limit') ?? 10;
    
    $build = [];
    $build['message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="messages messages--error"><p>' . $this->t('You have reached your daily access limit of @limit pages. Please try again tomorrow.', [
        '@limit' => $daily_limit,
      ]) . '</p></div>',
    ];

    return $build;
  }

} 