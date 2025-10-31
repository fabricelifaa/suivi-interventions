<?php
/**
 * Script de désinstallation du plugin Suivi des Interventions
 * 
 * Ce fichier est exécuté quand le plugin est désinstallé via l'interface WordPress.
 * Il supprime toutes les données créées par le plugin.
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Vérifier que la désinstallation est légitime
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Supprimer toutes les données du plugin
 */
function suivi_interventions_uninstall() {
    global $wpdb;
    
    // === 1. SUPPRIMER TOUS LES POSTS "INTERVENTION" ===
    
    // Récupérer tous les posts d'intervention
    $interventions = get_posts(array(
        'post_type' => 'intervention',
        'numberposts' => -1,
        'post_status' => 'any',
        'fields' => 'ids'
    ));
    
    // Supprimer chaque intervention et ses métadonnées
    foreach ($interventions as $intervention_id) {
        // Sécuriser l'ID et vérifier avant suppression
        $intervention_id = (int) $intervention_id;
        if ($intervention_id <= 0) {
            // ID invalide : ignorer
            continue;
        }

        // Supprimer toutes les métadonnées de ce post (format sécurisé)
        $wpdb->delete(
            $wpdb->postmeta,
            array('post_id' => $intervention_id),
            array('%d')
        );

        // Supprimer le post lui-même (force true pour suppression définitive)
        wp_delete_post($intervention_id, true);
    }
    
    // === 2. SUPPRIMER LA TAXONOMIE "PROJET" ET SES TERMES ===
    
    // Récupérer tous les termes de la taxonomie projet
    $projets = get_terms(array(
        'taxonomy' => 'projet',
        'hide_empty' => false,
        'fields' => 'ids'
    ));
    
    if (!is_wp_error($projets) && !empty($projets)) {
        foreach ($projets as $projet_id) {
            // Supprimer les métadonnées du terme
            $wpdb->delete(
                $wpdb->termmeta,
                array('term_id' => $projet_id),
                array('%d')
            );
            
            // Supprimer le terme
            wp_delete_term($projet_id, 'projet');
        }
    }
    
    // Supprimer les relations terme-post dans wp_term_relationships
    $wpdb->query($wpdb->prepare("
        DELETE tr FROM {$wpdb->term_relationships} tr
        INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
        WHERE tt.taxonomy = 'projet'
    "));
    
    // Supprimer les entrées dans wp_term_taxonomy
    $wpdb->delete(
        $wpdb->term_taxonomy,
        array('taxonomy' => 'projet'),
        array('%s')
    );
    
    // === 3. SUPPRIMER LES MÉTADONNÉES UTILISATEURS ===
    
    $user_meta_keys = array(
        'client_projets',
        'client_company', 
        'client_contact_phone'
    );
    
    foreach ($user_meta_keys as $meta_key) {
        $wpdb->delete(
            $wpdb->usermeta,
            array('meta_key' => $meta_key),
            array('%s')
        );
    }
    
    // === 4. SUPPRIMER LE RÔLE UTILISATEUR "BSDCLIENT" ===
    
    // Obtenir tous les utilisateurs avec le rôle bsdclient
    $client_users = get_users(array(
        'role' => 'bsdclient',
        'fields' => 'ID'
    ));
    
    // Changer leur rôle vers subscriber avant de supprimer le rôle
    foreach ($client_users as $user_id) {
        $user = get_user_by('ID', $user_id);
        if ($user) {
            $user->set_role('subscriber');
        }
    }
    
    // Supprimer le rôle personnalisé
    remove_role('bsdclient');
    
    // === 5. SUPPRIMER LES CAPACITÉS AJOUTÉES ===
    
    $admin_role = get_role('administrator');
    if ($admin_role) {
        $capabilities_to_remove = array(
            'edit_intervention',
            'read_intervention', 
            'delete_intervention',
            'edit_interventions',
            'edit_others_interventions',
            'publish_interventions',
            'read_private_interventions',
            'delete_interventions',
            'delete_private_interventions',
            'delete_published_interventions',
            'delete_others_interventions',
            'edit_private_interventions',
            'edit_published_interventions'
        );
        
        foreach ($capabilities_to_remove as $cap) {
            $admin_role->remove_cap($cap);
        }
    }
    
    // Faire de même pour les autres rôles si nécessaire
    $editor_role = get_role('editor');
    if ($editor_role) {
        $editor_caps = array(
            'edit_intervention',
            'read_intervention',
            'delete_intervention',
            'edit_interventions',
            'publish_interventions',
            'delete_interventions'
        );
        
        foreach ($editor_caps as $cap) {
            $editor_role->remove_cap($cap);
        }
    }
    
    // === 6. SUPPRIMER LES OPTIONS DU PLUGIN ===
    
    $options_to_delete = array(
        'suivi_interventions_version',
        'suivi_interventions_settings',
        'suivi_interventions_db_version',
        'suivi_interventions_activation_time',
        'suivi_interventions_first_activation',
        'rewrite_rules' // Forcer la régénération des règles de réécriture
    );
    
    foreach ($options_to_delete as $option) {
        delete_option($option);
        
        // Supprimer aussi des options du réseau si multisite
        if (is_multisite()) {
            delete_site_option($option);
        }
    }
    
    // === 7. NETTOYER LES MÉTADONNÉES ORPHELINES ===
    
    // Supprimer les meta de posts orphelins
    $wpdb->query($wpdb->prepare("
        DELETE pm FROM {$wpdb->postmeta} pm
        LEFT JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE p.ID IS NULL
    "));
    
    // Supprimer les meta de termes orphelins  
    $wpdb->query($wpdb->prepare("
        DELETE tm FROM {$wpdb->termmeta} tm
        LEFT JOIN {$wpdb->terms} t ON tm.term_id = t.term_id
        WHERE t.term_id IS NULL
    "));
    
    // Supprimer les meta d'utilisateurs orphelins
    $wpdb->query(("
        DELETE um FROM {$wpdb->usermeta} um
        LEFT JOIN {$wpdb->users} u ON um.user_id = u.ID
        WHERE u.ID IS NULL
    "));
    
    // === 8. NETTOYER LES TABLES DE RELATIONS ===
    
    // Supprimer les relations terme-post orphelines
    $wpdb->query($wpdb->prepare("
        DELETE tr FROM {$wpdb->term_relationships} tr
        LEFT JOIN {$wpdb->posts} p ON tr.object_id = p.ID
        WHERE p.ID IS NULL
    "));
    
    // Supprimer les taxonomies orphelines
    $wpdb->query($wpdb->prepare("
        DELETE tt FROM {$wpdb->term_taxonomy} tt
        LEFT JOIN {$wpdb->terms} t ON tt.term_id = t.term_id
        WHERE t.term_id IS NULL
    "));
    
    // === 9. NETTOYER LE CACHE ET LES TRANSIENTS ===
    
    // Supprimer les transients du plugin
    $wpdb->query($wpdb->prepare("
        DELETE FROM {$wpdb->options} 
        WHERE option_name LIKE '_transient_si_%' 
        OR option_name LIKE '_transient_timeout_si_%'
        OR option_name LIKE '_site_transient_si_%'
        OR option_name LIKE '_site_transient_timeout_si_%'
    "));
    
    // Vider les caches d'objet si disponibles
    if (function_exists('wp_cache_flush')) {
        wp_cache_flush();
    }
    
    // === 10. NETTOYER LES FICHIERS TEMPORAIRES (SI EXISTANTS) ===
    
    $upload_dir = wp_upload_dir();
    $plugin_upload_dir = $upload_dir['basedir'] . '/suivi-interventions/';
    
    if (is_dir($plugin_upload_dir)) {
        suivi_interventions_remove_directory($plugin_upload_dir);
    }
    
    // === 11. FLUSH REWRITE RULES ===
    
    // Supprimer les règles de réécriture du plugin
    flush_rewrite_rules();
    
    // === 12. NETTOYER LES LOGS ET ERREURS ===
    
    // Supprimer les logs d'erreur spécifiques au plugin
    $log_file = WP_CONTENT_DIR . '/debug.log';
    if (file_exists($log_file) && is_writable($log_file)) {
        $log_content = file_get_contents($log_file);
        $cleaned_log = preg_replace('/.*suivi.interventions.*\n/', '', $log_content);
        file_put_contents($log_file, $cleaned_log);
    }
    
    // === 13. STATISTIQUES DE DÉSINSTALLATION ===
    
    $uninstall_stats = array(
        'interventions_deleted' => count($interventions),
        'projets_deleted' => is_array($projets) ? count($projets) : 0,
        'users_updated' => count($client_users),
        'uninstall_date' => current_time('mysql'),
        'wp_version' => get_bloginfo('version'),
        'plugin_version' => '1.0.0'
    );
    
    // Log final de désinstallation
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(
            'Plugin Suivi des Interventions: Désinstallation complète - ' . 
            json_encode($uninstall_stats)
        );
    }
    
    // === 14. OPTIMISATION FINALE DE LA BASE DE DONNÉES ===
    
    // Optimiser les tables touchées
    $tables_to_optimize = array(
        $wpdb->posts,
        $wpdb->postmeta,
        $wpdb->terms,
        $wpdb->term_taxonomy,
        $wpdb->term_relationships,
        $wpdb->termmeta,
        $wpdb->usermeta,
        $wpdb->options
    );
    
    foreach ($tables_to_optimize as $table) {
        $wpdb->query($wpdb->prepare("OPTIMIZE TABLE {$table}"));
    }
}

/**
 * Fonction utilitaire pour supprimer un dossier récursivement
 */
function suivi_interventions_remove_directory($dir) {
    if (!is_dir($dir)) {
        return false;
    }
    
    $files = array_diff(scandir($dir), array('.', '..'));
    
    foreach ($files as $file) {
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        
        if (is_dir($path)) {
            suivi_interventions_remove_directory($path);
        } else {
            unlink($path);
        }
    }
    
    return rmdir($dir);
}

/**
 * Fonction de confirmation et vérification avant désinstallation
 */
function suivi_interventions_confirm_uninstall() {
    // Vérifier les permissions
    if (!current_user_can('activate_plugins')) {
        return false;
    }
    
    // Vérifier que nous sommes bien dans le contexte de désinstallation WordPress
    if (!defined('WP_UNINSTALL_PLUGIN')) {
        return false;
    }
    
    // Vérifier que le fichier du plugin existe encore
    $plugin_file = WP_PLUGIN_DIR . '/suivi-interventions/suivi-interventions.php';
    if (!file_exists($plugin_file)) {
        return false;
    }
    
    // Optionnel : Ajouter d'autres vérifications de sécurité
    
    return true;
}

/**
 * Fonction de sauvegarde des données avant suppression (optionnelle)
 */
function suivi_interventions_backup_data() {
    global $wpdb;
    
    $backup_data = array(
        'interventions' => array(),
        'projets' => array(),
        'client_associations' => array(),
        'export_date' => current_time('mysql')
    );
    
    // Sauvegarder les interventions
    $interventions = $wpdb->get_results($wpdb->prepare("
        SELECT p.*, pm.meta_key, pm.meta_value 
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
        WHERE p.post_type = 'intervention'
    ", ARRAY_A));
    
    $backup_data['interventions'] = $interventions;
    
    // Sauvegarder les projets
    $projets = $wpdb->get_results($wpdb->prepare("
        SELECT t.*, tt.*, tm.meta_key, tm.meta_value
        FROM {$wpdb->terms} t
        INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
        LEFT JOIN {$wpdb->termmeta} tm ON t.term_id = tm.term_id
        WHERE tt.taxonomy = 'projet'
    ", ARRAY_A));
    
    $backup_data['projets'] = $projets;
    
    // Sauvegarder les associations clients
    $client_data = $wpdb->get_results($wpdb->prepare("
        SELECT user_id, meta_key, meta_value
        FROM {$wpdb->usermeta}
        WHERE meta_key IN ('client_projets', 'client_company', 'client_contact_phone')
    ", ARRAY_A));
    
    $backup_data['client_associations'] = $client_data;
    
    // Sauvegarder dans un fichier JSON
    $upload_dir = wp_upload_dir();
    $backup_file = $upload_dir['basedir'] . '/suivi-interventions-backup-' . date('Y-m-d-H-i-s') . '.json';
    
    file_put_contents($backup_file, json_encode($backup_data, JSON_PRETTY_PRINT));
    
    return $backup_file;
}

// === EXÉCUTION DE LA DÉSINSTALLATION ===

// Vérifier et exécuter la désinstallation
if (suivi_interventions_confirm_uninstall()) {
    
    // Optionnel : Créer une sauvegarde avant suppression
    if (defined('SUIVI_INTERVENTIONS_BACKUP_ON_UNINSTALL') && SUIVI_INTERVENTIONS_BACKUP_ON_UNINSTALL) {
        $backup_file = suivi_interventions_backup_data();
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Suivi Interventions: Sauvegarde créée avant désinstallation: ' . $backup_file);
        }
    }
    
    // Exécuter la désinstallation complète
    suivi_interventions_uninstall();
    
    // Log final de confirmation
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Plugin Suivi des Interventions: Désinstallation terminée avec succès le ' . date('Y-m-d H:i:s'));
    }
    
} else {
    // Log d'erreur si la désinstallation ne peut pas s'exécuter
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('Plugin Suivi des Interventions: Échec de la vérification de désinstallation');
    }
}
    
    // Supprimer tous les posts du type 'intervention'
    $interventions = get_posts(array(
        'post_type' => 'intervention',
        'numberposts' => -1,
        'post_status' => 'any'
    ));
    
    foreach ($interventions as $intervention) {
        wp_delete_post($intervention->ID); // Peut laisser des traces
    }   