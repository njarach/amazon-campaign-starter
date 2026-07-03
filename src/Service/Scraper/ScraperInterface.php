<?php

namespace App\Service\Scraper;

interface ScraperInterface
{
    public function scrapeProductPage(string $url): array;
    public function searchProducts(string $keyword): array;
    public function batchScrapeProducts(array $searchUrls): array;
}
