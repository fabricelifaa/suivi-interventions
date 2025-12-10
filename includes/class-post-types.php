<?php
/**
 * Gestion des Post Types personnalisés
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class SUIVDEIN_Post_Types {
    
    /**
     * Constructeur
     */
    public function __construct() {
        // Enregistrer immédiatement le post type
        add_action('init', array($this, 'suivdein_register_post_type'), 3);
        add_action('add_meta_boxes', array($this, 'suivdein_add_meta_boxes'));
        add_action('save_post', array($this, 'suivdein_save_meta_boxes'));
    }
    
    /**
     * Enregistrer le post type intervention
     */
    public function suivdein_register_post_type() {
        // Vérifier si le post type n'existe pas déjà
        if (post_type_exists('intervention')) {
            return;
        }
        
        $labels = array(
            'name'                  => __('Interventions', 'suivi-des-interventions'),
            'singular_name'         => __('Intervention', 'suivi-des-interventions'),
            'menu_name'            => __('Interventions', 'suivi-des-interventions'),
            'name_admin_bar'       => __('Intervention', 'suivi-des-interventions'),
            'add_new'              => __('Ajouter une intervention', 'suivi-des-interventions'),
            'add_new_item'         => __('Ajouter une nouvelle intervention', 'suivi-des-interventions'),
            'new_item'             => __('Nouvelle intervention', 'suivi-des-interventions'),
            'edit_item'            => __('Modifier l\'intervention', 'suivi-des-interventions'),
            'view_item'            => __('Voir l\'intervention', 'suivi-des-interventions'),
            'all_items'            => __('Toutes les interventions', 'suivi-des-interventions'),
            'search_items'         => __('Rechercher des interventions', 'suivi-des-interventions'),
            'parent_item_colon'    => __('Intervention parent :', 'suivi-des-interventions'),
            'not_found'            => __('Aucune intervention trouvée.', 'suivi-des-interventions'),
            'not_found_in_trash'   => __('Aucune intervention trouvée dans la corbeille.', 'suivi-des-interventions')
        );
        
        $args = array(
            'labels'             => $labels,
            'public'             => false,
            'publicly_queryable' => false,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array('slug' => 'maj'),
            'capability_type'    => 'post',
            'capabilities'       => array(
                'edit_post'          => 'read',
                'read_post'          => 'read',
                'delete_post'        => 'edit_posts',
                'edit_posts'         => 'read',
                'edit_others_posts'  => 'edit_posts',
                'publish_posts'      => 'edit_posts',
                'read_private_posts' => 'read',
            ),
            'map_meta_cap'       => true,
            'has_archive'        => false,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-clipboard',
            'supports'           => array('title', 'editor'),
            'show_in_rest'       => false
        );
        
        register_post_type('intervention', $args);
        
        // Log pour débogage
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Post type "intervention" enregistré avec succès - capabilities: read pour tous');
        }
    }
    
    /**
     * Ajouter les meta boxes
     */
    public function suivdein_add_meta_boxes() {
        add_meta_box(
            'intervention_details',
            __('Détails de l\'intervention', 'suivi-des-interventions'),
            array($this, 'render_meta_box'),
            'intervention',
            'normal',
            'high'
        );
    }
    
    /**
     * Rendu de la meta box
     */
    public function render_meta_box($post) {
        // Inclure le template
        include SUIVDEIN_PLUGIN_DIR . 'admin/partials/intervention-meta-box.php';
    }
    
    /**
     * Sauvegarder les meta boxes
     */
    public function suivdein_save_meta_boxes($post_id) {
        // Vérifications de sécurité
        if (!isset($_POST['intervention_meta_nonce'])) {
            return;
        }
        
        if (!wp_verify_nonce($_POST['intervention_meta_nonce'], 'intervention_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_intervention', $post_id)) {
            return;
        }
        
        // Sauvegarder les champs
        $this->save_intervention_fields($post_id);
    }
    
    /**
     * Sauvegarder les champs de l'intervention
     */
    private function save_intervention_fields($post_id) {
        // Date d'intervention
        if (isset($_POST['date_intervention'])) {
            update_post_meta(
                $post_id, 
                '_date_intervention', 
                sanitize_text_field($_POST['date_intervention'])
            );
        }
        
        // Intervention terminée
        $intervention_terminee = isset($_POST['intervention_terminee']) ? '1' : '0';
        update_post_meta($post_id, '_intervention_terminee', $intervention_terminee);
        
        // Description supplémentaire
        if (isset($_POST['intervention_description'])) {
            update_post_meta(
                $post_id, 
                '_intervention_description', 
                wp_kses_post($_POST['intervention_description'])
            );
        }
    }
    
    /**
     * Obtenir les meta données d'une intervention
     */
    public static function get_intervention_meta($post_id) {
        return array(
            'date_intervention' => get_post_meta($post_id, '_date_intervention', true),
            'intervention_terminee' => get_post_meta($post_id, '_intervention_terminee', true),
            'description' => get_post_meta($post_id, '_intervention_description', true)
        );
    }
}