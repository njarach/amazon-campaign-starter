<?php

namespace App\Service;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;

class SymfonyCrawlerScraper implements ScraperInterface
{
    private HttpBrowser $browser;

    public function __construct()
    {
        $client = HttpClient::create([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'fr-FR,fr;q=0.9,en;q=0.8',
            ]
        ]);

        $this->browser = new HttpBrowser($client);
    }

    public function scrapeProductPage(string $url): array
    {
        try {
            $crawler = $this->browser->request('GET', $url);

            $title = $crawler->filter('#productTitle')->count()
                ? trim($crawler->filter('#productTitle')->text())
                : '';

            $bullets = $crawler->filter('#feature-bullets li span.a-list-item')->each(
                fn(Crawler $node) => trim($node->text())
            );

            return [
                'title' => $title,
                'bullets' => array_filter($bullets)
            ];
        } catch (\Exception $e) {
            throw new \RuntimeException("Erreur scraping produit: " . $e->getMessage());
        }
    }

    public function searchProducts(string $keyword): array
    {
        try {
            $url = "https://www.amazon.fr/s?k=" . urlencode($keyword);
            $crawler = $this->browser->request('GET', $url);

            $asins = $crawler->filter('[data-asin]')->each(function (Crawler $node) {
                $asin = $node->attr('data-asin');
                return $asin && preg_match('/^[A-Z0-9]{10}$/', $asin) ? $asin : null;
            });

            $uniqueAsins = array_slice(array_unique(array_filter($asins)), 0, 5);

            return array_map(
                fn($asin) => "https://www.amazon.fr/dp/{$asin}",
                $uniqueAsins
            );
        } catch (\Exception $e) {
            throw new \RuntimeException("Erreur recherche produits: " . $e->getMessage());
        }
    }

    public function batchScrapeProducts(array $searchUrls): array
    {
        $results = [];

        foreach ($searchUrls as $url) {
            try {
                $productData = $this->scrapeProductPage($url);

                // Texte brut pour OpenAI : titre + bullets
                $text = $productData['title'];
                if (!empty($productData['bullets'])) {
                    $text .= ' ' . implode(' ', $productData['bullets']);
                }

                $results[] = [$text];

                // Anti-blocage : pause entre requêtes
                sleep(2);
            } catch (\Exception $e) {
                echo "⚠️  Erreur sur $url - skipping: " . $e->getMessage() . "\n";
                continue;
            }
        }

        return $results;
    }
}
