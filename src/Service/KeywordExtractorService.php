<?php

namespace App\Service;

use Psr\Log\LoggerInterface;
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
        private LoggerInterface     $logger,
        private string              $openaiApiKey
    ) {}

    public function extractCoreKeywordFromProductTitle(string $productTitle): array
    {
        $prompt = "Formule un titre pour ce produit, générique et sans nom de marque et qui ne fasse pas plus de 5 mots : $productTitle.
        Tu me fais un retour en json uniquement.";

        return $this->promptGipidyForKeywords($prompt);
    }

    public function askGipidyForTheKeywords(string $productTitle, array $batchScrapedProductPages): array
    {
        $productsJson = json_encode($batchScrapedProductPages, JSON_UNESCAPED_UNICODE);

        $prompt = <<<PROMPT
        Contexte : titre du produit principal = "$productTitle".
        Titres et descriptions des produits concurrents scrapés (JSON) : $productsJson

        Tâche : identifie EXACTEMENT 20 mots-clés pour une campagne Amazon Ads Sponsored Products.

        Règles à suivre dans l'ordre :
        1. Un mot-clé = ce qu'un acheteur taperait réellement dans la barre de recherche Amazon (pas une phrase de description).
        2. Exclus les noms de marque.
        3. Exclus les mots de liaison seuls ("et", "pour", "avec"...).
        4. Classe du plus générique (proche du titre du produit) au plus spécifique (variante technique : dimension, matière, usage).
        5. N'invente pas de mot-clé absent du contexte fourni (titres/descriptions ci-dessus) : reformule ou combine des termes qui y apparaissent réellement.
        6. Le score de pertinence (1 à 100) reflète la fréquence d'apparition du terme (ou d'un synonyme proche) dans le JSON fourni, pas une estimation générale du marché.

        Exemple de format attendu (valeurs fictives, ne pas les réutiliser) :
        {"gourde isotherme": 95, "bouteille inox 500ml": 80, "gourde sport": 62}

        Réponds UNIQUEMENT avec un objet JSON de 20 paires clé/valeur, trié par score décroissant. Aucun texte hors JSON.
        PROMPT;

        $keywords = $this->promptGipidyForKeywords($prompt);

        if (count($keywords) !== 20) {
            $this->logger->warning('KeywordExtractor: nombre de mots-clés inattendu', [
                'expected' => 20,
                'actual' => count($keywords),
            ]);
        }

        return $keywords;
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
                'max_tokens' => 1500,
                'response_format' => ['type' => 'json_object'],
            ]
        ]);

        $data = $response->toArray();
        $content = $data['choices'][0]['message']['content'] ?? '';

        $decoded = json_decode(trim($content), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error('KeywordExtractor: réponse OpenAI non parsable en JSON', [
                'raw_content' => $content,
                'json_error' => json_last_error_msg(),
            ]);

            return [];
        }

        return $decoded ?? [];
    }
}
