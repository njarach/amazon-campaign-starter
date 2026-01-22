<?php

namespace App\Service;

class AmazonScraperService
{
    public function __construct(
        private readonly FirecrawlService $firecrawlService,
        private readonly KeywordExtractorService $keywordExtractor
    ) {}

    public function findCoreKeyWordByAsin(string $asin): array
    {
        $searchUrl = "https://www.amazon.fr/dp/{$asin}";
        $result = $this->firecrawlService->scrapeAsinProductPage($searchUrl);
        $productTitle = $result["data"]["metadata"]["title"];
        return $this->keywordExtractor->extractCoreKeywordFromProductTitle($productTitle);
    }

    public function mapProductPagesFoundFromCoreKeywords(string $coreKeywords): array
    {
        $url = "https://www.amazon.fr/s?k=$coreKeywords";
        $productPagesFound = $this->firecrawlService->mapProductPagesFromResearch($url);
        $html = $productPagesFound['data']['html'] ?? '';

        preg_match_all('/role="listitem"[^>]*data-asin="([A-Z0-9]{10})"/', $html, $matches);

        $asins = array_slice(array_unique($matches[1]), 0, 5);

        return array_map(fn($asin) => "https://www.amazon.fr/dp/{$asin}", $asins);
    }

    public function batchScrapeProductPages(array $searchUrls)
    {
        return $this->firecrawlService->batchScrapeProductPages($searchUrls);
    }
}
