<?php

namespace App\Tests\Entity;

use App\Entity\BulksheetRecord;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class BulksheetRecordTest extends TestCase
{
    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');
        return $user;
    }

    public function testCreatedAtIsSetOnConstruction(): void
    {
        $before = new \DateTimeImmutable();
        $record = new BulksheetRecord();
        $after = new \DateTimeImmutable();

        $this->assertGreaterThanOrEqual($before, $record->getCreatedAt());
        $this->assertLessThanOrEqual($after, $record->getCreatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $user = $this->makeUser();

        $record = new BulksheetRecord();
        $record->setUser($user);
        $record->setAsin('B08N5WRWNW');
        $record->setCampaignId('Casque Bluetooth');
        $record->setKeywords(['wireless headphones', 'bluetooth headset']);

        $this->assertSame($user, $record->getUser());
        $this->assertSame('B08N5WRWNW', $record->getAsin());
        $this->assertSame('Casque Bluetooth', $record->getCampaignId());
        $this->assertSame(['wireless headphones', 'bluetooth headset'], $record->getKeywords());
    }

    public function testKeywordsDefaultToEmptyArray(): void
    {
        $record = new BulksheetRecord();
        $this->assertSame([], $record->getKeywords());
    }

    public function testIdIsNullBeforePersist(): void
    {
        $record = new BulksheetRecord();
        $this->assertNull($record->getId());
    }
}
