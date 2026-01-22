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
    public function __construct(private AmazonScraperService $amazonScraper, private readonly KeywordExtractorService $keywordExtractorService) {}

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
            $coreKeywordsResult = $this->amazonScraper->findCoreKeyWordByAsin($asin)['keyword'];
            $productPagesLinks = $this->amazonScraper->mapProductPagesFoundFromCoreKeywords($coreKeywordsResult);
            $batchScrapedProductPages = $this->amazonScraper->batchScrapeProductPages($productPagesLinks);
            $amazingKeywords = $this->keywordExtractorService->findThoseDamnedKeywordsInAStrangeAndCoolWay($batchScrapedProductPages);
            return $this->render('analyzer/_analyzer_results.html.twig', [
//                'keywords' => $result['keywords'],
//                'productsCount' => $result['productsCount']
            'results' => $amazingKeywords
            ]);

        } catch (\Exception $e) {
            return $this->render('analyzer/_analyzer_results.html.twig', [
                'error' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
}
