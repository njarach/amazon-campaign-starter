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

    public function scrapeUrl(string $url): array
    {
        $response = $this->httpClient->request('POST', self::API_URL . '/scrape', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ],
            'json' => [
                'url' => $url,
                'formats' => ['markdown', 'html'],
            ]
        ] );

        return $response->toArray();
    }

    public function batchScrape(array $urls): array
    {
        $results = [];
        foreach ($urls as $url) {
            try {
                $results[] = $this->scrapeUrl($url);
            } catch (\Exception $e) {
                continue;
            }
        }
        return $results;
    }
}
