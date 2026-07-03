<?php

namespace App\Tests\Controller;

use App\Entity\BulksheetRecord;
use App\Entity\User;
use App\Repository\BulksheetRecordRepository;
use App\Service\CampaignBulksheetMakerService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class BulksheetHistoryControllerTest extends WebTestCase
{
    private function makeUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setPassword('hashed');
        return $user;
    }

    private function makeRecord(User $user, string $asin = 'B08N5WRWNW'): BulksheetRecord
    {
        $record = new BulksheetRecord();
        $record->setUser($user);
        $record->setAsin($asin);
        $record->setCampaignId('Casque Bluetooth');
        $record->setKeywords(['wireless headphones', 'bluetooth headset']);
        return $record;
    }

    public function testHistoryPageShowsUserRecords(): void
    {
        $client = static::createClient();
        $user = $this->makeUser();

        $record = $this->makeRecord($user);

        $repositoryMock = $this->createMock(BulksheetRecordRepository::class);
        $repositoryMock->method('findByUserOrderedByDate')->willReturn([$record]);

        static::getContainer()->set(BulksheetRecordRepository::class, $repositoryMock);

        $client->loginUser($user);
        $client->request('GET', '/history');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('B08N5WRWNW', $client->getResponse()->getContent());
        $this->assertStringContainsString('Casque Bluetooth', $client->getResponse()->getContent());
    }

    public function testHistoryPageShowsEmptyState(): void
    {
        $client = static::createClient();
        $user = $this->makeUser();

        $repositoryMock = $this->createMock(BulksheetRecordRepository::class);
        $repositoryMock->method('findByUserOrderedByDate')->willReturn([]);

        static::getContainer()->set(BulksheetRecordRepository::class, $repositoryMock);

        $client->loginUser($user);
        $client->request('GET', '/history');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Aucune bulksheet', $client->getResponse()->getContent());
    }

    public function testHistoryRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/history');

        $this->assertResponseRedirects('/login');
    }

    public function testDownloadRegeneratesCsv(): void
    {
        $client = static::createClient();
        $user = $this->makeUser();

        $record = $this->makeRecord($user, 'B01TEST1234');

        $repositoryMock = $this->createMock(BulksheetRecordRepository::class);
        $repositoryMock->method('find')->willReturn($record);

        $bulksheetServiceMock = $this->createMock(CampaignBulksheetMakerService::class);
        $bulksheetServiceMock->method('generateCampaigns')->willReturnArgument(0);
        $bulksheetServiceMock->method('exportToCsv')->willReturnCallback(function ($bulksheet, $filepath) {
            file_put_contents($filepath, "Product,Entity\nSponsored Products,Campaign\n");
        });

        static::getContainer()->set(BulksheetRecordRepository::class, $repositoryMock);
        static::getContainer()->set(CampaignBulksheetMakerService::class, $bulksheetServiceMock);

        $client->loginUser($user);
        // Symfony ParamConverter needs the record in the DB; we bypass it by mocking the repository
        // and triggering the route with a fake ID — the mock always returns our record
        $client->request('GET', '/history/1/download');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('text/csv', $client->getResponse()->headers->get('Content-Type') ?? '');
    }
}
