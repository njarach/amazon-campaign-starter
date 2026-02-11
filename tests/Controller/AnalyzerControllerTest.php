<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AnalyzerControllerTest extends WebTestCase
{
    public function testFindKeywordsWithValidAsin(): void
    {
        $client = static::createClient();

        $client->request('POST', '/find/keywords', [
            'asin' => 'B08N5WRWNW'
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $this->assertNotEmpty($content);

        $this->assertStringNotContainsString('Erreur:', $content);

        echo "✅ Test réussi : réponse reçue sans erreur\n";
    }

    public function testFindKeywordsWithoutAsin(): void
    {
        $client = static::createClient();

        $client->request('POST', '/find/keywords');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('Veuillez entrer un code ASIN', $content);

        echo "✅ Test réussi : erreur ASIN manquant détectée\n";
    }
}
