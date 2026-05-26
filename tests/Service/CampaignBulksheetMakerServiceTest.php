<?php

namespace App\Tests\Service;

use App\DTO\Bulksheet;
use App\Service\CampaignBulksheetMakerService;
use PHPUnit\Framework\TestCase;

class CampaignBulksheetMakerServiceTest extends TestCase
{
    private CampaignBulksheetMakerService $service;

    protected function setUp(): void
    {
        $this->service = new CampaignBulksheetMakerService();
    }

    private function createBulksheet(array $keywords = ['keyword1', 'keyword2']): Bulksheet
    {
        $bulksheet = new Bulksheet();
        $bulksheet->setCampaignId('Test Campaign');
        $bulksheet->setAsin('B08N5WRWNW');
        $bulksheet->setSku('SKU-123');
        $bulksheet->setAutobid(0.35);
        $bulksheet->setKeywords($keywords);
        $bulksheet->setRows([]);
        return $bulksheet;
    }

    public function testGenerateCampaignsRowCount(): void
    {
        $bulksheet = $this->createBulksheet(['keyword1', 'keyword2']);
        $result = $this->service->generateCampaigns($bulksheet);

        // Auto:   1 campaign + 1 ad group + 1 product ad + 4 targeting = 7
        // Manual: 1 campaign + 1 ad group + 1 product ad + (2 × 3 match types) = 9
        $this->assertCount(16, $result->getRows());
    }

    public function testGenerateCampaignsWithNoKeywords(): void
    {
        $bulksheet = $this->createBulksheet([]);
        $result = $this->service->generateCampaigns($bulksheet);

        // Auto: 7 rows, Manual: 3 rows (no keywords)
        $this->assertCount(10, $result->getRows());
    }

    public function testAutoCampaignIsCreated(): void
    {
        $bulksheet = $this->createBulksheet();
        $result = $this->service->generateCampaigns($bulksheet);

        $campaignRows = array_values(array_filter($result->getRows(), fn($r) => $r['Entity'] === 'Campaign'));

        $this->assertCount(2, $campaignRows);
        $this->assertStringContainsString('Auto', $campaignRows[0]['Campaign Id']);
        $this->assertEquals('Auto', $campaignRows[0]['Targeting Type']);
    }

    public function testManualCampaignIsCreated(): void
    {
        $bulksheet = $this->createBulksheet();
        $result = $this->service->generateCampaigns($bulksheet);

        $campaignRows = array_values(array_filter($result->getRows(), fn($r) => $r['Entity'] === 'Campaign'));

        $this->assertStringContainsString('Manual', $campaignRows[1]['Campaign Id']);
        $this->assertEquals('Manual', $campaignRows[1]['Targeting Type']);
    }

    public function testKeywordsCreatedForAllMatchTypes(): void
    {
        $bulksheet = $this->createBulksheet(['keyword1', 'keyword2']);
        $result = $this->service->generateCampaigns($bulksheet);

        $keywordRows = array_values(array_filter($result->getRows(), fn($r) => $r['Entity'] === 'Keyword'));

        $this->assertCount(6, $keywordRows); // 2 keywords × 3 match types
        $matchTypes = array_column($keywordRows, 'Match Type');
        $this->assertContains('exact', $matchTypes);
        $this->assertContains('phrase', $matchTypes);
        $this->assertContains('broad', $matchTypes);
    }

    public function testProductTargetingRowsCreated(): void
    {
        $bulksheet = $this->createBulksheet();
        $result = $this->service->generateCampaigns($bulksheet);

        $targetingRows = array_values(array_filter($result->getRows(), fn($r) => $r['Entity'] === 'Product targeting'));

        $this->assertCount(4, $targetingRows);
        $expressions = array_column($targetingRows, 'Product Targeting Expression');
        $this->assertContains('close-match', $expressions);
        $this->assertContains('loose-match', $expressions);
        $this->assertContains('substitutes', $expressions);
        $this->assertContains('complements', $expressions);
    }

    public function testAllRowsHaveSponsoredProductsProduct(): void
    {
        $bulksheet = $this->createBulksheet(['keyword1']);
        $result = $this->service->generateCampaigns($bulksheet);

        foreach ($result->getRows() as $row) {
            $this->assertEquals('Sponsored Products', $row['Product']);
        }
    }

    public function testExportToCsvCreatesFile(): void
    {
        $bulksheet = $this->createBulksheet(['keyword1']);
        $this->service->generateCampaigns($bulksheet);

        $filepath = sys_get_temp_dir() . '/test_bulksheet_' . uniqid() . '.csv';
        $this->service->exportToCsv($bulksheet, $filepath);

        $this->assertFileExists($filepath);
        $this->assertStringContainsString('Campaign Id', file_get_contents($filepath));

        unlink($filepath);
    }

    public function testExportToCsvEmptyRows(): void
    {
        $bulksheet = new Bulksheet();
        $bulksheet->setRows([]);

        $filepath = sys_get_temp_dir() . '/test_bulksheet_empty_' . uniqid() . '.csv';
        $this->service->exportToCsv($bulksheet, $filepath);

        $this->assertFileExists($filepath);
        $this->assertEmpty(file_get_contents($filepath));

        unlink($filepath);
    }
}
