<?php

namespace App\DTO;

class BulksheetData
{
    private array $keywords;
    private array $frequency;
    private float $autobid;
    private string $campaignId;
    private string $asin;

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function setKeywords(array $keywords): void
    {
        $this->keywords = $keywords;
    }

    public function getFrequency(): array
    {
        return $this->frequency;
    }

    public function setFrequency(array $frequency): void
    {
        $this->frequency = $frequency;
    }

    public function getAutobid(): float
    {
        return $this->autobid;
    }

    public function setAutobid(float $autobid): void
    {
        $this->autobid = $autobid;
    }

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    public function setCampaignId(string $campaignId): void
    {
        $this->campaignId = $campaignId;
    }

    public function getAsin(): string
    {
        return $this->asin;
    }

    public function setAsin(string $asin): void
    {
        $this->asin = $asin;
    }


}
