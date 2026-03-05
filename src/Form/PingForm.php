<?php

declare(strict_types=1);

namespace Drupal\ping_bridge\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ping_bridge\PingBridgeService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Provides a Ping bridge form.
 */
final class PingForm extends FormBase {

	protected $nid;
	protected $node;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'ping_bridge_ping';
  }

 	/**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        // Instantiates this form class.
        return new static(
            $container->get('ping_bridge.post'),
            $container->get('current_route_match')
        );
    }

 	/**
     * {@inheritdoc}
     */
 	public function __construct(
 		protected PingBridgeService $pingBridge,
 		RouteMatchInterface $routeMatch, 		
 		) {
 		$this->node = $routeMatch->getParameter('node');
	}


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

 	
	if (!empty($this->node)) {
		// Save nid the route back.
		$this->nid = $this->node->id();
		// Get the link.
		$link = $this->node->toUrl()->setAbsolute()->toString();
	}


    $form['source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Full URL to the Post'),
      '#required' => TRUE,
      '#default_value' => $link,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Send Ping'),
      ],
    ];

    return $form;
  }

	/**
	 * {@inheritdoc}
	 */
	public function validateForm(array &$form, FormStateInterface $form_state): void {
		// @todo Validate the form here.
		// Example:
		// @code
		//   if (mb_strlen($form_state->getValue('message')) < 10) {
		//     $form_state->setErrorByName(
		//       'message',
		//       $this->t('Message should be at least 10 characters.'),
		//     );
		//   }
		// @endcode
	}
	
	/**
	* {@inheritdoc}
	*/
	public function submitForm(array &$form, FormStateInterface $form_state): void {
		$sourceUrl = $form_state->getValue('source');  
		$err = $this->pingBridge->ping($sourceUrl);
		
		if (false === $err) {
			$this->messenger()->addStatus($this->t("Bridgy gas been pinged."));
			$form_state->setRedirect('entity.node.canonical', ['node' => $this->nid]);
		}
		else {
			$this->messenger()->addStatus($this->t($err));
			$form_state->setRebuild();
		}

	}
}
