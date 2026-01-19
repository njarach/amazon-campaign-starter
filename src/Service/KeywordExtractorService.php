<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class KeywordExtractorService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $openaiApiKey
    ) {}

    public function extractKeywords(array $productsContent, int $limit = 50): array
    {
        $allContent = implode("\n\n---\n\n", $productsContent);

        $prompt = "Tu es un expert Amazon Ads. Analyse ces 10 fiches produits et extrais les {$limit} meilleurs mots-clés pour des campagnes publicitaires Amazon.

CRITÈRES :
- Mots-clés que les acheteurs tapent réellement sur Amazon
- Termes de recherche à fort potentiel de conversion
- Mixe de mots-clés génériques, spécifiques et de marque
- Élimine les stop words et termes non pertinents
- Privilégie les termes commerciaux (ex: \"acheter\", \"meilleur\", \"pas cher\")

CONTENU DES PRODUITS :
{$allContent}

Réponds UNIQUEMENT avec un JSON : {\"mot-clé\": score_pertinence}
Score de 1 à 100 (100 = très pertinent pour Amazon Ads).
Trie par score décroissant.";

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
                'max_tokens' => 1500,
            ]
        ]);

        $data = $response->toArray();
        $content = $data['choices'][0]['message']['content'];

        $content = preg_replace('/```json\n?|\n?```/', '', $content);

        return json_decode(trim($content), true) ?? [];
    }
}
