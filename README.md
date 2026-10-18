# Plugin JeeDore pour Jeedom (Delta Dore Tydom)

**JeeDore** est un plugin Jeedom permettant de piloter et de superviser les équipements domotiques **Delta Dore** via une passerelle **Tydom** (Tydom 1.0, 2.0, Home, Pro).

Cette version a été modernisée pour être pleinement compatible avec **Jeedom 4.4+ (Jeedom Luna)** et **PHP 8+**.

---

## Architecture et fonctionnement

Le plugin repose sur une communication bidirectionnelle entre **Jeedom (PHP/JS)** et un **démon local .NET Core (`Rezolv.JeeDore.Server`)** qui maintient la connexion WebSocket permanente avec la box Delta Dore Tydom.

```
┌──────────────────┐    Socket TCP (127.0.0.1:13666)    ┌──────────────────────────┐    WebSocket TLS (local/distant)    ┌──────────────────┐
│                  │ ─────────────────────────────────► │                          │ ──────────────────────────────────► │                  │
│   Jeedom (PHP)   │                                    │        Démon .NET        │                                     │   Box Tydom      │
│  plugin jeedore  │ ◄───────────────────────────────── │  Rezolv.JeeDore.Server   │ ◄────────────────────────────────── │   Delta Dore     │
└──────────────────┘    HTTP POST (core/php/callback)   └──────────────────────────┘       Événements temps réel         └──────────────────┘
```

### 1. De Jeedom vers la box Tydom (Commandes et Requêtes)
- La classe [`jeedoreDaemon`](core/class/jeedoreDaemon.class.php) ouvre un socket TCP local sur `127.0.0.1:13666`.
- Chaque action utilisateur (clic sur une commande, synchronisation des équipements, identification, rafraîchissement cron) est sérialisée en JSON et transmise au démon .NET via [`jeedore::sendToDaemon()`](core/class/jeedore.class.php).

### 2. Du démon .NET vers Jeedom (Retours d'état et Découverte)
- Dès que la box Tydom émet une trame (ou répond à une synchronisation), le démon .NET envoie une requête HTTP POST JSON à [`core/php/callback.php`](core/php/callback.php) avec un paramètre `cmd` :
  - `cmd=box` : Crée ou met à jour l'équipement système représentant la passerelle Tydom.
  - `cmd=geteqlogic` : Crée les nouveaux équipements/scénarios découverts et met à jour la catégorie d'usage (`tags`) des équipements existants sans écraser les personnalisations Jeedom (nom, pièce parente, visibilité).
  - `cmd=getcmd` : Crée et configure les commandes (`info` et `action`) de chaque équipement et les relie entre elles.
  - `cmd=getcmddata` : Met à jour en temps réel les valeurs d'état des commandes et déclenche les événements Jeedom.
  - `cmd=gethistoric` / `cmd=gethistorictranslated` / `cmd=getinstant` : Enregistre les historiques de consommation et les journaux d'événements (notamment pour l'alarme Tyxal+).
  - `cmd=pingBeat` : Enregistre le battement de cœur (heartbeat) confirmant que la liaison Tydom est active.

---

## Arborescence du projet

```text
jeedore/
├── core/
│   ├── ajax/
│   │   └── jeedore.ajax.php          # Point d'entrée AJAX pour l'interface d'administration
│   ├── class/
│   │   ├── jeedore.class.php         # Classe principale eqLogic (gestion du démon, crons, icônes dynamiques)
│   │   ├── jeedoreCmd.class.php      # Classe cmd (exécution des commandes info/action vers Tydom)
│   │   └── jeedoreDaemon.class.php   # Client socket TCP vers le démon .NET (127.0.0.1:13666)
│   ├── i18n/                         # Fichiers de traduction (fr_FR, en_US)
│   ├── php/
│   │   └── callback.php              # Webhook local appelé par le démon .NET pour pousser les données dans Jeedom
│   └── template/                     # Templates HTML/JS des widgets (dashboard & mobile)
├── desktop/
│   ├── js/
│   │   ├── jeedore.js                # Logique JS de la page principale du plugin
│   │   └── histo.js                  # Logique JS de la modale des messages d'historique d'alarme
│   ├── modal/
│   │   └── histo.php                 # Fenêtre modale de personnalisation des messages d'événements
│   └── php/
│       └── jeedore.php               # Vue principale de gestion des équipements par catégorie
├── plugin_info/
│   ├── configuration.php             # Formulaire de configuration du plugin (IP Tydom, MAC, mots de passe)
│   ├── info.json                     # Métadonnées Jeedom du plugin
│   └── install.php                   # Hooks d'installation, de mise à jour et de désinstallation
└── ressources/
    ├── daemon/                       # Binaires du démon .NET Core (linux-x64, linux-arm) et script d'installation
    └── images/                       # Icônes dynamiques par catégorie et état (thèmes light & dark)
```

---

## Spécificités techniques (Jeedom 4.4+ & PHP 8)

- **Affichage des icônes d'état (`data:image/png;base64`)** : Depuis Jeedom 4.2/4.4, la configuration Apache bloque par défaut (`403 Forbidden`) l'accès HTTP direct aux sous-dossiers non standards comme `ressources/images/`. La méthode [`jeedore::resolveImage()`](core/class/jeedore.class.php) encode automatiquement les icônes en Data URI Base64 (avec mise en cache mémoire et repli automatique du thème sombre vers le thème clair si une variante d'icône est absente).
- **Synchronisation non destructive** : Le bouton **Synchroniser équipements** met à jour les catégories d'usage modifiées dans l'application Tydom (par exemple un équipement passé de *Autre* à *Lumière* ou *Prise*) tout en préservant le nom Jeedom, l'objet parent et les paramètres des commandes existantes.
- **Sécurité des identifiants** : Les fichiers générés dynamiquement contenant les identifiants de connexion (`jeedore_config.json`, `jeedore_jeedomconfig.json`, `jeedore_cron.json`) sont exclus du contrôle de version via [`.gitignore`](.gitignore).

---

## Crédits et Licence

- **Auteur original du plugin et du démon .NET (`Rezolv.JeeDore.Server`)** : **Nicolas Arfeuil** ([rezolv-fr / jeedoredaemon-dotnet](https://github.com/rezolv-fr/jeedoredaemon-dotnet)) — un grand merci pour le travail de rétro-ingénierie du protocole Delta Dore Tydom et la création initiale du plugin JeeDore.
- **Adaptation et modernisation Jeedom 4.4+ / PHP 8+** : Corrections d'affichage des icônes, compatibilité Packery/FontAwesome 5, synchronisation dynamique des catégories d'usage Tydom et documentation complète du code.
- **Licence** : Distribué sous licence **MIT** (conformément au projet d'origine défini dans [`plugin_info/info.json`](plugin_info/info.json)).

