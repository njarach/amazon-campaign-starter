<?php

namespace App\Service;

class AmazonScraperService
{
    public function __construct(
        private readonly FirecrawlService $firecrawlService
    ) {}


    public function searchByAsin(string $asin): array
    {
        $searchUrl = "https://www.amazon.fr/s?k={$asin}";

        $data = $this->firecrawlService->scrapeUrl($searchUrl);

        return $this->extractProductUrls($data, 10);
    }

    public function getProductsDetails(array $productUrls): array
    {
        $results = $this->firecrawlService->batchScrape($productUrls);

        return array_map(function($result) {
            $markdown = $result['data']['markdown'] ?? '';
            return $this->extractRelevantContent($markdown);
        }, $results);
    }

    private function extractProductUrls(array $scrapedData, int $limit): array
    {
        $markdown = $scrapedData['data']['markdown'] ?? '';

        preg_match_all('/\/dp\/([A-Z0-9]{10})/', $markdown, $matches);

        $asins = array_unique(array_slice($matches[1], 0, $limit));

        $urls = [];
        foreach ($asins as $asin) {
            $urls[] = "https://www.amazon.fr/dp/{$asin}";
        }

        return $urls;
    }

    private function extractRelevantContent(string $markdown): string
    {
        $content = [];

        if (preg_match('/^#\s+(.+?)$/m', $markdown, $match)) {
            $content[] = "Titre: " . trim($match[1]);
        }

        if (preg_match('/(?:Description|À propos|About).{0,50}?\n+(.*?)(?:\n#{1,2}|$)/si', $markdown, $match)) {
            $desc = trim($match[1]);
            $content[] = "Description: " . substr($desc, 0, 500);
        }

        if (preg_match_all('/^\s*[-*]\s+(.+)$/m', $markdown, $matches)) {
            $features = array_slice($matches[1], 0, 10);
            $content[] = "Caractéristiques: " . implode('. ', $features);
        }

        return implode("\n\n", $content);
    }
}
