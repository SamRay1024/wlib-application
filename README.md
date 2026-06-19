# wlib/application - Documentation Technique

[![License](https://img.shields.io/badge/License-CeCILL-blue.svg)](https://www.cecill.info/)

## Sommaire

- [Introduction](#introduction)
- [Architecture](#architecture)
- [Installation](#installation)
- [Configuration](#configuration)
- [Noyau (Kernel)](#noyau-kernel)
- [Routage](#routage)
- [Contrôleurs](#contr%C3%B4leurs)
- [Authentification](#authentification)
- [Sécurité](#s%C3%A9curit%C3%A9)
- [Cache HTTP](#cache-http)
- [Internationalisation](#internationalisation)
- [Templates](#templates)
- [Mailer](#mailer)
- [Cryptographie](#cryptographie)
- [Modèle Utilisateur](#mod%C3%A8le-utilisateur)
- [Services et Injection de Dépendances](#services-et-injection-de-d%C3%A9pendances)
- [Debugging](#debugging)
- [Structure des Fichiers](#structure-des-fichiers)
- [Bonnes Pratiques](#bonnes-pratiques)
- [Exemples Complets](#exemples-complets)
- [Licence](#licence)

---

## Introduction

**wlib/application** est un framework PHP léger, modulaire et orienté objet, conçu pour simplifier le développement d'applications web modernes. Il s'appuie sur une architecture basée sur l'injection de dépendances (via [wlib/dibox](https://github.com/wlib-php/dibox)) et suit les standards PSR.

### Principes Fondamentaux

| Principe | Description |
|----------|-------------|
| **Simplicité** | Courbe d'apprentissage réduite, syntaxe intuitive |
| **Modularité** | Chaque composant peut être utilisé indépendamment |
| **Performance** | Empreinte mémoire minimale, optimisé pour la production |
| **Sécurité** | Protection intégrée contre les attaques courantes (CSRF, XSS, flooding) |
| **Extensibilité** | Système de hooks et providers personnalisables |

---

## Architecture

### Composants Principaux

```
┌─────────────────────────────────────────────────────────────┐
│                      Kernel (Cœur)                          │
│  ┌──────────────┐  ┌─────────────┐  ┌─────────────┐         │
│  │   DiBox      │  │  Service    │  │   Router    │         │
│  │  (Container) │  │  Providers  │  │             │         │
│  └──────────────┘  └─────────────┘  └─────────────┘         │
└─────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────┐
│                    Contrôleurs                              │
│  ┌─────────────┐  ┌───────────────┐  ┌───────────────────┐  │
│  │ Controller  │  │ RestController│  │ FrontController   │  │
│  │             │  │               │  │                   │  │
│  └─────────────┘  └───────────────┘  └───────────────────┘  │
└─────────────────────────────────────────────────────────────┘
         │
         ▼
┌─────────────────────────────────────────────────────────────┐
│                    Services                                 │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐          │
│  │ Auth/       │  │ Mailer/     │  │ Templates/  │          │
│  │ │ WebGuard  │  │ │ Mail      │  │ │ Engine    │          │
│  │ │ Providers │  │             │  │             │          │
│  │ Crypto/     │  │ Sys/        │  │ L10n/       │          │
│  │ │ HashMgr   │  │ │ Cache     │  │             │          │
│  │ Models/     │  │             │  │             │          │
│  │ │ User      │  │             │  │             │          │
│  └─────────────┘  └─────────────┘  └─────────────┘          │
└─────────────────────────────────────────────────────────────┘
```

### Flux de Requête

```
    HTTP Request
         │
         ▼
┌──────────────────┐
│   Kernel::run()  │
└────────┬─────────┘
         │
         ▼
┌────────────────────────────────┐
│  Router::dispatch()            │
│  - Résolution URL → Controller │
│  - Gestion des arguments       │
└────────┬───────────────────────┘
         │
         ▼
┌────────────────────┐
│  Controller        │
│  - authenticate()  │
│  - checkAccess()   │
│  - checkFlooding() │
│  - cache()         │
│  - start()         │ ← Méthode principale
└────────┬───────────┘
         │
         ▼
┌─────────────────┐
│   Response      │
│  - HTML/JSON    │
│  - Headers      │
│  - Cache        │
└────────┬────────┘
         │
         ▼
    HTTP Response
```

---

## Installation

### Prérequis

- **PHP** : 8.1 ou supérieur
- **Extensions PHP** :
  - `mbstring` (pour l'internationalisation)
  - `pdo` + pilote de base de données (mysql, sqlite, pgsql)
  - `session` (pour l'authentification)
  - `openssl` (pour le hachage sécurisé)

### Installation via Composer

```bash
composer require wlib/application
```

### Structure de projet recommandée

```
mon-projet/
├── app/
│   └── Controllers/
│       ├── IndexController.php
│       └── Admin/
│           └── DashboardController.php
├── config/
│   ├── app.php
│   └── databases.php
├── public/
│   └── index.php          # Point d'entrée
├── storage/
│   ├── cache/             # Fichiers de cache HTTP
│   └── logs/              # Journaux d'erreurs
├── templates/             # Vues
│   └── layout.html.php
├── resources/
│   └── locales/           # Fichiers de traduction (.mo)
│       └── fr_FR.mo
└── vendor/
```

---

## Configuration

### Fichier de configuration principal (`config/app.php`)

```php
<?php
return [
    'app' => [
        // Nom de l'application
        'name' => 'Mon Application',
        
        // Mode production (désactive le debug)
        'production' => false,
        
        // Fuseau horaire
        'timezone' => 'Europe/Paris',
        
        // URI de base (pour les applications dans un sous-dossier)
        'base_uri' => '/',
        'base_url' => 'mon-app.local',
        
        // Namespace des contrôleurs
        'ns_controllers' => 'App\\Controllers',
        
        // Auto-chargement PSR-4
        'psr4_folders' => [
            'App\\' => __DIR__.'/../app'
        ],
        
        // Chemins des dossiers
        'cache_path' => __DIR__.'/../storage/cache',
        'logs_path' => __DIR__.'/../storage/logs',
        'public_path' => __DIR__.'/../public',
        'templates_path' => __DIR__.'/../templates',
        'templates_ext' => '.html.php',
        
        // Internationalisation
        'i18n_path' => __DIR__.'/../resources/locales',
        'i18n_locale' => 'fr_FR',
    ],
    
    'databases' => [
        'default' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'mydb',
            'username' => 'user',
            'password' => 'password',
            'charset' => 'utf8mb4',
            'port' => 3306,
        ],
        // 'sqlite' => [
        //     'driver' => 'sqlite',
        //     'database' => __DIR__.'/../storage/database.sqlite',
        // ],
    ],
    
    'security' => [
        'user_provider' => 'database', // ou 'array'
        'database' => [
            'name' => 'default', // Nom de la connexion DB
        ],
        'guard' => [
            'web' => [
                'can_register' => true,
                'login_url' => '/auth/login',
                'logout_url' => '/auth/logout',
                'register_url' => '/auth/register',
                'verify_url' => '/auth/verify?k=%s',
                'forgot_url' => '/auth/forgot',
                'renew_url' => '/auth/renew?k=%s',
            ],
        ],
        'array' => [
            'users' => [
                ['id' => 1, 'name' => 'Admin', 'email' => 'admin@example.com', 
                 'password' => '$2y$10$...', 'can_login' => true],
            ],
        ],
    ],
    
    'mailer' => [
        'driver' => 'smtp', // sendmail, mail, smtp
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'user@example.com',
        'password' => 'password',
        'encryption' => 'tls', // '', 'ssl', 'tls'
        'from' => 'noreply@example.com',
        'replyto' => 'support@example.com',
        'charset' => 'utf-8',
    ],
];
```

### Configuration ùinimale

```php
<?php
// config/app.php
return [
    'app' => [
        'name' => 'Mon App',
        'production' => false,
        'timezone' => 'Europe/Paris',
        'ns_controllers' => 'App\\Controllers',
        'cache_path' => __DIR__.'/../storage/cache',
        'logs_path' => __DIR__.'/../storage/logs',
    ],
    'databases' => [
        'default' => [
            'driver' => 'sqlite',
            'database' => __DIR__.'/../storage/database.sqlite',
        ],
    ],
];
```

---

## Noyau (Kernel)

Le `Kernel` est le cœur du framework. Il étend `wlib\Di\DiBox` pour fournir un conteneur d'injection de dépendances complet.

### Initialisation

```php
<?php

// public/index.php
require __DIR__.'/../vendor/autoload.php';

use wlib\Application\Sys\Kernel;

$composer = require __DIR__.'/../vendor/autoload.php';

$app = new Kernel(__DIR__, [
    'sys.composer' => $composer,
    'sys.config_dir' => __DIR__.'/../config',
    'sys.env_filename' => '.env'
]);

$app->run();
```

### Méthodes principales

| Méthode | Description | Retour |
|---------|-------------|--------|
| `getVersion()` | Version du framework | `string` |
| `getTable(string $className, string $dbName = 'default')` | Obtient une instance de table DB | `wlib\Db\Table` |
| `run()` | Lance l'application (traitement de la requête) | `void` |
| `bind(string $key, mixed $value)` | Lie une clé à une valeur (hérité de DiBox) | `void` |
| `get(string $key, array $args = [])` | Récupère un service | `mixed` |
| `has(string $key)` | Vérifie si un service existe | `bool` |
| `make(string $class, array $args = [])` | Instancie une classe avec injection | `object` |

### Cycle de vie

1. **Construction** : Initialisation de la configuration, de l'autoloader, du reporting d'erreurs
2. **Boot des Providers** : Appel de `boot()` sur tous les DiBoxProviders enregistrés
3. **Traitement de la Requête** : Routage → Contrôleur → Réponse
4. **Envoi de la Réponse** : Envoi des headers et du body

---

## Routage

### Routage automatique

Le framework utilise un **routage par convention** basé sur la structure des fichiers des contrôleurs.

| URL | Contrôleur Appelé | Fichier |
|-----|-------------------|--------|
| `/` | `App\Controllers\IndexController` | `app/Controllers/IndexController.php` |
| `/articles` | `App\Controllers\ArticlesController` | `app/Controllers/ArticlesController.php` |
| `/articles/show` | `App\Controllers\Articles\ShowController` | `app/Controllers/Articles/ShowController.php` |
| `/admin/users` | `App\Controllers\Admin\UsersController` | `app/Controllers/Admin/UsersController.php` |
| `/admin/users/edit/5` | `App\Controllers\Admin\Users\EditController` | Args: `['5']` |

### Règles de routage

1. **Conversion des segments URL** : Les segments sont convertis en PascalCase
   - `/hello-world` → `HelloWorldController`
   - `/user_profile` → `UserProfileController`
   - `/admin/users` → `Admin\UsersController`

2. **Arguments** : Les segments supplémentaires deviennent des arguments
   - `/articles/show/42` → Controller: `Articles\ShowController`, Args: `['42']`
   - `/users/edit/5/john` → Controller: `Users\EditController`, Args: `['5', 'john']`

3. **Index par défaut** : Si le chemin est vide, `IndexController` est appelé

### Personnalisation du routage

Le router peut être configuré dans le Kernel :

```php
$app->bind('http.router', function($box)
{
    return new \wlib\Application\Sys\Router(
        $box['http.request'],
        'App\\Custom\\Controllers', // Namespace personnalisé
        '/api'                        // Base URI à ignorer
    );
});
```

---

## Contrôleurs

### Classe de base

Tous les contrôleurs **doivent** hériter de `wlib\Application\Controllers\Controller`.

```php
<?php
namespace App\Controllers;

use wlib\Application\Controllers\Controller;

class WelcomeController extends Controller
{
    public function start()
    {
        $this->response->html('<h1>Bienvenue !</h1>');
    }
}
```

### Méthodes du cycle de vie

| Méthode | Description | Appel |
|---------|-------------|-------|
| `__construct(Kernel $app)` | Constructeur (ne pas surcharger) | Automatique |
| `initialize()` | Initialisation personnalisée | Avant `authenticate()` |
| `authenticate()` | Authentification (appelle le provider) | Automatique |
| `checkAccessRights()` | Vérification des droits d'accès | Automatique |
| `checkFlooding()` | Vérification anti-flooding | Automatique |
| `initCache()` | Initialisation du cache | Automatique |
| `run()` | Exécute `start()` et gère le cache | Automatique |
| `start()` | **Méthode principale** (obligatoire) | Appelée par `run()` |

### Propriétés disponibles

| Propriété | Type | Description |
|-----------|------|-------------|
| `$app` | `Kernel` | Instance de l'application |
| `$request` | `Request` | Requête HTTP actuelle |
| `$response` | `Response` | Réponse HTTP à construire |
| `$session` | `Session` | Gestion de session |
| `$auth` | `AuthProviderInterface` | Provider d'authentification |
| `$user` | `UserInterface` | Utilisateur authentifié |
| `$db` | `Db` | Instance de la base de données par défaut |
| `$aArgs` | `array` | Arguments de route |
| `$sUid` | `string` | Identifiant unique du contrôleur |

### Contrôleur REST

Pour créer des APIs RESTful, héritez de `RestController` :

```php
<?php
namespace App\Controllers;

use wlib\Application\Controllers\RestController;

class ApiPostsController extends RestController
{
    // Méthodes HTTP automatiquement appelées
    
    public function get()
    {
        $posts = $this->db->from('posts')->all();
        $this->response->json($posts);
    }
    
    public function post()
    {
        $data = $this->rawData();
        $id = $this->db->into('posts')->insert(json_decode($data, true));
        $this->response->json(['id' => $id], 201);
    }
    
    public function put($id)
    {
        $data = $this->rawData();
        $this->db->update('posts', json_decode($data, true), ['id' => $id]);
        $this->response->setStatus(204);
    }
    
    public function delete($id)
    {
        $this->db->delete('posts', ['id' => $id]);
        $this->response->setStatus(204);
    }
}
```

### Gestion des requêtes HTTP

```php
// Vérifier la méthode HTTP
$this->isGet();      // bool - Requête GET
$this->isPost();     // bool - Requête POST
$this->isPut();      // bool - Requête PUT
$this->isDelete();   // bool - Requête DELETE
$this->isPatch();    // bool - Requête PATCH
$this->isAjax();     // bool - Requête XMLHttpRequest
$this->isJson();     // bool - Requête avec Content-Type: application/json

// Récupérer les paramètres GET
$this->param('id');            // Valeur de $_GET['id']
$this->param('search', '');    // Avec valeur par défaut
$this->hasParam('filter');     // Vérifie l'existence

// Récupérer les données POST
$this->data('email');          // Valeur de $_POST['email']
$this->hasData('password');    // Vérifie l'existence

// Corps brut de la requête
$this->rawData();              // Corps brut (pour JSON, XML, etc.)

// Méthode HTTP
$this->method();               // 'GET', 'POST', etc.
$this->method(true);           // Méthode réelle (sans override)

// Chemin de la route
$this->pathUri();              // Chemin routé (ex: "articles/show/")

// Arguments de route
$this->arg(0);                 // Premier argument
$this->args();                 // Tous les arguments
```

### Gestion des réponses

```php
// Réponse HTML
$this->response->html('<p>Contenu HTML</p>');
$this->response->html($this->render('view', ['data' => $value]));

// Réponse JSON
$this->response->json(['status' => 'success', 'data' => $items]);
$this->response->json($object, 201); // Avec code HTTP

// Réponse texte brut
$this->response->setBody('Texte brut');

// Réponse avec template
$this->response->html($this->app['app.templates']->render('template', $data));

// Ajouter des headers
$this->response->addHeader('X-Custom-Header', 'value');
$this->response->setHeader('Content-Type', 'application/json');

// Code HTTP
$this->response->setStatus(201); // Created
$this->response->setStatus(404); // Not Found
```

### Redirections

```php
// Redirection temporaire (302 Found)
$this->redirect('/nouvelle-page');

// Redirection permanente (301 Moved Permanently)
$this->redirectPermanent('/page-definitive');

// Redirection après POST (303 See Other)
$this->redirectAfterPost('/confirmation');

// Redirection avec code personnalisé
$this->redirect('/url', \wlib\Http\Server\Response::HTTP_MOVED_PERMANENTLY);
```

### Codes d'erreur HTTP

Toutes les méthodes `halt*()` lancent une `HttpException` qui est automatiquement gérée.

```php
// 400 Bad Request
$this->haltBadRequest('Paramètre manquant');

// 401 Unauthorized
$this->haltUnauthorized('Authentification requise');

// 403 Forbidden
$this->haltForbidden('Accès interdit');

// 404 Not Found
$this->haltNotFound('Ressource non trouvée');

// 405 Method Not Allowed
$this->haltMethodNotAllowed('Méthode non autorisée');

// 406 Not Acceptable
$this->haltNotAcceptable('Format non supporté');

// 409 Conflict
$this->haltConflict('Conflit de données');

// 417 Expectation Failed
$this->haltExpectationFailed('Attente non satisfaite');

// 429 Too Many Requests
$this->haltTooManyRequests('Trop de requêtes', ['Retry-After: 60']);

// 500 Internal Server Error
$this->haltInternalServerError('Erreur serveur');

// 501 Not Implemented
$this->haltNotImplemented('Non implémenté');

// 503 Service Unavailable
$this->haltServiceUnavailable('Service indisponible');
```

---

## Authentification

### Architecture

```
┌────────────────────────────────────────────────────────────────┐
│                    Authentification                            │
├────────────────────────────────────────────────────────────────┤
│  ┌────────────────┐  ┌─────────────────┐  ┌──────────────────┐ │
│  │AuthProvider    │◄─│UserProvider     │  │  Guard           │ │
│  │                │  │                 │  │                  │ │
│  │- authenticate()│  │- getById()      │  │- login()         │ │
│  │- getUser()     │  │- getByKey()     │  │- logout()        │ │
│  └────────────────┘  │- getByUsername()│  │- isLoggedIn()    │ │
│                      └─────────────────┘  │- getCurrentUser()│ │
│                                           └──────────────────┘ │
└────────────────────────────────────────────────────────────────┘
```

### Providers d'authentification

| Provider | Classe | Description |
|----------|--------|-------------|
| Public | `PublicAuthProvider` | Accès public sans authentification |
| Basic | `BasicAuthProvider` | Authentification HTTP Basic |
| Key | `KeyAuthProvider` | Authentification par clé API |
| WSSE | `WsseAuthProvider` | Authentification WSSE |
| Web | `WebAuthProvider` | Authentification web (via WebGuard) |

### Providers d'utilisateurs

| Provider | Classe | Description |
|----------|--------|-------------|
| Database | `UserDbProvider` | Utilisateurs stockés en base de données |
| Array | `UserArrayProvider` | Utilisateurs définis dans un tableau |

> ⚠️ L'utilisation du fournisseur `UserArrayProvider` devrait être réservé pour échaffauder rapidement vos applications de type POC ou pour des usages privés uniquement. Si votre application doit être mise en ligne passez par `UserDbProvider` qui fournira la sécurité nécessaire pour la protection des mots de passe.

### Configuration dans un contrôleur

```php
<?php
namespace App\Controllers;

use wlib\Application\Controllers\Controller;

class AdminController extends Controller
{
    // Définir le provider d'authentification
    protected function authentification()
    {
        return 'auth.web'; // Utilise WebGuard
        // return 'auth.basic'; // Authentification Basic
        // return 'auth.key'; // Authentification par clé API
        // return ['auth.web', ['redirect_to' => '/login']]; // Avec paramètres
    }
    
    // Définir les droits d'accès
    protected function allow(): bool
    {
        // Vérifier si l'utilisateur a les droits
        return $this->user && $this->user->getUsername() === 'admin';
    }
    
    public function start()
    {
        $this->response->html('Zone administrateur');
    }
}
```

### WebGuard - Gestion complète de l'authentification web

`WebGuard` est un service dédié pour gérer tout le processus d'authentification web.

#### Initialisation

```php
// Récupérer WebGuard depuis le conteneur
$guard = $this->app->get('guard.web');

// Ou via l'injection dans un contrôleur
$guard = $this->app['guard.web'];
```

#### Méthodes de WebGuard

```php
// Connexion
try {
    $user = $guard->login('user@example.com', 'password123');
    $this->response->json(['status' => 'logged_in', 'user' => $user->getUsername()]);
} catch (\wlib\Application\Auth\AuthenticateException $e) {
    $this->haltUnauthorized($e->getMessage());
}

// Déconnexion
$guard->logout();

// Vérifier si connecté
if ($guard->isLoggedIn()) {
    $user = $guard->getCurrentUser();
    // $user est une instance de UserInterface
}

// Vérifier si l'inscription est autorisée
if ($guard->canRegister()) {
    // Afficher le formulaire d'inscription
}

// Inscription (début du processus)
$guard->register('user@example.com');
// → Envoie un email avec un lien de vérification

// Vérification de l'email (après clic sur le lien)
$guard->verify($token, 'Jean Dupont', 'password123');
// → Active le compte et permet la connexion

// Mot de passe oublié (début du processus)
$guard->startForgotPassword('user@example.com');
// → Envoie un email avec un lien de renouvellement

// Renouvellement du mot de passe
$guard->renewPassword($token, 'newPassword123');
// → Met à jour le mot de passe et envoie un email de confirmation
```

#### URL de WebGuard

Les URLs par défaut peuvent être configurées dans `config/app.php` :

```php
'security' => [
    'guard' => [
        'web' => [
            'login_url' => '/auth/login',
            'logout_url' => '/auth/logout',
            'register_url' => '/auth/register',
            'verify_url' => '/auth/verify?k=%s',
            'forgot_url' => '/auth/forgot',
            'renew_url' => '/auth/renew?k=%s',
        ],
    ],
],
```

#### Récupération des URLs

```php
$loginUrl = $guard->getLoginUrl();
$logoutUrl = $guard->getLogoutUrl();
$registerUrl = $guard->getRegisterUrl();
$verifyUrl = $guard->getVerifyUrl($token);
$forgotUrl = $guard->getForgotUrl();
$renewUrl = $guard->getRenewUrl($token);
```

### Authentification HTTP Basic

```php
// Dans un contrôleur
protected function authentification()
{
    return 'auth.basic';
}

// Configuration dans AuthDiProvider
// Utilise auth.userprovider (database ou array)
```

### Authentification par clé d'API

```php
// Dans un contrôleur
protected function authentification()
{
    return 'auth.key';
}

// L'utilisateur doit fournir une clé dans le header
// X-API-Key: ma_cle_secrete
```

---

## Sécurité

### Protection CSRF

#### Générer un token pour votre formulaire

```php
// Dans un contrôleur, avant d'afficher un formulaire
$token = $this->getFormToken();

// Dans le template
<form method="post">
    <input type="hidden" name="_token" value="<?= $token ?>">
    <!-- champs du formulaire -->
</form>
```

#### Valider un token

```php
// Dans le contrôleur de traitement du formulaire
try
{
    $this->checkFormToken(); // Vérifie $_POST['_token'] par défaut
    $this->checkFormToken('_csrf_token'); // Champ personnalisé
    
    // Traitement du formulaire...
}
catch (\wlib\Http\Server\HttpException $e)
{
    // Le token est invalide ou manquant
    $this->haltMethodNotAllowed('Formulaire invalide, veuillez réessayer.');
}
```

### Anti-Flooding (Limitation de requêtes)

#### Configuration dans un contrôleur

```php
<?php
namespace App\Controllers;

use wlib\Application\Controllers\Controller;

class ApiController extends Controller
{
    // Limite à 5 secondes entre deux requêtes
    protected function limit(): int
    {
        return 5000; // Temps en millisecondes
    }
    
    public function start()
    {
        // Si la limite est dépassée, une exception 429 est lancée
        $this->response->json(['data' => 'ok']);
    }
}
```

#### Mécanisme

- Utilise la session pour stocker le timestamp de la dernière requête
- Clé de session : `wlib.http.throttle.{IP}.{controller_uid}`
- Lance une exception `429 Too Many Requests` si la limite est dépassée
- Header `Retry-After` inclus dans la réponse

### Filtrage des Entrées

```php
// Dans un contrôleur
public function start()
{
    // Validation d'un email
    $email = filter_var($this->data('email'), FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $this->haltBadRequest('Email invalide');
    }
    
    // Validation d'un entier
    $id = filter_var($this->arg(0), FILTER_VALIDATE_INT);
    if ($id === false) {
        $this->haltBadRequest('ID invalide');
    }
    
    // Sanitization
    $name = filter_var($this->data('name'), FILTER_SANITIZE_STRING);
    $content = filter_var($this->data('content'), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
}
```

### Bonnes Pratiques de Sécurité

1. **Toujours valider les entrées utilisateur**
2. **Utiliser le hachage pour les mots de passe** (bcrypt par défaut)
3. **Protéger les formulaires avec CSRF**
4. **Limiter l'accès aux contrôleurs sensibles** avec `authentification()` et `allow()`
5. **Activer le mode production** pour désactiver le debug
6. **Configurer correctement les headers de sécurité**

---

## Cache HTTP

### Activation dans un contrôleur

```php
<?php
namespace App\Controllers;

use wlib\Application\Controllers\Controller;

class StaticPageController extends Controller
{
    // Cache pour 1 heure (3600 secondes)
    protected function cache(): int
    {
        return 3600;
    }
    
    public function start()
    {
        $this->response->html('<h1>Page statique</h1>');
    }
}
```

### Mécanisme

1. **Première requête** : la réponse est générée et sauvegardée dans un fichier de cache
2. **Requêtes suivantes** : si le cache est valide, la réponse est servie depuis le cache
3. **Expiration** : après le délai configuré, le cache est automatiquement purgé

### Configuration

```php
// Chemin de stockage (config/app.php)
'cache_path' => __DIR__.'/../storage/cache',
```

### Personnalisation avancée

```php
// Dans un contrôleur
protected function cache(): int
{
    // Cache conditionnel
    if ($this->isGet()) {
        return 3600; // Cache les requêtes GET
    }
    return 0; // Pas de cache pour les autres méthodes
}
```

### Fichiers de cache

Les fichiers de cache sont stockés avec le format :
```
{cache_path}/{controller_uid}.cache.{timestamp}
```

Exemple : `/storage/cache/index.cache.1654321000`

---

## Internationalisation

### Configuration

```php
// config/app.php
return [
    'app' => [
        'i18n_path' => __DIR__.'/../resources/locales',
        'i18n_locale' => 'fr_FR', // Locale par défaut
    ],
];
```

### Fichiers de traduction

Les fichiers doivent être au format `.mo` (format standard gettext) :

```
resources/
└── locales/
    ├── en_US.mo
    ├── fr_FR.mo
    └── es_ES.mo
```

### Utilisation dans le code

```php
// Traduction simple
__('Bonjour le monde');

// Traduction avec domaine
__('Bonjour', 'wapp'); // wapp est le domaine par défaut (W_L10N_DOMAIN)

// Traduction avec variables
__('Bonjour %s', 'Jean');

// Pluriel
__('%d article', $count);
__('%d articles', $count);

// Traduction avec contexte
__('Poster', 'verb'); // Contexte pour distinguer les homonymes
```

### Génération des fichiers .mo

Utilisez les outils standards gettext :

```bash
# Créer un fichier .po
xgettext --output=messages.po --language=PHP --keyword=__ src/

# Compiler en .mo
msgfmt -o fr_FR.mo messages-fr.po
```

> 📌 Personnellement, je gère mes fichiers de traduction avec [Poedit](https://poedit.com/) ; avec la version gratuite (ce lien est proposé sans aucune collaboration commerciale ;-))

### Personnalisation du translator

```php
// Récupérer le translator
$translator = $this->app->get('translator');

// Ajouter manuellement des traductions
$translator->addTranslation('Bonjour', 'Hello', 'en_US');

// Changer la locale
$translator->setLocale('fr_FR');
```

---

## Templates

### Moteur de templates

Le framework utilise un moteur de templates PHP simple mais puissant.

### Configuration

```php
// config/app.php
return [
    'app' => [
        'templates_path' => __DIR__.'/../templates',
        'templates_ext' => '.html.php', // Extension par défaut
    ],
];
```

### Utilisation dans un contrôleur

```php
public function start()
{
    $data = [
        'title' => 'Bienvenue',
        'users' => ['Alice', 'Bob', 'Charlie'],
        'app' => $this->app,
    ];
    
    // Rendre un template
    $html = $this->app['app.templates']->render('index', $data);

    // Ou, en étendant FrontController, la méthode render() est directement disponible
    // $html = $this->render('index', $data);
    
    $this->response->html($html);
}
```

### Structure des templates

```
templates/
├── layout.html.php          # Layout principal
├── header.html.php          # En-tête
├── footer.html.php          # Pied de page
├── index.html.php           # Page d'accueil
├── articles/
│   ├── index.html.php       # Liste des articles
│   ├── show.html.php        # Affichage d'un article
│   └── form.html.php        # Formulaire d'article
└── auth/
    ├── login.html.php       # Formulaire de connexion
    └── register.html.php     # Formulaire d'inscription
```

### Exemple de template

```php
<?php // templates/articles/show.html.php ?>

<?php $this->layout('layout', ['title' => $article['title']]) ?>

<article>
    <h1><?= htmlspecialchars($article['title']) ?></h1>
    <p class="date">Publié le <?= date('d/m/Y', strtotime($article['created_at'])) ?></p>
    <div class="content">
        <?= $article['content'] ?>
    </div>
    
    <?php if (!empty($article['tags'])): ?>
        <div class="tags">
            <?php foreach ($article['tags'] as $tag): ?>
                <span class="tag"><?= htmlspecialchars($tag) ?></span>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</article>
```

### Layouts

```php
<?php // templates/layout.html.php ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title><?= htmlspecialchars($title ?? 'Mon Site') ?></title>
    <link rel="stylesheet" href="/css/style.css">
</head>
<body>
    <?= $this->render('header') ?>
    
    <main>
        <?= $content ?? '' ?>
    </main>
    
    <?= $this->render('footer') ?>
    
    <script src="/js/app.js"></script>
</body>
</html>
```

### Utilisation dans un contrôleur avec layout

```php
public function start()
{
    $content = $this->app['app.templates']->render('articles/show', [
        'article' => $article,
    ]);
    
    $html = $this->app['app.templates']->render('layout', [
        'title' => $article['title'],
        'content' => $content,
    ]);
    
    $this->response->html($html);
}
```

### Ajout de chemins de templates

```php
// Dans un DiBoxProvider personnalisé
$engine = $this->app['app.templates'];
$engine->addSrcPath(__DIR__.'/../custom/templates');
$engine->setFileExtension('.tpl.php');
```

---

## Mailer

### Configuration

```php
// config/app.php
return [
    'mailer' => [
        'driver' => 'smtp', // sendmail, mail, smtp
        'host' => 'smtp.example.com',
        'port' => 587,
        'username' => 'user@example.com',
        'password' => 'password',
        'encryption' => 'tls', // '', 'ssl', 'tls'
        'from' => 'noreply@example.com',
        'replyto' => 'support@example.com',
        'charset' => 'utf-8',
    ],
];
```

### Envoi d'un email simple

```php
// Récupérer l'instance Mail
$mail = $this->app->get('mailer.mail');

// Configurer l'email
$mail->setFrom('contact@monsite.com', 'Mon Site');
$mail->addAddress('user@example.com', 'Jean Dupont');
$mail->addReplyTo('support@monsite.com');
$mail->addCC('cc@example.com');
$mail->addBCC('bcc@example.com');

$mail->Subject = 'Sujet de l\'email';
$mail->Body    = '<h1>Bonjour !</h1><p>Ceci est un email HTML.</p>';
$mail->AltBody = 'Bonjour ! Ceci est la version texte.';

// Envoi
if (!$mail->send()) {
    $this->haltInternalServerError('Échec de l\'envoi : ' . $mail->ErrorInfo);
}
```

### Envoi avec template

Le framework fournit un système de templates pour les emails.

```php
$mail = $this->app->get('mailer.mail');

$mail->addAddress('user@example.com');
$mail->Subject = 'Bienvenue sur notre site';

// Utiliser un template
$mail->setTemplateBody('mails/welcome', [
    'username' => 'Jean Dupont',
    'activation_link' => 'https://monsite.com/activate?token=abc123',
]);

$mail->send();
```

### Structure des templates d'emails

```
resources/
└── templates/
    └── mails/
        ├── layout.html.php      # Layout des emails
        ├── header.html.php      # En-tête
        ├── footer.html.php      # Pied de page
        ├── welcome.html.php     # Email de bienvenue
        └── password-reset.html.php # Reset de mot de passe
```

### Exemple de template d'email

```php
<?php // resources/templates/mails/welcome.html.php ?>
<?php 
// Le sujet doit être défini avant l'appel à setTemplateBody
$mail->Subject = 'Bienvenue sur ' . $appname;
?>

<?php $this->render('mails/header') ?>

<h1>Bonjour <?= htmlspecialchars($username) ?> !</h1>

<p>Merci de vous être inscrit sur <?= $appname ?>.</p>

<p>Pour activer votre compte, veuillez cliquer sur le lien suivant :</p>

<p>
    <a href="<?= $activation_link ?>" style="display: inline-block; 
        padding: 10px 20px; background: #007bff; color: white; 
        text-decoration: none; border-radius: 5px;">
        Activer mon compte
    </a>
</p>

<p>Si vous n'avez pas créé ce compte, vous pouvez ignorer cet email.</p>

<?php $this->render('mails/footer') ?>
```

### Pièces jointes

```php
$mail->addAttachment('/chemin/vers/fichier.pdf', 'document.pdf');
$mail->addAttachment('/chemin/vers/image.jpg', 'photo.jpg');
```

---

## Cryptographie

### HashManager

Gère le hachage des mots de passe avec différents algorithmes.

#### Configuration

```php
// Dans AuthDiProvider, la configuration est lue depuis config/app.php
'security' => [
    'database' => [
        'hash_algo' => 'bcrypt', // bcrypt ou plaintext
        'hash_options' => [
            'cost' => 12, // Pour bcrypt
        ],
    ],
],
```

#### Utilisation directe

```php
// Récupérer HashManager
$hashManager = $this->app->get('hash.manager', [
    'bcrypt', ['cost' => 12]
]);

// Hacher un mot de passe
$hash = $hashManager->hash('mon_mot_de_passe');

// Vérifier un mot de passe
$isValid = $hashManager->check('mon_mot_de_passe', $hash);

// Obtenir des informations sur un hash
$info = $hashManager->info($hash);
// Retourne : ['algo' => 'bcrypt', 'algoName' => 'bcrypt', 'options' => ['cost' => 12]]
```

### Algorithmes disponibles

| Algorithme | Constante | Description |
|-----------|-----------|-------------|
| Bcrypt | `HashManager::ALGO_BCRYPT` | Hachage sécurisé (recommandé) |
| Plaintext | `HashManager::ALGO_PLAINTEXT` | Pas de hachage (test uniquement) |

### Options pour Bcrypt

```php
$options = [
    'cost' => 12, // Coût de hachage (10-12 recommandé)
];
```

### Bonnes pratiques

1. **Toujours utiliser bcrypt en production**
2. **Ne jamais stocker les mots de passe en clair**
3. **Utiliser un coût adapté** (12 est un bon compromis)
4. **Générer un nouveau hash à chaque changement de mot de passe**

---

## Modèle utilisateur

### Structure de la table

Le framework fournit un modèle `User` prêt à l'emploi avec une structure de table SQL.

#### SQL de création

```sql
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(80) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(64),
    token VARCHAR(255),
    can_login INTEGER,
    created_at DATETIME,
    updated_at DATETIME,
    verified_at DATETIME,
    deleted_at DATETIME
);
```

### Méthodes du modèle `User`

```php
// Récupérer le modèle
$userTable = $this->app->getTable(\wlib\Application\Models\User::class);

// Créer la table (si elle n'existe pas)
$userTable->createTable();

// Créer un utilisateur
$userId = $userTable->save([
    'name' => 'Jean Dupont',
    'email' => 'jean@example.com',
    'password' => $this->app['hash.manager']->hash('password123'),
    'can_login' => true,
    'verified_at' => 'NOW()',
]);

// Trouver un utilisateur par ID
$user = $userTable->findId(1);

// Trouver un utilisateur par email
$user = $userTable->findId('email', 'jean@example.com');

// Vérifier si un compte est actif (email vérifié)
$isActive = $userTable->isAccountActive('jean@example.com');

// Mettre à jour un utilisateur
$userTable->save([
    'name' => 'Jean Marie Dupont',
], $userId);

// Supprimer un utilisateur (soft delete)
$userTable->save([
    'deleted_at' => 'NOW()',
], $userId);
```

### Utilisation avec WebGuard

`WebGuard` utilise automatiquement le modèle `User` pour :

- L'inscription des utilisateurs
- La vérification des emails
- La gestion des mots de passe oubliés
- Le renouvellement des mots de passe

---

## Services et injection de dépendances

### DiBox (Conteneur d'injection)

Le `Kernel` étend `wlib\Di\DiBox`, un [conteneur d'injection de dépendances](https://github.com/SamRay1024/wlib-dibox) léger.

#### Méthodes de DiBox

```php
// Lier une clé à une valeur
$app->bind('service.name', $instance);

// Lier une clé à une factory
$app->bind('service.name', function($app, $args) {
    return new MyService($args[0]);
});

// Lier une clé à un singleton
$app->singleton('service.name', MyService::class);
$app->singleton('service.name', function($app, $args) {
    return new MyService();
});

// Récupérer un service
$service = $app->get('service.name');
$service = $app['service.name'];

// Vérifier si un service existe
if ($app->has('service.name')) { ... }

// Instancier une classe avec injection automatique
$instance = $app->make(MyClass::class, [$arg1, $arg2]);
```

### DiBoxProviders

Les providers sont des classes qui enregistrent des services dans le conteneur.

#### Providers Intégrés

| Provider | Classe | Services Enregistrés |
|----------|--------|---------------------|
| Debug | `DebugDiProvider` | Debugging (Tracy, Clockwork) |
| Sys | `SysDiProvider` | Request, Response, Session, Router, DB |
| Templates | `EngineDiProvider` | app.templates |
| Auth | `AuthDiProvider` | auth.*, guard.web, auth.userprovider |
| Hash | `HashDiProvider` | hash.manager |
| Mailer | `MailerDiProvider` | mailer.mail |
| L10n | `L10nDiProvider` | translator |
| Tracy | `TracyDiProvider` | Panneaux Tracy pour les DB |

### Création d'un provider personnalisé

```php
<?php
namespace App\Providers;

use wlib\Di\DiBox;
use wlib\Di\DiBoxProvider;

class MyServiceProvider implements DiBoxProvider
{
    public function provide(DiBox $box)
    {
        $box->singleton('my.service', function($box, $args) {
            return new \App\Services\MyService($box['db.default']);
        });
        
        $box->bind('my.value', 'valeur par défaut');
    }
    
    // Méthode optionnelle appelée après tous les provide()
    public function boot(DiBox $box)
    {
        // Initialisation après que tous les services soient enregistrés
    }
}
```

#### Enregistrement du Provider

```php
// Dans le Kernel (ou via un hook)
$app->register(\App\Providers\MyServiceProvider::class);
```

---

## Debugging

### Tracy

Le framework intègre [Tracy](https://tracy.nettrine.fr/) pour le debugging avancé.

#### Configuration

```php
// Dans DebugDiProvider
Debugger::enable(Debugger::Development); // ou Production
Debugger::$strictMode = true;
Debugger::$dumpTheme = 'dark';
Debugger::$showLocation = true;
Debugger::$logDirectory = '/chemin/vers/logs';
```

#### Fonctionnalités

- **Barre de debug** : affiche les informations en bas de page
- **Dumping de variables** : `dump($variable);` ou `bdump($variable);`
- **Journalisation** : `Debugger::log('Message');`
- **Gestion des erreurs** : affichage des exceptions avec stack trace

### Fonctions utilitaires wlib/utils

Le package `wlib/utils` installé avec `wlib/application` fournit des fonctions de débogage simples et efficaces pour un debugging rapide.

| Fonction | Description |
|----------|-------------|
| `vd(...$var)` | Dump and continue : affiche les variables avec leurs types et valeurs, puis continue l'exécution |
| `vdd(...$var)` | Dump and die : affiche les variables puis arrête immédiatement l'exécution |

#### Exemples d'utilisation

```php
// Import des fonctions (si utiliser en dehors de l'autoload)
use function wlib\vd;
use function wlib\vdd;

// Dump d'une variable simple
$users = ['Cédric', 'Marie', 'Pierre'];
vd($users);

// Dump de plusieurs variables
$user = ['name' => 'Cédric', 'age' => 35];
$config = ['debug' => true, 'env' => 'dev'];
vd($user, $config);

// Dump et arrêt (utile pour vérifier avant de continuer)
$entity = $this->entityManager->find(User::class, 1);
vdd($entity); // L'exécution s'arrête ici

// Dans une vue ou un template
<?php vd($this->data); ?>
```

> **Note**: Les fonctions `vd()` et `vdd()` affichent également le fichier et la ligne d'appel, ainsi que le timestamp. En CLI, l'affichage est adapté au terminal.

### Clockwork

[Clockwork](https://itsgoingd.com/clockwork/) est intégré pour le profiling.

#### Configuration

```php
// Dans DebugDiProvider (en développement uniquement)
$box->bind('sys.clockwork_config', [
    'api' => '/clockwork/?request=',
    'register_helpers' => true,
    'storage_files_path' => config('app.logs_path') . '/clockwork',
    'web' => [
        'enable' => true,
        'path' => config('app.public_path') . '/vendor/clockwork',
        'uri' => '/vendor/clockwork'
    ]
]);
```

#### Utilisation

```php
// Marqueur de temps
clock()->startEvent('db.query', 'SELECT * FROM users');
// ... exécution de la requête ...
clock()->endEvent('db.query');

// Log personnalisé
clock()->addEvent(['name' => 'custom', 'data' => ['key' => 'value']]);

// Requêtes DB automatiques
// Un hook est déjà configuré pour logger toutes les requêtes DB
```

### Accès à l'Interface Clockwork

1. Visitez `/vendor/clockwork` dans votre navigateur
2. Ou cliquez sur l'icône Clockwork dans la barre Tracy

---

## Structure des Fichiers

```
application/
├── init.php                    # Initialisation globale (constantes)
├── composer.json               # Dépendances Composer
├── README.md                   # Documentation principale
├── resources/
│   ├── templates/
│   │   └── mails/
│   │       ├── auth/
│   │       │   ├── confirm-email.html.php
│   │       │   ├── password-updated.html.php
│   │       │   └── renew-password.html.php
│   │       ├── footer.html.php
│   │       └── header.html.php
│   └── locales/                # Fichiers .mo pour i18n
└── src/
    ├── Auth/
    │   ├── AuthDiProvider.php
    │   ├── AuthProvider.php
    │   ├── AuthProviderInterface.php
    │   ├── AuthenticateException.php
    │   ├── BasicAuthProvider.php
    │   ├── KeyAuthProvider.php
    │   ├── PublicAuthProvider.php
    │   ├── User.php
    │   ├── UserArrayProvider.php
    │   ├── UserDbProvider.php
    │   ├── UserInterface.php
    │   ├── UserProviderInterface.php
    │   ├── WebAuthProvider.php
    │   ├── WebGuard.php
    │   └── WsseAuthProvider.php
    ├── Controllers/
    │   ├── AllowLoggedInUsersTrait.php
    │   ├── ClockworkController.php
    │   ├── Controller.php
    │   ├── FrontController.php
    │   ├── RestController.php
    │   ├── RestrictedWebAreaTrait.php
    │   └── WebGateController.php
    ├── Crypto/
    │   ├── AbstractHashDriver.php
    │   ├── BcryptDriver.php
    │   ├── HashDiProvider.php
    │   ├── HashDriverInterface.php
    │   └── HashManager.php
    │   └── PlaintextDriver.php
    ├── Exceptions/
    │   └── UnexpectedFieldValueException.php
    ├── L10n/
    │   └── L10nDiProvider.php
    ├── Mailer/
    │   ├── Mail.php
    │   └── MailerDiProvider.php
    ├── Models/
    │   └── User.php
    ├── Sys/
    │   ├── Cache.php
    │   ├── DebugDiProvider.php
    │   ├── Kernel.php
    │   ├── Router.php
    │   ├── SysDiProvider.php
    │   └── TracyDiProvider.php
    └── Templates/
        ├── Engine.php
        └── EngineDiProvider.php
```

---

## Bonnes Pratiques

### Organisation du code

1. **Contrôleurs** : Un contrôleur par fonctionnalité
2. **Services** : Externaliser la logique métier dans des services
3. **Templates** : Séparer le layout, les partials et les vues
4. **Configuration** : Utiliser les fichiers de config pour tout ce qui peut varier

### Sécurité

1. **Toujours valider les entrées** : Utiliser `filter_var()` et les validateurs
2. **Échapper les sorties** : Utiliser `htmlspecialchars()` dans les templates
3. **CSRF** : Toujours protéger les formulaires
4. **Authentification** : Utiliser les providers intégrés
5. **Hachage** : Toujours hacher les mots de passe avec bcrypt
6. **Limitation** : Configurer des limites anti-flooding pour les APIs

### Performance

1. **Cache HTTP** : Utiliser le cache pour les pages statiques
2. **Requêtes DB** : Optimiser les requêtes, utiliser les index
3. **Mode Production** : Activer le mode production en environnement de prod
4. **Minification** : Minifier les assets CSS/JS

### Maintenance

1. **Logging** : Utiliser Tracy pour le debugging
2. **Profiling** : Utiliser Clockwork pour identifier les goulots
3. **Tests** : Écrire des tests unitaires et fonctionnels
4. **Documentation** : Documenter le code et les APIs

---

## Exemples complets

### 1. Application minimale

**public/index.php**
```php
<?php
require __DIR__.'/../vendor/autoload.php';

use wlib\Application\Sys\Kernel;

$app = new Kernel(__DIR__, [
    'sys.composer' => require __DIR__.'/../vendor/autoload.php',
    'sys.config_dir' => __DIR__.'/../config',
]);

$app->run();
```

**config/app.php**
```php
<?php
return [
    'app' => [
        'name' => 'Minimal App',
        'production' => false,
        'timezone' => 'Europe/Paris',
        'ns_controllers' => 'App\\Controllers',
        'cache_path' => __DIR__.'/../storage/cache',
        'logs_path' => __DIR__.'/../storage/logs',
    ],
    'databases' => [
        'default' => [
            'driver' => 'sqlite',
            'database' => __DIR__.'/../storage/database.sqlite',
        ],
    ],
];
```

**app/Controllers/IndexController.php**
```php
<?php
namespace App\Controllers;

use wlib\Application\Controllers\Controller;

class IndexController extends Controller
{
    public function start()
    {
        $this->response->html('<h1>Hello World!</h1>');
    }
}
```

### 2. API REST avec authentification

**app/Controllers/Api/PostsController.php**
```php
<?php
namespace App\Controllers\Api;

use wlib\Application\Controllers\RestController;

class PostsController extends RestController
{
    protected function authentification()
    {
        return 'auth.key';
    }
    
    public function get()
    {
        $posts = $this->db->from('posts')->all();
        $this->response->json($posts);
    }
    
    public function getById($id)
    {
        $post = $this->db->from('posts')->where('id = ?', $id)->first();
        
        if (!$post) {
            $this->haltNotFound('Post not found');
        }
        
        $this->response->json($post);
    }
    
    public function post()
    {
        $data = json_decode($this->rawData(), true);
        
        if (empty($data['title'])) {
            $this->haltBadRequest('Title is required');
        }
        
        $id = $this->db->into('posts')->insert([
            'title' => $data['title'],
            'content' => $data['content'] ?? '',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        
        $this->response->json(['id' => $id], 201);
    }
}
```

### 3. Zone admin avec authentification Web

**app/Controllers/Admin/DashboardController.php**
```php
<?php
namespace App\Controllers\Admin;

use wlib\Application\Controllers\Controller;

class DashboardController extends Controller
{
    protected function authentification()
    {
        return 'auth.web';
    }
    
    protected function allow(): bool
    {
        // Seul l'admin peut accéder
        return $this->user && $this->user->getUsername() === 'admin';
    }
    
    public function start()
    {
        $stats = $this->getStats();
        
        $this->response->html($this->app['app.templates']->render('admin/dashboard', [
            'stats' => $stats,
            'user' => $this->user,
        ]));
    }
    
    private function getStats(): array
    {
        return [
            'users_count' => $this->db->from('users')->count(),
            'posts_count' => $this->db->from('posts')->count(),
        ];
    }
}
```

**app/Controllers/Auth/LoginController.php**
```php
<?php
namespace App\Controllers\Auth;

use wlib\Application\Controllers\Controller;

class LoginController extends Controller
{
    protected function authentification()
    {
        return 'auth.public';
    }
    
    public function start()
    {
        if ($this->isGet()) {
            $this->showForm();
        } elseif ($this->isPost()) {
            $this->handleLogin();
        }
    }
    
    private function showForm()
    {
        $token = $this->getFormToken();
        
        $this->response->html($this->app['app.templates']->render('auth/login', [
            'token' => $token,
        ]));
    }
    
    private function handleLogin()
    {
        $this->checkFormToken();
        
        $email = $this->data('email');
        $password = $this->data('password');
        
        try {
            $guard = $this->app['guard.web'];
            $user = $guard->login($email, $password);
            
            $this->redirect('/admin/dashboard');
        } catch (\wlib\Application\Auth\AuthenticateException $e) {
            $this->response->html($this->app['app.templates']->render('auth/login', [
                'token' => $this->getFormToken(),
                'error' => $e->getMessage(),
            ]));
        }
    }
}
```

**templates/auth/login.html.php**
```php
<h1>Connexion</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<form method="post">
    <input type="hidden" name="_token" value="<?= $token ?>">
    
    <div>
        <label for="email">Email:</label>
        <input type="email" name="email" id="email" required>
    </div>
    
    <div>
        <label for="password">Mot de passe:</label>
        <input type="password" name="password" id="password" required>
    </div>
    
    <button type="submit">Se connecter</button>
</form>

<p><a href="/auth/register">S'inscrire</a> | <a href="/auth/forgot">Mot de passe oublié ?</a></p>
```

### 4. Contrôleur avec cache et limitation

**app/Controllers/Api/DataController.php**
```php
<?php
namespace App\Controllers\Api;

use wlib\Application\Controllers\RestController;

class DataController extends RestController
{
    protected function authentification()
    {
        return 'auth.key';
    }
    
    // Cache pour 10 minutes
    protected function cache(): int
    {
        return 600;
    }
    
    // Limite à 10 requêtes par seconde
    protected function limit(): int
    {
        return 100; // 100ms entre les requêtes
    }
    
    public function get()
    {
        $data = $this->fetchData();
        $this->response->json($data);
    }
    
    private function fetchData(): array
    {
        // Simulation de données coûteuses
        return [
            'items' => range(1, 100),
            'generated_at' => date('Y-m-d H:i:s'),
        ];
    }
}
```

---

## Dépannage

### Problèmes courants

#### 1. Routage ne fonctionne pas

**Vérifications :**
- Le namespace des contrôleurs est correct dans `config/app.php`
- Les fichiers des contrôleurs existent et sont correctement nommés
- Le nom des contrôleurs se termine par `Controller`
- Les URL respectent la convention de nommage

#### 2. Authentification échoue

**Vérifications :**
- Le provider d'authentification est correctement configuré
- Les utilisateurs existent dans le provider (DB ou tableau)
- Les mots de passe sont correctement hachés
- La session est démarrée (`session_start()`)

#### 3. Erreur 404 sur toutes les pages

**Vérifications :**
- Le répertoire `config` existe et contient `app.php`
- Le namespace des contrôleurs est correct
- Le `base_uri` est correctement configuré (surtout pour les sous-dossiers)

#### 4. Erreur de base de données

**Vérifications :**
- La configuration de la DB dans `config/app.php` est correcte
- Les extensions PDO sont installées
- Le driver est supporté (mysql, sqlite, pgsql)

#### 5. Templates non trouvés

**Vérifications :**
- Le chemin `templates_path` est correct dans `config/app.php`
- L'extension des templates est correcte
- Les fichiers de templates existent

### Activation du debug

```php
// Dans config/app.php
return [
    'app' => [
        'production' => false, // Mode développement
        'logs_path' => __DIR__.'/../storage/logs',
    ],
];
```

---

## Contribution

### Signaler un bug

1. Vérifiez que le bug n'a pas déjà été signalé
2. Créez une issue sur GitHub avec :
   - Description claire du problème
   - Étapes pour reproduire
   - Version de PHP
   - Version du framework
   - Message d'erreur complet

### Développement

1. Forker le dépôt
2. Créer une branche (`git checkout -b feature/nouvelle-fonctionnalite`)
3. Committer vos changements (`git commit -am 'Ajout de la fonctionnalité'`)
4. Pousser sur la branche (`git push origin feature/nouvelle-fonctionnalite`)
5. Créer une Pull Request

### Tests

Le framework utilise Pest pour les tests :

```bash
# Lancer les tests
./vendor/bin/pest

# Lancer les tests avec couverture
./vendor/bin/pest --coverage
```

---

## Changelog

Voir [CHANGELOG.md](CHANGELOG.md) pour l'historique des versions.

---

## Licence

Ce logiciel est distribué sous la **licence CeCILL 2.1**.

Voir le fichier [LICENCE](LICENCE) pour plus de détails.

---

## Auteurs

- **Cédric Ducarre** - Développeur principal

---

© 2026 wlib/application - Framework PHP léger et modulaire
