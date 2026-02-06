<?php

namespace App\Controller;

use App\DTO\Bulksheet;
use App\Form\BulksheetType;
use App\Service\CampaignBulksheetMakerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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

        if (!$form->isValid()) {
            return $this->redirectToRoute('app_home');
        }

        $data = $form->getData();

        $keywords = array_filter(
            array_map(fn($kw) => $kw['text'] ?? null, $data['keywords']),
            fn($kw) => !empty($kw)
        );

        $bulksheet = new Bulksheet();
        $bulksheet->setKeywords(array_values($keywords));
        $bulksheet->setAsin($data['asin']);
        $bulksheet->setCampaignId($data['campaignId']);
        $bulksheet->setAutobid($data['autobid']);
        $bulksheet->setSku($data['sku']);

        $bulksheet = $this->bulksheetMakerService->generateCampaigns($bulksheet);

        $filepath = $this->getParameter('kernel.project_dir') . '/var/tmp/amazon_campaign.csv';
        $this->bulksheetMakerService->exportToCsv($bulksheet, $filepath);

        return $this->file($filepath, 'adlance_campaign_' . date('Ymd_His') . '.csv');
    }
}
