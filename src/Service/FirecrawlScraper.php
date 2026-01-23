<?php

namespace App\Service;

use App\Service\ScraperInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class FirecrawlScraper implements ScraperInterface
{
    private const API_URL = 'https://api.firecrawl.dev/v2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey
    ) {}

    public function scrapeProductPage(string $url): array
    {
        $response = $this->httpClient->request('POST', self::API_URL . '/scrape', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'url' => $url,
                'location' => ['country' => 'FR'],
                'includeTags' => ['#productTitle', '#feature-bullets'],
                'onlyMainContent' => true
            ]
        ]);

        $data = $response->toArray();
        return [
            'title' => $data['data']['metadata']['title'] ?? '',
            'bullets' => []
        ];
    }

    public function searchProducts(string $keyword): array
    {
        $url = "https://www.amazon.fr/s?k=$keyword";
        $response = $this->httpClient->request('POST', self::API_URL . '/scrape', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'url' => $url,
                'location' => ['country' => 'FR'],
                'onlyMainContent' => true,
                'formats' => ['html']
            ]
        ]);

        $data = $response->toArray();
        $html = $data['data']['html'] ?? '';

        preg_match_all('/role="listitem"[^>]*data-asin="([A-Z0-9]{10})"/', $html, $matches);
        $asins = array_slice(array_unique($matches[1]), 0, 5);

        return array_map(fn($asin) => "https://www.amazon.fr/dp/{$asin}", $asins);
    }

    public function batchScrapeProducts(array $searchUrls): array
    {
        $results = [];

        foreach ($searchUrls as $url) {
            try {
                $response = $this->httpClient->request('POST', self::API_URL . '/scrape', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'url' => $url,
                        'location' => ['country' => 'FR'],
                        'includeTags' => ['#productTitle', '#feature-bullets'],
                        'onlyMainContent' => true,
                    ]
                ]);

                if ($response->getStatusCode() === 500) {
                    echo "⚠️  500 error on $url - skipping\n";
                    continue;
                }

                $data = $response->toArray();
                $results[] = [$data['data']['markdown'] ?? ''];

                sleep(2);
            } catch (\Exception $e) {
                echo "⚠️  Erreur sur $url - skipping\n";
                continue;
            }
        }

        return $results;
    }
}
