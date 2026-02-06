<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

readonly class KeywordExtractorService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string              $openaiApiKey
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

    public function askGipidyForTheKeywords(array $batchScrapedProductPages)
    {
        $productsJson = json_encode($batchScrapedProductPages, JSON_UNESCAPED_UNICODE);
        $prompt = "Tu es un expert Amazon Ads. Tu dois me trouver les 40 mots-clé sans nom de marque pour ces produits dont je te donne les titres et
        descriptions : $productsJson.
        Réponds UNIQUEMENT avec un JSON : {\"mot-clé\": score_pertinence}
        Score de 1 à 100 (100 = très pertinent pour Amazon Ads).
        Trie par score décroissant. Ce score est basé sur la fréquence des mots utilisés sur la page du produit, retrouvés dans l'expression 'mot-clé' que tu proposes.
        CRITÈRES :
        - Mots-clé que les acheteurs tapent réellement sur Amazon, dans la barre de recherche,
        - Commence avec des mots clés très génériques et communs utilisés notamment dans le titre du produit,
        - Poursuis par des mots-clé très génériques et communs utilisés pour le type de produit,
        - Termes de recherche à fort potentiel de conversion,
        - Évite les mots de liaison tels que 'et','pou','avec',
        - Utilise quelques données techniques selon les produits si présentes dans le titre ou la description : dimensions, performances.";

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
