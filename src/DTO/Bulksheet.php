<?php

namespace App\DTO;

class Bulksheet
{
    private array $keywords;
    private array $frequency;
    private float $autobid;
    private string $campaignId;
    private string $asin;
    private string $sku;
    private array $rows;

    public function getRows(): array
    {
        return $this->rows;
    }

    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }

    public function addRow(array $row): void
    {
        $this->rows[] = $row;
    }

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

    public function getSku(): string
    {
        return $this->sku;
    }

    public function setSku(string $sku): void
    {
        $this->sku = $sku;
    }


}
