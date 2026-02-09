<?php
/**
 * Fonctions utilitaires et helpers pour le plugin Suivi des Interventions
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Obtenir toutes les interventions d'un projet avec leurs détails
 * 
 * @param int $projet_id ID du projet
 * @param array $args Arguments supplémentaires pour la requête
 * @return array Liste des interventions avec détails
 */
function suivdein_get_interventions_by_projet($projet_id, $args = array()) {
    $default_args = array(
        'post_type' => 'suivdein_post',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'meta_key' => '_date_intervention',
        'orderby' => 'meta_value',
        'order' => 'DESC',
        'tax_query' => array(
            array(
                'taxonomy' => 'suivdein_projet',
                'field' => 'term_id',
                'terms' => $projet_id
            )
        )
    );
    
    $args = wp_parse_args($args, $default_args);
    $interventions = get_posts($args);
    
    $result = array();
    foreach ($interventions as $intervention) {
        $meta = SUIVDEIN_Post_Types::get_intervention_meta($intervention->ID);
        $result[] = array(
            'ID' => $intervention->ID,
            'title' => $intervention->post_title,
            'content' => $intervention->post_content,
            'date_created' => $intervention->post_date,
            'date_intervention' => $meta['date_intervention'],
            'terminee' => $meta['intervention_terminee'] == '1',
            'description' => $meta['description'],
            'author' => get_the_author_meta('display_name', $intervention->post_author)
        );
    }
    
    return $result;
}

/**
 * Calculer les statistiques globales du plugin
 * 
 * @return array Statistiques complètes
 */
function suivdein_get_global_stats() {
    global $wpdb;
    
    // Compter les interventions
    $total_interventions = wp_count_posts('suivdein_post')->publish;
    
    // Compter les interventions terminées
    // Requête sécurisée : utiliser $wpdb->prepare et COUNT(DISTINCT p.ID)
    $completed_interventions = (int) $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE pm.meta_key = %s
             AND pm.meta_value = %s
             AND p.post_type = %s
             AND p.post_status = %s",
             '_intervention_terminee',
             '1',
             'suivdein_post',
             'publish'
        )
    );
    
    // Compter les projets
    $total_projets = wp_count_terms('suivdein_projet');
    
    // Compter les clients
    $total_clients = count(get_users(array('role' => 'bsdclient')));
    
    // Projet le plus actif
    $most_active_projet = $wpdb->get_row(
        $wpdb->prepare("SELECT t.term_id, t.name, COUNT(tr.object_id) as intervention_count
         FROM {$wpdb->terms} t
         INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
         INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
         INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
         WHERE tt.taxonomy = 'suivdein_projet' 
         AND p.post_type = 'suivdein_post' 
         AND p.post_status = 'publish'
         GROUP BY t.term_id, t.name
         ORDER BY intervention_count DESC
         LIMIT 1"
    ));
    
    return array(
        'total_interventions' => (int) $total_interventions,
        'completed_interventions' => (int) $completed_interventions,
        'pending_interventions' => (int) ($total_interventions - $completed_interventions),
        'total_projets' => (int) $total_projets,
        'total_clients' => (int) $total_clients,
        'completion_rate' => $total_interventions > 0 ? round(($completed_interventions / $total_interventions) * 100, 1) : 0,
        'most_active_projet' => $most_active_projet ? array(
            'id' => $most_active_projet->term_id,
            'name' => $most_active_projet->name,
            'count' => $most_active_projet->intervention_count
        ) : null
    );
}

/**
 * Vérifier si un quota de projet est dépassé
 * 
 * @param int $projet_id ID du projet
 * @return array Informations sur le dépassement
 */
function suivdein_check_quota_exceeded($projet_id) {
    $progression = SUIVDEIN_Taxonomies::get_projet_progression($projet_id);
    
    return array(
        'exceeded' => $progression['percentage'] > 100,
        'at_limit' => $progression['percentage'] >= 100,
        'warning' => $progression['percentage'] > 80,
        'percentage' => $progression['percentage'],
        'used' => $progression['used'],
        'quota' => $progression['quota'],
        'remaining' => $progression['remaining']
    );
}

/**
 * Obtenir les projets en approche de la limite de quota
 * 
 * @param int $threshold Seuil de pourcentage (défaut: 80%)
 * @return array Projets proches de la limite
 */
function suivdein_get_projects_near_quota_limit($threshold = 80) {
    $projets = get_terms(array(
        'taxonomy' => 'suivdein_projet',
        'hide_empty' => false
    ));
    
    $projects_near_limit = array();
    
    foreach ($projets as $projet) {
        $quota = get_term_meta($projet->term_id, 'quota', true);
        if (!$quota) continue;
        
        $progression = SUIVDEIN_Taxonomies::get_projet_progression($projet->term_id);
        
        if ($progression['percentage'] >= $threshold) {
            $projects_near_limit[] = array(
                'id' => $projet->term_id,
                'name' => $projet->name,
                'progression' => $progression,
                'status' => $progression['percentage'] >= 100 ? 'exceeded' : 'warning'
            );
        }
    }
    
    // Trier par pourcentage décroissant
    usort($projects_near_limit, function($a, $b) {
        return $b['progression']['percentage'] <=> $a['progression']['percentage'];
    });
    
    return $projects_near_limit;
}

/**
 * Formater une date selon les préférences WordPress
 * 
 * @param string $date Date à formater
 * @param string $format Format personnalisé (optionnel)
 * @return string Date formatée
 */
function suivdein_format_date($date, $format = null) {
    if (!$date) return '';
    
    $format = $format ?: get_option('date_format');
    return date_i18n($format, strtotime($date));
}

/**
 * Obtenir la couleur CSS pour une barre de progression selon le pourcentage
 * 
 * @param float $percentage Pourcentage
 * @return string Classe CSS
 */
function suivdein_get_progress_color_class($percentage) {
    if ($percentage > 80) {
        return 'quota-red';
    } elseif ($percentage > 45) {
        return 'quota-blue';
    }
    return 'quota-green';
}

/**
 * Vérifier si un utilisateur peut gérer les interventions d'un projet
 * 
 * @param int $user_id ID de l'utilisateur
 * @param int $projet_id ID du projet
 * @return bool
 */
function suivdein_user_can_manage_projet_interventions($user_id, $projet_id) {
    // Les administrateurs peuvent tout gérer
    if (user_can($user_id, 'manage_options')) {
        return true;
    }
    
    // Les clients ne peuvent que voir (pas gérer)
    if (user_can($user_id, 'bsdclient')) {
        return false;
    }
    
    // Pour les autres rôles, vérifier les capacités standards
    return user_can($user_id, 'edit_interventions');
}

/**
 * Obtenir le nom d'affichage d'un client avec ses projets
 * 
 * @param int $user_id ID du client
 * @return string Nom formaté avec projets
 */
function suivdein_get_client_display_name($user_id) {
    $user = get_user_by('ID', $user_id);
    if (!$user) return '';
    
    $client_projets = SUIVDEIN_User_Roles::get_client_projets($user_id);
    $projet_names = array();
    
    foreach ($client_projets as $projet_id) {
        $term = get_term($projet_id, 'suivdein_projet');
        if ($term && !is_wp_error($term)) {
            $projet_names[] = $term->name;
        }
    }
    
    $display_name = $user->display_name;
    if (!empty($projet_names)) {
        $display_name .= ' (' . implode(', ', $projet_names) . ')';
    }
    
    return $display_name;
}

/**
 * Créer un utilisateur client avec projets assignés
 * 
 * @param array $user_data Données utilisateur
 * @param array $project_ids IDs des projets à assigner
 * @return int|WP_Error ID de l'utilisateur créé ou erreur
 */
function suivdein_create_client_user($user_data, $project_ids = array()) {
    // Données par défaut
    $default_data = array(
        'role' => 'bsdclient',
        'user_pass' => wp_generate_password(),
        'show_admin_bar_front' => false
    );
    
    $user_data = wp_parse_args($user_data, $default_data);
    
    // Créer l'utilisateur
    $user_id = wp_insert_user($user_data);
    
    if (is_wp_error($user_id)) {
        return $user_id;
    }
    
    // Assigner les projets
    if (!empty($project_ids)) {
        update_user_meta($user_id, 'client_projets', array_map('intval', $project_ids));
    }
    
    // Envoyer l'email de notification (optionnel)
    wp_send_new_user_notifications($user_id, 'both');
    
    return $user_id;
}

/**
 * Obtenir les interventions récentes d'un client
 * 
 * @param int $user_id ID du client
 * @param int $limit Nombre d'interventions à retourner
 * @return array Liste des interventions récentes
 */
function suivdein_get_client_recent_interventions($user_id, $limit = 10) {
    $client_projets = SUIVDEIN_User_Roles::get_client_projets($user_id);
    
    if (empty($client_projets)) {
        return array();
    }
    
    $args = array(
        'post_type' => 'suivdein_post',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'tax_query' => array(
            array(
                'taxonomy' => 'suivdein_projet',
                'field' => 'term_id',
                'terms' => $client_projets,
                'operator' => 'IN'
            )
        )
    );
    
    return get_posts($args);
}

/**
 * Vérifier si un projet a atteint sa limite de quota
 * 
 * @param int $projet_id ID du projet
 * @return array Informations sur le statut du quota
 */
function suivdein_is_projet_quota_exceeded($projet_id) {
    return suivdein_check_quota_exceeded($projet_id);
}


/**
 * Logger des événements du plugin (si WP_DEBUG activé)
 * 
 * @param string $message Message à logger
 * @param string $level Niveau de log (info, warning, error)
 */
function suivdein_log($message, $level = 'info') {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        $formatted_message = '[Suivi Interventions] [' . strtoupper($level) . '] ' . $message;
        error_log($formatted_message);
    }
}

/**
 * Obtenir l'URL de la page de gestion des clients
 * 
 * @return string URL de la page
 */
function suivdein_get_client_management_url() {
    return admin_url('edit.php?post_type=intervention&page=si-client-management');
}

/**
 * Générer un rapport PDF des interventions d'un projet (placeholder)
 * 
 * @param int $projet_id ID du projet
 * @return string|false Chemin vers le fichier PDF ou false
 */
function suivdein_generate_projet_report($projet_id) {
    // Cette fonction pourrait être implémentée pour générer des rapports PDF
    // Pour l'instant, c'est un placeholder
    
    suivdein_log("Génération de rapport demandée pour le projet {$projet_id}", 'info');
    
    // TODO: Implémenter la génération de rapport PDF
    return false;
}

/**
 * Nettoyer les données du plugin lors de la désinstallation
 * 
 * @return bool Succès de l'opération
 */
function suivdein_cleanup_plugin_data() {
    // Cette fonction est utilisée par uninstall.php
    return true;
}

/**
 * Vérifier la compatibilité du plugin avec la version WordPress
 * 
 * @return bool|string True si compatible, message d'erreur sinon
 */
function suivdein_check_wordpress_compatibility() {
    global $wp_version;
    
    $required_wp_version = '6.6';
    
    if (version_compare($wp_version, $required_wp_version, '<')) {
        return sprintf(
            esc_attr('Le plugin Suivi des Interventions nécessite WordPress %1s ou supérieur. Version actuelle: %2s', 'suivi-des-interventions'),
            $required_wp_version,
            $wp_version
        );
    }
    
    return true;
}