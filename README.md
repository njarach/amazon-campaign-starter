# Amazon Ads Keyword Finder & Bulksheet Generator
Projet POC (à portée éducative personnelle) pour automatiser la recherche de mots-clés et la génération de bulksheets pour les campagnes Amazon Sponsored Products.
🎯 Objectif
Automatiser le processus de création de campagnes Amazon Ads en :
- Analysant un produit à partir de son ASIN
- Trouvant les mots-clés pertinents via scraping et IA
- Générant un fichier CSV bulksheet prêt à importer dans Amazon Ads

### 🚧 Work In Progress
📋 Prérequis

- PHP 8.2+
- Composer
- Clé API OpenAI (GPT-4o-mini)
- Clé API Firecrawl (optionnel, pour le scraping alternatif)

### 🚀 Installation
Cloner le projet, installer les dépendances avec _composer install_
### Configurer l'environnement
cp .env .env.local
Éditer .env.local et ajouter :
- OPENAI_API_KEY=votre_clé_openai
- FIRECRAWL_API_KEY=votre_clé_firecrawl  # (optionnel)

Lancer le serveur :
_symfony serve_

## 🔧 Architecture

### Services de scraping

Deux implémentations du `ScraperInterface` :
- **SymfonyCrawlerScraper** : Scraping natif avec Symfony (par défaut)
- **FirecrawlScraper** : Via API Firecrawl (optionnel, plus robuste)

### Flux de traitement

1. **Input** : ASIN du produit
2. **Extraction** : Titre et description du produit Amazon
3. **Recherche** : Produits concurrents via mots-clés génériques
4. **Analyse IA** : Extraction de 20 mots-clés pertinents avec scores
5. **Génération** : Création du bulksheet avec campagnes Auto + Manual
6. **Export** : Fichier CSV prêt pour Amazon

### Structure des campagnes générées

**Campagne Auto** :
- 1 campagne avec targeting automatique
- 1 groupe d'annonces
- 4 product targeting (close-match, loose-match, substitutes, complements)

**Campagne Manual** :
- 1 campagne avec targeting manuel
- 1 groupe d'annonces  
- 20 mots-clés × 3 match types (exact, phrase, broad) = 60 keywords

### ⚠️ Limitations actuelles

- Scraping dépendant de la structure HTML Amazon, 
- C'est un projet léger donc on ne se confronte pas à du rate limiting, mais c'est une limite théorique,
- Pas de gestion d'erreurs, de tests, interface basique (wip)

### 📝 License
Projet éducatif - Usage personnel
