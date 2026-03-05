<?php

declare(strict_types=1);

namespace Drupal\ping_bridge;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelInterface;

/**
* @todo Add a description for the middleware.
*/
final class PingBridgeService {

	/**
 	 * Constructs a PingBridgeService object.
	 */
	public function __construct(
		private readonly ClientInterface $httpClient,
		private readonly LoggerChannelInterface $logger,
	) {}
	
	
	/**
	 * Sends a ping to the fed.brid.gy notifying them of new content
	 *
	 * @param $node the new node I want to tell them about
	 * @returns void
	 */
	public function ping($source)  {
		// $source_url = $node->toUrl('canonical', ['absolute' => TRUE])->toString();
		$endpoint = 'https://fed.brid.gy/webmention'; 
		$target = 'https://fed.brid.gy/';
		
		try {			
			$this->httpClient->post($endpoint, [
				'form_params' => [
				'source' => $source,
				'target' => $target,
			],
			'timeout' => 5,
			]);
		
			$this->logger('ping_bridgeb')->info('Successfully pinged Bridgy Fed for: @url', ['@url' => $source]);	return FALSE;
		} 
		catch (\Exception $e) {
			$this->logger('ping_bridgeb')->error('Failed to ping Bridgy Fed for @url. Error: @msg', [
				'@url' => $sourceUrl,
				'@msg' => $e->getMessage(),
			]);
			return $e->getMessage();
		}	
	}
}
