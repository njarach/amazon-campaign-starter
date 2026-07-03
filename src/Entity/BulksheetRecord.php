<?php

namespace App\Entity;

use App\Repository\BulksheetRecordRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BulksheetRecordRepository::class)]
class BulksheetRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 20)]
    private string $asin;

    #[ORM\Column(length: 255)]
    private string $campaignId;

    #[ORM\Column(type: 'json')]
    private array $keywords = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getAsin(): string
    {
        return $this->asin;
    }

    public function setAsin(string $asin): static
    {
        $this->asin = $asin;
        return $this;
    }

    public function getCampaignId(): string
    {
        return $this->campaignId;
    }

    public function setCampaignId(string $campaignId): static
    {
        $this->campaignId = $campaignId;
        return $this;
    }

    public function getKeywords(): array
    {
        return $this->keywords;
    }

    public function setKeywords(array $keywords): static
    {
        $this->keywords = $keywords;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
