<?php

declare(strict_types=1);

namespace Drupal\ping_bridge\Routing;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\Routing\RouteCollection;

/**
 * Subscriber for Custom Elements UI routes.
 */
class PingBridgeRouteSubscriber extends RouteSubscriberBase {

	protected $config;

  /**
   * Constructs a RouteSubscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param ConfigFactoryInterfac $factory
   *   The config factory.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    ConfigFactoryInterface $configFactory,
  ) {
	  	$this->config = $configFactory->get('ping_bridge.settings');
    }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // Get the selected types from config.
    $types = $this->config->get('types');

    // Modify our route parameters->node->bundle
    // to include our selected node types.
    $route_name = 'ping_bridge.tab';
    $route = $collection->get($route_name);
    $options = $route->getOptions();
    $options['parameters']['node']['bundle'] = $types;
    $route->setOptions($options);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -120];
    return $events;
  }

}
