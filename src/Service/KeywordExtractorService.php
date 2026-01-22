<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class KeywordExtractorService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openaiApiKey
    ) {}

    public function extractCoreKeywordFromProductTitle(string $productTitle): array
    {
        $prompt = "Tu es un expert Amazon Ads. Tu dois me trouver le mot clé sans nom de marque pour ce produit : $productTitle.
        Tu me fais un retour en json uniquement.
        CRITÈRES :
        - Mot-clé que les acheteurs tapent réellement sur Amazon, dans la barre de recherche.
        - Terme de recherche à fort potentiel de conversion";

        return $this->promptGipidyForKeywords($prompt);
    }

    public function findThoseDamnedKeywordsInAStrangeAndCoolWay(array $batchScrapedProductPages)
    {
        $productsJson = json_encode($batchScrapedProductPages, JSON_UNESCAPED_UNICODE);
        $prompt = "Tu es un expert Amazon Ads. Tu dois me trouver les 20 mots clé sans nom de marque pour ces produits dont je te donne les titres et
        descriptions : $productsJson.
        Tu me fais un retour en json uniquement.
        CRITÈRES :
        - Mots-clé que les acheteurs tapent réellement sur Amazon, dans la barre de recherche.
        - Termes de recherche à fort potentiel de conversion";

        return $this->promptGipidyForKeywords($prompt);
    }

    /**
     * @param string $prompt
     * @return array|mixed
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    private function promptGipidyForKeywords(string $prompt): mixed
    {
        $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->openaiApiKey,
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un expert Amazon Ads spécialisé en recherche de mots-clés pour campagnes sponsorisées.'
                    ],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'temperature' => 0.2,
                'max_tokens' => 800,
            ]
        ]);

        $data = $response->toArray();
        $content = $data['choices'][0]['message']['content'];

        $content = preg_replace('/```json\n?|\n?```/', '', $content);

        return json_decode(trim($content), true) ?? [];
    }
}
