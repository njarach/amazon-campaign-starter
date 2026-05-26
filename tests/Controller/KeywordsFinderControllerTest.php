<?php

namespace App\Tests\Controller;

use App\Service\AmazonScraperService;
use App\Service\KeywordExtractorService;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class KeywordsFinderControllerTest extends WebTestCase
{
    public function testHomePageLoads(): void
    {
        $client = static::createClient();

        $client->request('GET', '/finder');

        $this->assertResponseIsSuccessful();
    }

    public function testFindKeywordsWithoutAsin(): void
    {
        $client = static::createClient();

        $client->request('POST', '/find/keywords');

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Veuillez entrer un code ASIN', $client->getResponse()->getContent());
    }

    public function testFindKeywordsWithValidAsin(): void
    {
        $client = static::createClient();

        $scraperMock = $this->createMock(AmazonScraperService::class);
        $scraperMock->method('findCoreKeyWordByAsin')
            ->willReturn(['title' => 'wireless headphones']);
        $scraperMock->method('mapProductPagesFoundFromCoreKeywords')
            ->willReturn(['https://www.amazon.fr/dp/B01TEST1234']);
        $scraperMock->method('batchScrapeProductPages')
            ->willReturn([['title' => 'Casque Bluetooth', 'bullets' => ['Autonomie 30h']]]);

        $extractorMock = $this->createMock(KeywordExtractorService::class);
        $extractorMock->method('askGipidyForTheKeywords')
            ->willReturn(['wireless headphones' => 9, 'bluetooth headset' => 8]);

        static::getContainer()->set(AmazonScraperService::class, $scraperMock);
        static::getContainer()->set(KeywordExtractorService::class, $extractorMock);

        $client->request('POST', '/find/keywords', ['asin' => 'B08N5WRWNW']);

        $this->assertResponseIsSuccessful();
        $this->assertStringNotContainsString('Erreur:', $client->getResponse()->getContent());
    }

    public function testFindKeywordsShowsErrorWhenServiceFails(): void
    {
        $client = static::createClient();

        $scraperMock = $this->createMock(AmazonScraperService::class);
        $scraperMock->method('findCoreKeyWordByAsin')
            ->willThrowException(new \Exception('Amazon unavailable'));

        static::getContainer()->set(AmazonScraperService::class, $scraperMock);

        $client->request('POST', '/find/keywords', ['asin' => 'B08N5WRWNW']);

        $this->assertResponseIsSuccessful();
        $this->assertStringContainsString('Erreur:', $client->getResponse()->getContent());
    }
}
