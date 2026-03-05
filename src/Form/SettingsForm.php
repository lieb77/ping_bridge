<?php

declare(strict_types=1);

namespace Drupal\ping_bridge\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure Bluesky Post settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * Array to hold available content types.
   *
   * @var array of selected content types
   */
  protected $types;

  /**
   * Instance of EntityTypeManger.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Instance of Route Builder.
   *
   * @var Drupal\Core\Routing\RouteBuilder
   */
  protected $routeBuilder;

  /**
   * Constructor.
   *
   * @param EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param RouteBuilder $routeBuilder
   *   The route builder.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    RouteBuilder $routeBuilder,
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity_type.manager'),
      $container->get('router.builder'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ping_bridge_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return ['ping_bridge.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Get current settings.
    $config = $this->config('ping_bridge.settings')->get('types');
    $default = empty($config) ? [] : array_keys($config);

    // Get node types.    
    $types = $this->entityTypeManager->getStorage('node_type')->loadMultiple();
	$options =  array_keys($types);
	$this->types = $options;
		
    $form['message'] = [
      '#type' => 'item',
      '#markup' => $this->t('Select the content types that you want to display the "Ping Bridgy" tab on'),
    ];

    $form['types'] = [
      '#type' => 'select',
      '#title' => $this->t('Select content types'),
      '#options' => $options,
      '#multiple' => TRUE,
      '#default_value' => $default,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (count($form_state->getValue('types')) < 1) {
      $form_state->setErrorByName(
            'types',
            $this->t('You must select at least one content type.'),
        );
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $types = $form_state->getValue('types');
    foreach ($types as $type) {
      $settings[$type] = $this->types[$type];
    }

    $this->config('ping_bridge.settings')
      ->set('types', $settings)
      ->save();
    parent::submitForm($form, $form_state);
    $this->routeBuilder->rebuild();
  }

}
