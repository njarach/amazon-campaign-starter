<?php

namespace App\Controller;

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
    public function index(Request $request): Response
    {
        $bulksheetData = $this->bulksheetMakerService->createBulksheet($request);
        $bulksheet = $this->bulksheetMakerService->generateCampaigns($bulksheetData);

        $filepath = $this->getParameter('kernel.project_dir') . '/var/tmp/amazon_campaign.csv';
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $this->bulksheetMakerService->exportToCsv($bulksheet, $filepath);

        return $this->file($filepath, 'amazon_campaign_' . date('Ymd') . '.csv');
    }
}
