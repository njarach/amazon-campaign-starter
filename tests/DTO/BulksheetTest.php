<?php

namespace App\Tests\DTO;

use App\DTO\Bulksheet;
use PHPUnit\Framework\TestCase;

class BulksheetTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $bulksheet = new Bulksheet();

        $bulksheet->setAsin('B08N5WRWNW');
        $bulksheet->setCampaignId('My Campaign');
        $bulksheet->setAutobid(0.75);
        $bulksheet->setSku('SKU-456');
        $bulksheet->setKeywords(['keyword one', 'keyword two']);
        $bulksheet->setRows([]);

        $this->assertEquals('B08N5WRWNW', $bulksheet->getAsin());
        $this->assertEquals('My Campaign', $bulksheet->getCampaignId());
        $this->assertEquals(0.75, $bulksheet->getAutobid());
        $this->assertEquals('SKU-456', $bulksheet->getSku());
        $this->assertEquals(['keyword one', 'keyword two'], $bulksheet->getKeywords());
        $this->assertEmpty($bulksheet->getRows());
    }

    public function testAddRow(): void
    {
        $bulksheet = new Bulksheet();
        $bulksheet->setRows([]);

        $row = ['Entity' => 'Campaign', 'Campaign Id' => 'Test'];
        $bulksheet->addRow($row);

        $this->assertCount(1, $bulksheet->getRows());
        $this->assertEquals($row, $bulksheet->getRows()[0]);
    }

    public function testAddMultipleRows(): void
    {
        $bulksheet = new Bulksheet();
        $bulksheet->setRows([]);

        $bulksheet->addRow(['Entity' => 'Campaign']);
        $bulksheet->addRow(['Entity' => 'Ad group']);
        $bulksheet->addRow(['Entity' => 'Keyword']);

        $this->assertCount(3, $bulksheet->getRows());
    }
}
