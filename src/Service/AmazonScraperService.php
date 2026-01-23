<?php

namespace App\Service;

class AmazonScraperService
{
    public function __construct(
        private readonly ScraperInterface $scraper,
        private readonly KeywordExtractorService $keywordExtractor
    ) {}

    public function findCoreKeyWordByAsin(string $asin): array
    {
        $url = "https://www.amazon.fr/dp/{$asin}";
        $result = $this->scraper->scrapeProductPage($url);

        return $this->keywordExtractor->extractCoreKeywordFromProductTitle($result['title']);
    }

    public function mapProductPagesFoundFromCoreKeywords(string $coreKeywords): array
    {
        return $this->scraper->searchProducts($coreKeywords);
    }

    public function batchScrapeProductPages(array $searchUrls): array
    {
        return $this->scraper->batchScrapeProducts($searchUrls);
    }
}
