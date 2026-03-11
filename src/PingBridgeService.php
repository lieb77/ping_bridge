<?php

declare(strict_types=1);

namespace Drupal\ping_bridge;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

final class PingBridgeService {

    public function __construct(
        private readonly ClientInterface $httpClient,
        private readonly LoggerChannelInterface $logger,
        private readonly EntityTypeManagerInterface $entityTypeManager,
    ) {}

    /**
     * @param string $source
     * The absolute URL of the Drupal node.
     * @param string $target
     * 'classic', 'fed', or 'both'.
     * @param int $nid
     * The Node ID to link the syndication to.
     *
     * @return string|bool
     * Error message or FALSE on success.
     */
    public function ping($source, $target, $nid) {
        $results = [];
        
        $bridEndpoint  = 'https://brid.gy/publish/webmention';
        $bridTarget    = 'https://brid.gy/publish/bluesky';
        
        $fedEndpoint   = 'https://fed.brid.gy/webmention';
        $fedTarget     = 'https://fed.brid.gy/';
        
        if ($target === 'classic' || $target === 'both') {
            $results['classic'] = $this->executePing($bridEndpoint, $bridTarget, $source);
        }

        if ($target === 'fed' || $target === 'both') {
            $results['fed'] = $this->executePing($fedEndpoint, $fedTarget, $source);
        }

        foreach ($results as $type => $data) {
            if (is_string($data)) {
                return "Bridgy $type Error: $data";
            }
            
            // If we got a URL back, create the syndication entity.
            if (!empty($data['url'])) {
                $this->createSyndicationEntity($nid, $data['url']);
            }
        }

        return FALSE;
    }

    /**
     * Internal helper to handle the HTTP logic and response parsing.
     */
    private function executePing(string $endpoint, string $targetUrl, string $sourceUrl) {
        try {
            $response = $this->httpClient->post($endpoint, [
                'form_params' => [
                    'source' => $sourceUrl,
                    'target' => $targetUrl,
                ],
                'timeout' => 10,
            ]);

            $body = json_decode($response->getBody()->getContents(), TRUE);
            
            // Bridgy usually returns the social post URL in the 'url' key
            return [
                'status' => $response->getStatusCode(),
                'url' => $body['url'] ?? $body['location'] ?? NULL,
            ];
        }
        catch (\Exception $e) {
            $this->logger->error('Bridgy Ping Failed: @msg', ['@msg' => $e->getMessage()]);
            return $e->getMessage();
        }
    }

    /**
     * Creates the IndieWeb Syndication Entity.
     */
    private function createSyndicationEntity(int $nid, string $syndicationUrl): void {
        try {
            $storage = $this->entityTypeManager->getStorage('indieweb_syndication');
            
            $syndication = $storage->create([
                'entity_id' => $nid,
                'entity_type' => 'node',
                'url' => $syndicationUrl,
            ]);
            
            $syndication->save();
            $this->logger->info('Created syndication entity for node @nid: @url', [
                '@nid' => $nid,
                '@url' => $syndicationUrl,
            ]);
        }
        catch (\Exception $e) {
            $this->logger->error('Failed to create syndication entity: @msg', ['@msg' => $e->getMessage()]);
        }
    }
}