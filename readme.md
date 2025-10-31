# Suivi des Interventions

Plugin professionnel de suivi des mises à jour et interventions sur les sites web.

## 📋 Description

Ce plugin permet de gérer et suivre les interventions techniques effectuées sur différents projets web. Il offre un système complet de gestion des quotas, des clients et des projets avec des restrictions d'accès granulaires.

## ✨ Fonctionnalités principales

### 🔧 Gestion des Interventions
- **Custom Post Type "Intervention"** avec slug `maj`
- Champs personnalisés : date d'intervention, statut (terminée/en cours)
- Description détaillée avec éditeur WYSIWYG
- Interface d'administration complète

### 📁 Gestion des Projets
- **Taxonomie "Projet"** liée aux interventions
- Quota d'interventions par projet
- Date d'expiration des quotas
- Informations client et URL du projet
- Calcul automatique de la progression

### 👥 Rôles Utilisateurs
- **Rôle "Client" (bsdclient)** avec accès restreint
- Liaison clients ↔ projets
- Interface en lecture seule pour les clients
- Informations supplémentaires (société, téléphone)

### 📊 Fonctionnalités avancées
- **Barres de progression colorées** pour les quotas
  - 🟢 Vert : 0-45% utilisé
  - 🔵 Bleu : 46-80% utilisé
  - 🔴 Rouge : 81-100%+ utilisé
- **Filtres avancés** : date, statut, projet
- **Colonnes personnalisées** dans les listes
- **Dashboard client** personnalisé

## 🚀 Installation

1. **Télécharger le plugin**
   ```bash
   # Cloner ou télécharger dans wp-content/plugins/
   cd wp-content/plugins/
   # Copier le dossier suivi-interventions/
   ```

2. **Activer le plugin**
   - Aller dans `WordPress Admin > Extensions`
   - Activer "Suivi des Interventions"

3. **Configuration initiale**
   - Le plugin crée automatiquement les post types et taxonomies
   - Ajouter des projets via `Interventions > Projets`
   - Créer des utilisateurs avec le rôle "Client"

## 📝 Guide d'utilisation

### Pour les Administrateurs

#### 1. Créer des projets
```
Interventions > Projets > Ajouter un nouveau projet
```
- **Nom du projet** : Identifiant principal
- **Quota** : Nombre maximum d'interventions
- **Date d'expiration** : Renouvellement du quota
- **URL du projet** : Lien vers le site
- **Informations client** : Notes et contacts

#### 2. Gérer les utilisateurs clients
```
Utilisateurs > Modifier un utilisateur (rôle Client)
```
- Assigner des **projets autorisés**
- Renseigner les **informations de contact**
- Le client ne verra que ses projets

#### 3. Ajouter des interventions
```
Interventions > Ajouter une intervention
```
- **Titre** : Description courte
- **Date d'intervention** : Date de réalisation
- **Projet** : Associer à un ou plusieurs projets
- **Statut** : Terminée (compte dans le quota) ou En cours
- **Description** : Détails complets

### Pour les Clients

#### Interface simplifiée
- Accès uniquement aux **interventions** de leurs projets
- **Mode lecture seule** (pas d'édition/suppression)
- **Filtres par date** disponibles
- **Dashboard personnalisé** avec leurs projets

#### Informations visibles
- Liste des interventions avec dates et statuts
- Progression des quotas par projet
- Projets autorisés affichés en haut de page

## 🎨 Interface utilisateur

### Barres de progression
Les quotas sont visualisés avec des barres colorées :
- **Animation fluide** lors du chargement
- **Tooltips informatifs** au survol
- **Mise à jour automatique** toutes les 30 secondes

### Filtres avancés
- **Plage de dates** : Filtrer par période d'intervention
- **Statut** : Terminées, En cours, Toutes
- **Projet** : Filtrer par projet spécifique
- **Recherche** : Recherche textuelle en temps réel

## 🔧 Architecture technique

### Structure des fichiers
```
suivi-interventions/
├── suivi-interventions.php          # Fichier principal
├── includes/                        # Classes principales
│   ├── class-suivi-interventions.php
│   ├── class-post-types.php
│   ├── class-taxonomies.php
│   ├── class-user-roles.php
│   ├── class-admin.php
│   └── class-client-restrictions.php
├── admin/                           # Interface admin
│   ├── css/admin-style.css
│   ├── js/admin-script.js
│   └── partials/
│       ├── intervention-meta-box.php
│       ├── projet-fields.php
│       └── client-projet-fields.php
├── uninstall.php                    # Script de désinstallation
└── README.md                        # Documentation
```

### Base de données
Le plugin utilise les tables WordPress existantes :
- **wp_posts** : Interventions
- **wp_terms** : Projets
- **wp_termmeta** : Métadonnées des projets
- **wp_postmeta** : Métadonnées des interventions
- **wp_usermeta** : Liaisons clients-projets

## 🔒 Sécurité

### Contrôles d'accès
- **Nonces** sur tous les formulaires
- **Capabilities** WordPress respectées
- **Sanitisation** de toutes les entrées utilisateur
- **Échappement** de toutes les sorties

### Restrictions clients
- **Filtrage automatique** des contenus par projet
- **Interface read-only** stricte
- **Vérifications multiples** des permissions
- **Redirection automatique** vers les interventions

## 🎯 API et hooks

### Hooks disponibles
```php
// Après création d'une intervention
do_action('si_intervention_created', $post_id);

// Avant suppression d'un projet
do_action('si_before_delete_projet', $term_id);

// Filtrer les projets visibles pour un client
apply_filters('si_client_visible_projets', $projets, $user_id);
```

### Fonctions utiles
```php
// Obtenir la progression d'un projet
SI_Taxonomies::get_projet_progression($term_id);

// Vérifier l'accès client à un projet
SI_User_Roles::client_has_projet_access($user_id, $projet_id);

// Obtenir les statistiques d'un client
SI_Client_Restrictions::get_client_stats($user_id);
```

## 📱 Responsive & Accessibilité

### Design adaptatif
- **Mobile-first** approach
- **Breakpoints** optimisés pour tablettes/mobiles
- **Interface tactile** friendly

### Accessibilité
- **Contraste** respectant WCAG 2.1
- **Navigation clavier** complète
- **Screen readers** compatibles
- **Aria labels** appropriés

## 🔄 Mise à jour et maintenance

### Désinstallation propre
Le fichier `uninstall.php` supprime :
- ✅ Tous les posts "intervention"
- ✅ Tous les termes "projet" 
- ✅ Métadonnées utilisateurs
- ✅ Rôle "Client"
- ✅ Capacités ajoutées
- ✅ Options du plugin

### Compatibilité
- **WordPress** : 5.0+
- **PHP** : 7.4+
- **MySQL** : 5.6+
- **Multisite** : Compatible

## 🤝 Support et contribution

### Problèmes courants
1. **Les barres de progression ne s'affichent pas**
   - Vérifier que les projets ont un quota défini
   - S'assurer que JavaScript est activé

2. **Les clients ne voient pas leurs projets**
   - Vérifier que des projets leur sont assignés
   - Contrôler les permissions du rôle

3. **Erreur 403 lors de l'édition**
   - Les clients n'ont que l'accès en lecture
   - Seuls les administrateurs peuvent modifier

### Logs de débogage
```php
// Activer le debug WordPress
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Les logs du plugin apparaîtront dans wp-content/debug.log
```

## 📄 Licence

Ce plugin est distribué sous licence GPL v2 ou ultérieure.

## 🏆 Crédits

Développé pour la gestion professionnelle des interventions web avec une approche moderne et sécurisée.

---

**Version :** 1.0.0  
**Testé jusqu'à :** WordPress 6.4  
**Nécessite PHP :** 7.4 ou supérieur