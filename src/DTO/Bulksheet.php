<?php

namespace App\DTO;

class Bulksheet
{
    private array $rows;

    public function getRows(): array
    {
        return $this->rows;
    }

    public function setRows(array $rows): void
    {
        $this->rows = $rows;
    }

}
