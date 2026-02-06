<?php

namespace App\DTO;

use Symfony\Component\HttpFoundation\Request;

class BulksheetFactory
{
    public function createFromRequest(Request $request): Bulksheet
    {
        $keywords = $request->query->all('keywords');
        $frequency = $request->query->all('frequency');
        $autoBid = $request->query->get('auto_bid');
        $campaignId = $request->query->get('campaign_id');
        $asin = $request->query->get('asin');
        $sku = $request->query->get('sku');

        $bulksheetData = new Bulksheet();
        $bulksheetData->setKeywords($keywords);
        $bulksheetData->setFrequency($frequency);
        $bulksheetData->setAutoBid($autoBid);
        $bulksheetData->setCampaignId($campaignId);
        $bulksheetData->setAsin($asin);
        $bulksheetData->setSku($sku);
        return $bulksheetData;
    }
}
