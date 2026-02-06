<?php

namespace App\Controller;

use App\Form\BulksheetType;
use App\Service\AmazonScraperService;
use App\Service\KeywordExtractorService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class KeywordsFinderController extends AbstractController
{
    public function __construct(private AmazonScraperService $amazonScraper, private readonly KeywordExtractorService $keywordExtractorService) {}

    #[Route('/finder', name: 'app_keywords_finder', methods: ['GET'])]
    public function home(): Response
    {
        return $this->render('analyzer/index.html.twig');
    }

    #[Route('/find/keywords', name: 'app_find_keywords', methods: ['POST'])]
    public function findKeywords(Request $request): Response
    {
        $asin = $request->request->get('asin');

        if (!$asin) {
            return $this->render('analyzer/_analyzer_results.html.twig', [
                'error' => 'Veuillez entrer un code ASIN.'
            ]);
        }

        try {
            $coreKeywordsResult = $this->amazonScraper->findCoreKeyWordByAsin($asin)['title'];
            $productPagesLinks = $this->amazonScraper->mapProductPagesFoundFromCoreKeywords($coreKeywordsResult);
            $batchScrapedProductPages = $this->amazonScraper->batchScrapeProductPages($productPagesLinks);
            $amazingKeywords = $this->keywordExtractorService->askGipidyForTheKeywords($coreKeywordsResult, $batchScrapedProductPages);

            $formData = [
              'asin' => $asin,
              'campaignId' => $coreKeywordsResult,
              'autobid' => 0.35,
              'keywords' => array_map(
                  fn($keyword,$score) => ['text' => $keyword, 'score' => $score], array_keys($amazingKeywords),$amazingKeywords)
            ];

            $form = $this->createForm(BulksheetType::class, $formData);

            return $this->render('analyzer/_analyzer_results.html.twig', [
                'form' => $form->createView(),
                'productsCount' => count($batchScrapedProductPages),
            ]);

        } catch (\Exception $e) {
            return $this->render('analyzer/_analyzer_results.html.twig', [
                'error' => 'Erreur: ' . $e->getMessage()
            ]);
        }
    }
}
