<?php

namespace Drupal\content_access_limiter\EventSubscriber;

use Drupal\content_access_limiter\Access\AccessLimiterService;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber to check content access limits.
 */
class NodeViewSubscriber implements EventSubscriberInterface {

  /**
   * The access limiter service.
   *
   * @var \Drupal\content_access_limiter\Access\AccessLimiterService
   */
  protected $accessLimiter;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new NodeViewSubscriber.
   *
   * @param \Drupal\content_access_limiter\Access\AccessLimiterService $access_limiter
   *   The access limiter service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(AccessLimiterService $access_limiter, RouteMatchInterface $route_match) {
    $this->accessLimiter = $access_limiter;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => ['onRequest', 30],
    ];
  }

  /**
   * Checks access when a node is viewed.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The request event.
   */
  public function onRequest(RequestEvent $event) {
    $route = $this->routeMatch->getRouteName();
    if ($route === 'entity.node.canonical') {
      $node = $this->routeMatch->getParameter('node');
      if ($node) {
        $account = \Drupal::currentUser();
        $result = $this->accessLimiter->checkAccess($node, $account);
        if ($result->isForbidden()) {
          $url = Url::fromRoute('content_access_limiter.limit_page')->toString();
          $response = new RedirectResponse($url);
          $event->setResponse($response);
        }
      }
    }
  }

}