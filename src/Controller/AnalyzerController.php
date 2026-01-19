<?php

namespace App\Controller;

use App\Service\AmazonScraperService;
use App\Service\KeywordExtractorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AnalyzerController extends AbstractController
{
    private AmazonScraperService $amazonScraper;
    private KeywordExtractorService $keywordExtractor;
    public function __construct(AmazonScraperService $amazonScraper, KeywordExtractorService $keywordExtractor)
    {
        $this->amazonScraper = $amazonScraper;
        $this->keywordExtractor = $keywordExtractor;
    }

    #[Route('/analyzer', name: 'app_home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('analyzer/index.html.twig');
    }

    #[Route('/analyze', name: 'app_analyze', methods: ['POST'])]
    public function analyze(Request $request): Response
    {
        $asin = $request->request->get('asin');

        if (!$asin) {
            return $this->render('analyzer/_analyzer_results.html.twig', [
                'error' => 'Veuillez entrer un code ASIN'
            ]);
        }

        try {
            $productUrls = $this->amazonScraper->searchByAsin($asin);

            $productsData = $this->amazonScraper->getProductsDetails($productUrls);

            $keywords = $this->keywordExtractor->extractKeywords($productsData);

            return $this->render('analyzer/_analyzer_results.html.twig', [
                'keywords' => $keywords,
                'productsCount' => count($productsData)
            ]);

        } catch (\Exception $e) {
            return $this->render('analyzer/_analyzer_results.html.twig', [
                'error' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
}
