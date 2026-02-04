<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

class BulksheetDataFactory
{
    public function createBulksheetData(Request $request): BulksheetData
    {
        $keywords = $request->query->all('keywords');
        $frequency = $request->query->all('frequency');
        $autoBid = $request->query->get('auto_bid');
        $campaignId = $request->query->get('campaign_id');
        $asin = $request->query->get('asin');

        $bulksheetData = new BulksheetData();
        $bulksheetData->setKeywords($keywords);
        $bulksheetData->setFrequency($frequency);
        $bulksheetData->setAutoBid($autoBid);
        $bulksheetData->setCampaignId($campaignId);
        $bulksheetData->setAsin($asin);
        return $bulksheetData;
    }
}
