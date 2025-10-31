<?php
/**
 * Gestion de l'interface d'administration
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class SI_Admin {
    
    /**
     * Constructeur
     */
    public function __construct() {
        add_action('admin_init', array($this, 'init'), 3);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('admin_notices', array($this, 'admin_notices'));
    }
    
    /**
     * Initialisation de l'admin
     */
    public function init() {
        $this->setup_list_table_hooks();
        $this->setup_filters();
    }
    
    /**
     * Configuration des hooks pour les listes
     */
    private function setup_list_table_hooks() {
        // Colonnes personnalisées pour les interventions
        add_filter('manage_intervention_posts_columns', array($this, 'set_intervention_columns'));
        add_action('manage_intervention_posts_custom_column', array($this, 'fill_intervention_columns'), 10, 2);
        add_filter('manage_edit-intervention_sortable_columns', array($this, 'intervention_sortable_columns'));
        
        // Styles pour les listes
        add_action('admin_head', array($this, 'admin_styles'));
    }
    
    /**
     * Configuration des filtres
     */
    private function setup_filters() {
        add_action('restrict_manage_posts', array($this, 'add_intervention_filters'));
        add_filter('parse_query', array($this, 'filter_interventions_by_date'));
        add_filter('parse_query', array($this, 'filter_interventions_by_status'));
        add_filter('parse_query', array($this, 'filter_interventions_by_projet'));
    }
    
    /**
     * Définir les colonnes des interventions
     */
    public function set_intervention_columns($columns) {
        // Réorganiser les colonnes
        $new_columns = array();
        $new_columns['cb'] = $columns['cb'];
        $new_columns['title'] = $columns['title'];
        $new_columns['intervention_date'] = _e('Date d\'intervention', 'suivi-interventions');
        $new_columns['projet'] = _e('Projet', 'suivi-interventions');
        $new_columns['quota_progress'] = _e('Progression Quota', 'suivi-interventions');
        $new_columns['status'] = _e('Statut', 'suivi-interventions');
        $new_columns['date'] = $columns['date'];
        
        return $new_columns;
    }
    
    /**
     * Remplir les colonnes personnalisées
     */
    public function fill_intervention_columns($column, $post_id) {
        switch ($column) {
            case 'intervention_date':
                $this->display_intervention_date($post_id);
                break;
                
            case 'projet':
                $this->display_intervention_projet($post_id);
                break;
                
            case 'quota_progress':
                $this->display_quota_progress($post_id);
                break;
                
            case 'status':
                $this->display_intervention_status($post_id);
                break;
        }
    }
    
    /**
     * Afficher la date d'intervention
     */
    private function display_intervention_date($post_id) {
        $date = get_post_meta($post_id, '_date_intervention', true);
        if ($date) {
            $formatted_date = date_i18n(get_option('date_format'), strtotime($date));
            echo '<span class="intervention-date">' . esc_html($formatted_date) . '</span>';
        } else {
            echo '<span class="intervention-date-empty">' . __('Non définie', 'suivi-interventions') . '</span>';
        }
    }
    
    /**
     * Afficher le projet de l'intervention
     */
    private function display_intervention_projet($post_id) {
        $terms = get_the_terms($post_id, 'projet');
        if ($terms && !is_wp_error($terms)) {
            $projet_links = array();
            foreach ($terms as $term) {
                $edit_link = admin_url('term.php?taxonomy=projet&tag_ID=' . $term->term_id . '&post_type=intervention');
                $projet_links[] = '<a href="' . esc_url($edit_link) . '">' . esc_html($term->name) . '</a>';
            }
            echo implode(', ', $projet_links);
        } else {
            echo '<span class="no-projet">' . __('Aucun projet', 'suivi-interventions') . '</span>';
        }
    }
    
    /**
     * Afficher la progression du quota
     */
    private function display_quota_progress($post_id) {
        $terms = get_the_terms($post_id, 'projet');
        if ($terms && !is_wp_error($terms)) {
            foreach ($terms as $term) {
                $progression = SI_Taxonomies::get_projet_progression($term->term_id);
                $this->render_progress_bar($progression);
                break; // Afficher seulement le premier projet
            }
        } else {
            echo '<span class="no-quota">' . __('Pas de quota', 'suivi-interventions') . '</span>';
        }
    }
    
    /**
     * Afficher le statut de l'intervention
     */
    private function display_intervention_status($post_id) {
        $terminee = get_post_meta($post_id, '_intervention_terminee', true);
        if ($terminee == '1') {
            echo '<span class="status-completed">✓ ' . __('Terminée', 'suivi-interventions') . '</span>';
        } else {
            echo '<span class="status-pending">⏳ ' . __('En cours', 'suivi-interventions') . '</span>';
        }
    }
    
    /**
     * Rendu de la barre de progression
     */
    private function render_progress_bar($progression) {
        $percentage = $progression['percentage'];
        $color_class = $this->get_progress_color_class($percentage);
        
        echo '<div class="quota-progress-container">';
        echo '<div class="quota-progress-bar">';
        echo '<div class="quota-progress-fill ' . esc_attr($color_class) . '" style="width: ' . min(100, $percentage) . '%"></div>';
        echo '</div>';
        echo '<div class="quota-text">';
        /* translators: %1$d: remaining, %2$d: quota, %3$.1f: percentage with one decimal */ printf(
            __('%1$d/%2$d restant (%3$.1f%%)', 'suivi-interventions'),
            $progression['remaining'],
            $progression['quota'],
            $percentage
        );
        echo '</div>';
        echo '</div>';
    }
    
    /**
     * Obtenir la classe CSS pour la couleur de progression
     */
    private function get_progress_color_class($percentage) {
        if ($percentage > 80) {
            return 'quota-red';
        } elseif ($percentage > 45) {
            return 'quota-blue';
        }
        return 'quota-green';
    }
    
    /**
     * Colonnes triables
     */
    public function intervention_sortable_columns($columns) {
        $columns['intervention_date'] = 'intervention_date';
        $columns['status'] = 'status';
        return $columns;
    }
    
    /**
     * Ajouter les filtres dans la liste des interventions
     */
    public function add_intervention_filters() {
        global $typenow;
        
        if ($typenow !== 'intervention') {
            return;
        }
        
        $this->add_date_filters();
        $this->add_status_filter();
        $this->add_projet_filter();
    }
    
    /**
     * Ajouter les filtres de date
     */
    private function add_date_filters() {
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        echo '<input type="date" name="date_from" value="' . esc_attr($date_from) . '" placeholder="' . __('Date de début', 'suivi-interventions') . '" />';
        echo '<input type="date" name="date_to" value="' . esc_attr($date_to) . '" placeholder="' . __('Date de fin', 'suivi-interventions') . '" />';
    }
    
    /**
     * Ajouter le filtre de statut
     */
    private function add_status_filter() {
        $current_status = isset($_GET['intervention_status']) ? sanitize_text_field($_GET['intervention_status']) : '';
        
        echo '<select name="intervention_status">';
        echo '<option value="">' . __('Tous les statuts', 'suivi-interventions') . '</option>';
        echo '<option value="completed"' . selected($current_status, 'completed', false) . '>' . __('Terminées', 'suivi-interventions') . '</option>';
        echo '<option value="pending"' . selected($current_status, 'pending', false) . '>' . __('En cours', 'suivi-interventions') . '</option>';
        echo '</select>';
    }
    
    /**
     * Ajouter le filtre de projet
     */
    private function add_projet_filter() {
        $projets = get_terms(array(
            'taxonomy' => 'projet',
            'hide_empty' => false
        ));
        
        if (empty($projets)) {
            return;
        }
        
        $current_projet = isset($_GET['projet_filter']) ? sanitize_text_field($_GET['projet_filter']) : '';
        
        echo '<select name="projet_filter">';
        echo '<option value="">' . __('Tous les projets', 'suivi-interventions') . '</option>';
        
        foreach ($projets as $projet) {
            echo '<option value="' . esc_attr($projet->term_id) . '"' . selected($current_projet, $projet->term_id, false) . '>';
            echo esc_html($projet->name);
            echo '</option>';
        }
        
        echo '</select>';
    }
    
    /**
     * Filtrer par date
     */
    public function filter_interventions_by_date($query) {
        if (!$this->is_intervention_admin_page($query)) {
            return;
        }
        
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        
        if (!$date_from && !$date_to) {
            return;
        }
        
        $meta_query = $query->get('meta_query') ?: array();
        $meta_query['relation'] = 'AND';
        
        if ($date_from) {
            $meta_query[] = array(
                'key' => '_date_intervention',
                'value' => $date_from,
                'compare' => '>=',
                'type' => 'DATE'
            );
        }
        
        if ($date_to) {
            $meta_query[] = array(
                'key' => '_date_intervention',
                'value' => $date_to,
                'compare' => '<=',
                'type' => 'DATE'
            );
        }
        
        $query->set('meta_query', $meta_query);
    }
    
    /**
     * Filtrer par statut
     */
    public function filter_interventions_by_status($query) {
        if (!$this->is_intervention_admin_page($query)) {
            return;
        }
        
        $status = isset($_GET['intervention_status']) ? sanitize_text_field($_GET['intervention_status']) : '';
        
        if (!$status) {
            return;
        }
        
        $meta_query = $query->get('meta_query') ?: array();
        
        if ($status === 'completed') {
            $meta_query[] = array(
                'key' => '_intervention_terminee',
                'value' => '1',
                'compare' => '='
            );
        } elseif ($status === 'pending') {
            $meta_query[] = array(
                'relation' => 'OR',
                array(
                    'key' => '_intervention_terminee',
                    'value' => '0',
                    'compare' => '='
                ),
                array(
                    'key' => '_intervention_terminee',
                    'compare' => 'NOT EXISTS'
                )
            );
        }
        
        $query->set('meta_query', $meta_query);
    }
    
    /**
     * Filtrer par projet
     */
    public function filter_interventions_by_projet($query) {
        if (!$this->is_intervention_admin_page($query)) {
            return;
        }
        
        $projet_id = isset($_GET['projet_filter']) ? intval($_GET['projet_filter']) : 0;
        
        if (!$projet_id) {
            return;
        }
        
        $tax_query = $query->get('tax_query') ?: array();
        $tax_query[] = array(
            'taxonomy' => 'projet',
            'field' => 'term_id',
            'terms' => $projet_id
        );
        
        $query->set('tax_query', $tax_query);
    }
    
    /**
     * Vérifier si on est sur la page admin des interventions
     */
    private function is_intervention_admin_page($query) {
        global $pagenow;
        
        return is_admin() && 
               $query->is_main_query() && 
               $pagenow === 'edit.php' && 
               isset($_GET['post_type']) && 
               $_GET['post_type'] === 'intervention';
    }
    
    /**
     * Enqueue les scripts et styles admin
     */
    public function enqueue_scripts($hook) {
        // Scripts pour toutes les pages admin
        wp_enqueue_style(
            'si-admin-style',
            SUIVI_INTERVENTIONS_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            SUIVI_INTERVENTIONS_VERSION
        );
        
        // Scripts spécifiques aux interventions
        if ('edit.php' === $hook && isset($_GET['post_type']) && 'intervention' === $_GET['post_type']) {
            wp_enqueue_script(
                'si-admin-script',
                SUIVI_INTERVENTIONS_PLUGIN_URL . 'admin/js/admin-script.js',
                array('jquery'),
                SUIVI_INTERVENTIONS_VERSION,
                true
            );
        }
    }
    
    /**
     * Styles admin inline
     */
    public function admin_styles() {
        echo '<style>
            .quota-progress-container { width: 180px; }
            .quota-progress-bar { width: 100%; height: 20px; background-color: #f0f0f0; border-radius: 10px; overflow: hidden; margin-bottom: 5px; }
            .quota-progress-fill { height: 100%; transition: width 0.3s ease; }
            .quota-green { background-color: #4CAF50; }
            .quota-blue { background-color: #2196F3; }
            .quota-red { background-color: #f44336; }
            .quota-text { font-size: 11px; text-align: center; color: #666; }
            .status-completed { color: #46b450; font-weight: 600; }
            .status-pending { color: #ffb900; font-weight: 600; }
            .intervention-date-empty, .no-projet, .no-quota { color: #999; font-style: italic; }
        </style>';
    }
    
    /**
     * Notices admin
     */
    public function admin_notices() {
        // Afficher des notices si nécessaire
        if (isset($_GET['message']) && $_GET['message'] === 'project_updated') {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p>' . __('Projet mis à jour avec succès.', 'suivi-interventions') . '</p>';
            echo '</div>';
        }
    }
}