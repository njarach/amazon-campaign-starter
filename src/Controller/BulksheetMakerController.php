<?php

namespace App\Controller;

use App\DTO\Bulksheet;
use App\Form\BulksheetType;
use App\Service\CampaignBulksheetMakerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

final class BulksheetMakerController extends AbstractController
{
    private CampaignBulksheetMakerService $bulksheetMakerService;
    public function __construct(CampaignBulksheetMakerService $bulksheetMakerService)
    {
        $this->bulksheetMakerService = $bulksheetMakerService;
    }

    #[Route('/bulksheet/create', name: 'app_create_bulksheet')]
    public function create(Request $request): Response
    {
        $form = $this->createForm(BulksheetType::class);
        $form->handleRequest($request);

//        if (!$form->isSubmitted() || !$form->isValid()) {
//            return $this->render('analyzer/_analyzer_results.html.twig', [
//                'form' => $form->createView(),
//                'error' => 'Données du formulaire invalides'
//            ]);
//        }

        $data = $form->getData();

        $bulksheet = new Bulksheet();
        $bulksheet->setAsin($data['asin']);
        $bulksheet->setCampaignId($data['campaignId']);
        $bulksheet->setAutobid((float)$data['autobid']);
        $bulksheet->setSku($data['sku']??'');
        $keywords = array_values(
            array_filter(
                array_map(
                    fn ($k) => $k['text'] ?? null,
                    $data['keywords']
                )
            )
        );
        $bulksheet->setKeywords(array_values($keywords));

        $bulksheet = $this->bulksheetMakerService->generateCampaigns($bulksheet);

        $filepath = $this->getParameter('kernel.project_dir') . '/var/tmp/amazon_campaign.csv';
        $this->bulksheetMakerService->exportToCsv($bulksheet, $filepath);

        return $this->file($filepath, 'adlance_campaign_' . date('Ymd_His') . '.csv');
    }
}
