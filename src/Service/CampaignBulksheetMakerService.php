<?php

namespace App\Service;

use App\DTO\Bulksheet;
use App\DTO\BulksheetData;
use App\DTO\BulksheetDataFactory;
use Symfony\Component\HttpFoundation\Request;

class CampaignBulksheetMakerService
{
    private BulksheetDataFactory $bulksheetDataFactory;
    public function __construct(BulksheetDataFactory $bulksheetDataFactory)
    {
        $this->bulksheetDataFactory = $bulksheetDataFactory;
    }

    public function getBulksheetDataFromRequest(Request $request): BulksheetData
    {
        return $this->bulksheetDataFactory->createBulksheetData($request);
    }

    public function createBulksheet(BulksheetData $bulksheetData): Bulksheet
    {
        $bulksheet = new Bulksheet();
        $rows = [];

        // ===== CAMPAGNE AUTO =====
        $autoCampaignId = $bulksheetData->getCampaignId() . ' Auto';
        $autoAdGroupId = $autoCampaignId . ' AG';

        $rows[] = $this->createCampaignRow($autoCampaignId, 'Auto');
        $rows[] = $this->createAdGroupRow($autoCampaignId, $autoAdGroupId, '0.75');
        $rows[] = $this->createProductAdRow($autoCampaignId, $autoAdGroupId, $bulksheetData->getAsin());

        // 4 product targeting pour auto
        foreach (['close-match', 'loose-match', 'substitutes', 'complements'] as $expression) {
            $rows[] = $this->createProductTargetingRow($autoCampaignId, $autoAdGroupId, $expression, '0.75');
        }

        // ===== CAMPAGNE MANUAL =====
        $manualCampaignId = $bulksheetData->getCampaignId() . ' Manual';
        $manualAdGroupId = $manualCampaignId . ' AG';

        $rows[] = $this->createCampaignRow($manualCampaignId, 'Manual');
        $rows[] = $this->createAdGroupRow($manualCampaignId, $manualAdGroupId, '0.50');
        $rows[] = $this->createProductAdRow($manualCampaignId, $manualAdGroupId, $bulksheetData->getAsin());

        // 20 keywords × 3 match types (exact, phrase, broad)
        foreach (['exact', 'phrase', 'broad'] as $matchType) {
            foreach ($bulksheetData->getKeywords() as $keyword) {
                $rows[] = $this->createKeywordRow($manualCampaignId, $manualAdGroupId, $keyword, $matchType);
            }
        }

        $bulksheet->setRows($rows);
        return $bulksheet;
    }

    private function createCampaignRow(string $campaignId, string $targetingType): array
    {
        return array_merge($this->getEmptyRow(), [
            'Entity' => 'Campaign',
            'Operation' => 'Create',
            'Campaign Id' => $campaignId,
            'Campaign Name' => $campaignId,
            'Start Date' => date('Ymd'),
            'Targeting Type' => $targetingType,
            'State' => 'enabled',
            'Daily Budget' => '10',
            'Bidding Strategy' => 'Fixed bid',
        ]);
    }

    private function createAdGroupRow(string $campaignId, string $adGroupId, string $defaultBid): array
    {
        return array_merge($this->getEmptyRow(), [
            'Entity' => 'Ad group',
            'Operation' => 'Create',
            'Campaign Id' => $campaignId,
            'Ad Group Id' => $adGroupId,
            'Ad Group Name' => $adGroupId,
            'State' => 'enabled',
            'Ad Group Default Bid' => $defaultBid,
        ]);
    }

    private function createProductAdRow(string $campaignId, string $adGroupId, string $asin): array
    {
        return array_merge($this->getEmptyRow(), [
            'Entity' => 'Product ad',
            'Operation' => 'Create',
            'Campaign Id' => $campaignId,
            'Ad Group Id' => $adGroupId,
            'State' => 'enabled',
            'asin' => $asin,
        ]);
    }

    private function createProductTargetingRow(string $campaignId, string $adGroupId, string $expression, string $bid): array
    {
        return array_merge($this->getEmptyRow(), [
            'Entity' => 'Product targeting',
            'Operation' => 'Create',
            'Campaign Id' => $campaignId,
            'Ad Group Id' => $adGroupId,
            'State' => 'enabled',
            'Bid' => $bid,
            'Product Targeting Expression' => $expression,
        ]);
    }

    private function createKeywordRow(string $campaignId, string $adGroupId, string $keyword, string $matchType): array
    {
        return array_merge($this->getEmptyRow(), [
            'Entity' => 'Keyword',
            'Operation' => 'Create',
            'Campaign Id' => $campaignId,
            'Ad Group Id' => $adGroupId,
            'State' => 'enabled',
            'Bid' => '0.50',
            'Keyword Text' => $keyword,
            'Match Type' => $matchType,
        ]);
    }

    private function getEmptyRow(): array
    {
        return [
            'Product' => 'Sponsored Products',
            'Entity' => '',
            'Operation' => '',
            'Campaign Id' => '',
            'Ad Group Id' => '',
            'Portfolio Id' => '',
            'Ad Id' => '',
            'Keyword Id' => '',
            'Product Targeting Id' => '',
            'Campaign Name' => '',
            'Ad Group Name' => '',
            'Start Date' => '',
            'End Date' => '',
            'Targeting Type' => '',
            'State' => '',
            'Daily Budget' => '',
            'sku' => '',
            'asin' => '',
            'Ad Group Default Bid' => '',
            'Bid' => '',
            'Keyword Text' => '',
            'Match Type' => '',
            'Bidding Strategy' => '',
            'Placement' => '',
            'Percentage' => '',
            'Product Targeting Expression' => '',
            'Audience ID' => '',
            'Shopper Cohort Percentage' => '',
            'Shopper Cohort Type' => '',
        ];
    }

    public function exportToCsv(Bulksheet $bulksheet, string $filepath): void
    {
        $handle = fopen($filepath, 'w');

        $rows = $bulksheet->getRows();

        if (empty($rows)) {
            fclose($handle);
            return;
        }

        fputcsv($handle, array_keys($rows[0]));

        foreach ($rows as $row) {
            fputcsv($handle, array_values($row));
        }

        fclose($handle);
    }
}
