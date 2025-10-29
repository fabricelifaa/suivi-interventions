<?php
/**
 * Gestion des Taxonomies personnalisées
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class SI_Taxonomies {
    
    /**
     * Constructeur
     */
    public function __construct() {
        // Enregistrer immédiatement la taxonomie
        add_action('init', array($this, 'register_taxonomy'), 3);
        add_action('projet_add_form_fields', array($this, 'add_projet_fields'));
        add_action('projet_edit_form_fields', array($this, 'edit_projet_fields'));
        add_action('edited_projet', array($this, 'save_projet_fields'));
        add_action('create_projet', array($this, 'save_projet_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_taxonomy_scripts'));
    }
    
    /**
     * Enregistrer la taxonomie projet
     */
    public function register_taxonomy() {
        // Vérifier si la taxonomie n'existe pas déjà
        if (taxonomy_exists('projet')) {
            return;
        }
        
        $labels = array(
            'name'              => __('Projets', 'suivi-interventions'),
            'singular_name'     => __('Projet', 'suivi-interventions'),
            'search_items'      => __('Rechercher des projets', 'suivi-interventions'),
            'all_items'         => __('Tous les projets', 'suivi-interventions'),
            'parent_item'       => __('Projet parent', 'suivi-interventions'),
            'parent_item_colon' => __('Projet parent :', 'suivi-interventions'),
            'edit_item'         => __('Modifier le projet', 'suivi-interventions'),
            'update_item'       => __('Mettre à jour le projet', 'suivi-interventions'),
            'add_new_item'      => __('Ajouter un nouveau projet', 'suivi-interventions'),
            'new_item_name'     => __('Nouveau nom de projet', 'suivi-interventions'),
            'menu_name'         => __('Projets', 'suivi-interventions')
        );
        
        $args = array(
            'hierarchical'      => false,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => array('slug' => 'projet'),
            'show_in_rest'      => false,
            'capabilities'      => array(
                'manage_terms' => 'manage_options', // Simplifié
                'edit_terms'   => 'manage_options',
                'delete_terms' => 'manage_options',
                'assign_terms' => 'edit_posts'
            )
        );
        
        register_taxonomy('projet', array('intervention'), $args);
        
        // Log pour débogage
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Taxonomie "projet" enregistrée avec succès');
        }
    }
    
    /**
     * Ajouter les champs personnalisés lors de l'ajout d'un projet
     */
    public function add_projet_fields($taxonomy) {
        include SUIVI_INTERVENTIONS_PLUGIN_DIR . 'admin/partials/projet-fields.php';
    }
    
    /**
     * Ajouter les champs personnalisés lors de l'édition d'un projet
     */
    public function edit_projet_fields($term) {
        $quota = get_term_meta($term->term_id, 'quota', true);
        $date_expiration = get_term_meta($term->term_id, 'date_expiration', true);
        $client = get_term_meta($term->term_id, 'client_info', true);
        
        include SUIVI_INTERVENTIONS_PLUGIN_DIR . 'admin/partials/projet-fields.php';
    }
    
    /**
     * Sauvegarder les champs personnalisés du projet
     */
    public function save_projet_fields($term_id) {
        // Quota
        if (isset($_POST['quota'])) {
            update_term_meta($term_id, 'quota', absint($_POST['quota']));
        }
        
        // Date d'expiration
        if (isset($_POST['date_expiration'])) {
            update_term_meta(
                $term_id, 
                'date_expiration', 
                sanitize_text_field($_POST['date_expiration'])
            );
        }
        
        // Informations client
        if (isset($_POST['client_info'])) {
            update_term_meta(
                $term_id, 
                'client_info', 
                sanitize_textarea_field($_POST['client_info'])
            );
        }
        
        // URL du projet
        if (isset($_POST['projet_url'])) {
            update_term_meta(
                $term_id, 
                'projet_url', 
                esc_url_raw($_POST['projet_url'])
            );
        }
    }
    
    /**
     * Enqueue les scripts pour la taxonomie
     */
    public function enqueue_taxonomy_scripts($hook) {
        if ('edit-tags.php' === $hook && isset($_GET['taxonomy']) && 'projet' === $_GET['taxonomy']) {
            wp_enqueue_script('jquery');
        }
    }
    
    /**
     * Obtenir les meta données d'un projet
     */
    public static function get_projet_meta($term_id) {
        return array(
            'quota' => get_term_meta($term_id, 'quota', true),
            'date_expiration' => get_term_meta($term_id, 'date_expiration', true),
            'client_info' => get_term_meta($term_id, 'client_info', true),
            'projet_url' => get_term_meta($term_id, 'projet_url', true)
        );
    }
    
    /**
     * Calculer la progression d'un projet
     */
    public static function get_projet_progression($term_id) {
        $quota = get_term_meta($term_id, 'quota', true);
        if (!$quota) {
            return array(
                'quota' => 0,
                'used' => 0,
                'remaining' => 0,
                'percentage' => 0
            );
        }
        
        // Compter les interventions terminées pour ce projet
        $args = array(
            'post_type' => 'intervention',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'projet',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ),
            ),
            'meta_query' => array(
                array(
                    'key' => '_intervention_terminee',
                    'value' => '1',
                    'compare' => '='
                )
            )
        );
        
        $query = new WP_Query($args);
        $used = $query->found_posts;
        $remaining = max(0, $quota - $used);
        $percentage = $quota > 0 ? ($used / $quota) * 100 : 0;
        
        return array(
            'quota' => (int) $quota,
            'used' => $used,
            'remaining' => $remaining,
            'percentage' => round($percentage, 2)
        );
    }
}