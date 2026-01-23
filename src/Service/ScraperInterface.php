<?php

namespace App\Service;

interface ScraperInterface
{
    public function scrapeProductPage(string $url): array;
    public function searchProducts(string $keyword): array;
    public function batchScrapeProducts(array $searchUrls): array;

}
