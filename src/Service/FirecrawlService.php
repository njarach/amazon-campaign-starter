<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class FirecrawlService
{
    private const API_URL = 'https://api.firecrawl.dev/v2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $apiKey
    ) {}

    public function scrapeAsinProductPage(string $url): array
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

        return $response->toArray();
    }

    public function mapProductPagesFromResearch(string $url): array
    {
        $response = $this->httpClient->request('POST', self::API_URL . '/scrape', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'url' => $url,
                'location' => ['country' => 'FR'],
//                'includeTags' => ['#productTitle', '#feature-bullets'],
                'onlyMainContent' => true,
                'formats'=>[
                    'html'
                ]
            ]
        ]);

        return $response->toArray();
    }

    public function batchScrapeProductPages(array $searchUrls): array
    {
        $results = [];
        foreach ($searchUrls as $url) {
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

            $results[] = $response->toArray();
            sleep(2);
        }

        return array_map(function ($item) {
            $metadata = $item['data']['markdown'] ?? [];
            return [
                $metadata
            ];
        }, $results);
    }
}
