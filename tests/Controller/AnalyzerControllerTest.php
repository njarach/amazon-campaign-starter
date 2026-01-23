<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AnalyzerControllerTest extends WebTestCase
{
    public function testAnalyzeWithValidAsin(): void
    {
        $client = static::createClient();

        $client->request('POST', '/analyze', [
            'asin' => 'B08N5WRWNW'
        ]);

        $this->assertResponseIsSuccessful();

        $content = $client->getResponse()->getContent();
        $this->assertNotEmpty($content);

        $this->assertStringNotContainsString('Erreur:', $content);

        echo "✅ Test réussi : réponse reçue sans erreur\n";
    }

    public function testAnalyzeWithoutAsin(): void
    {
        $client = static::createClient();

        $client->request('POST', '/analyze');

        $this->assertResponseIsSuccessful();
        $content = $client->getResponse()->getContent();

        $this->assertStringContainsString('Veuillez entrer un code ASIN', $content);

        echo "✅ Test réussi : erreur ASIN manquant détectée\n";
    }
}
