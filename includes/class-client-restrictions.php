<?php
/**
 * Gestion des restrictions pour les clients
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class SUIVDEIN_Client_Restrictions {
    
    /**
     * Constructeur
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Initialisation
     */
    public function init() {
        if ($this->is_client_user()) {
            $this->setup_client_restrictions();
        }
    }
    
    /**
     * Vérifier si l'utilisateur actuel est un client
     */
    private function is_client_user() {
        if (!is_user_logged_in()) {
            return false;
        }
        
        $user = wp_get_current_user();
        
        // Vérifier le rôle de manière robuste
        if (!$user || !isset($user->roles)) {
            return false;
        }
        
        $is_client = in_array('bsdclient', (array) $user->roles);
        $is_admin = in_array('administrator', (array) $user->roles);
        
        // Log pour débogage
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Client check - User ID: ' . $user->ID . ', Is Client: ' . ($is_client ? 'YES' : 'NO') . ', Is Admin: ' . ($is_admin ? 'YES' : 'NO'));
        }
        
        // Un client ne doit PAS être administrateur
        return $is_client && !$is_admin;
    }
    
    /**
     * Configuration des restrictions pour les clients
     */
    private function setup_client_restrictions() {
        // Restrictions de menu - Masquer tous les menus WordPress natifs
        add_action('admin_menu', array($this, 'restrict_admin_menu'), 9999);
        
        // Restrictions de contenu - Filtrer les interventions
        add_action('pre_get_posts', array($this, 'filter_posts_for_clients'));
        
        // Redirection automatique
        add_action('admin_init', array($this, 'redirect_to_client_dashboard'), 1);
        
        // Bloquer l'accès aux pages non autorisées
        add_action('admin_init', array($this, 'block_unauthorized_pages'), 1);
        
        // Masquer la barre admin en front-end
        add_filter('show_admin_bar', '__return_false');
        
        // Supprimer les liens de la barre admin
        add_action('admin_bar_menu', array($this, 'remove_admin_bar_links'), 999);
        
        // Restrictions de capacités
        add_filter('user_has_cap', array($this, 'restrict_client_capabilities'), 10, 3);
    }
    
    /**
     * Restreindre le menu admin pour les clients
     */
    public function restrict_admin_menu() {
        // Liste de TOUS les menus WordPress natifs à supprimer
        $menus_to_remove = array(
            'index.php',                    // Dashboard
            'separator1',
            'edit.php',                     // Posts
            'upload.php',                   // Media
            'edit.php?post_type=page',      // Pages
            'edit-comments.php',            // Comments  
            'separator2',
            'themes.php',                   // Appearance
            'plugins.php',                  // Plugins
            'users.php',                    // Users
            'tools.php',                    // Tools
            'options-general.php',          // Settings
            'separator-last',
            'edit.php?post_type=intervention', // Post type intervention natif
        );
        
        foreach ($menus_to_remove as $menu) {
            remove_menu_page($menu);
        }
        
        // Supprimer tous les sous-menus possibles
        remove_submenu_page('edit.php?post_type=intervention', 'post-new.php?post_type=intervention');
        remove_submenu_page('edit.php?post_type=intervention', 'edit-tags.php?taxonomy=projet&post_type=intervention');
        remove_submenu_page('edit.php?post_type=intervention', 'si-client-management');
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Menus restreints pour le client ID: ' . get_current_user_id());
        }
    }
    
    /**
     * Rediriger vers le dashboard client
     */
    public function redirect_to_client_dashboard() {
        global $pagenow;
        
        // Rediriger depuis le dashboard WordPress natif
        if ($pagenow === 'index.php') {
            wp_safe_redirect(admin_url('admin.php?page=client-interventions'));
            exit;
        }
    }
    
    /**
     * Bloquer l'accès aux pages non autorisées
     */
    public function block_unauthorized_pages() {
        global $pagenow;
        
        // Pages autorisées pour les clients
        $allowed_pages = array(
            'admin.php',          // Page custom (client-interventions)
            'profile.php',        // Profil
            'admin-ajax.php',     // AJAX
            'admin-post.php'      // Actions POST
        );
        
        // Vérifier si on est sur une page admin.php autorisée
        if ($pagenow === 'admin.php') {
            $page = isset($_GET['page']) ? $_GET['page'] : '';
            if ($page !== 'client-interventions') {
                wp_safe_redirect(admin_url('admin.php?page=client-interventions'));
                exit;
            }
            return; // Page autorisée
        }
        
        // Si c'est une page autorisée, laisser passer
        if (in_array($pagenow, $allowed_pages)) {
            return;
        }
        
        // Bloquer toutes les autres pages et rediriger
        wp_safe_redirect(admin_url('admin.php?page=client-interventions'));
        exit;
    }
    
    /**
     * Filtrer les posts pour les clients
     */
    public function filter_posts_for_clients($query) {
        if (!is_admin() || !$query->is_main_query()) {
            return;
        }
        
        // Filtrer uniquement les interventions
        if (!isset($query->query['post_type']) || $query->query['post_type'] !== 'intervention') {
            return;
        }
        
        $user_id = get_current_user_id();
        $client_projets = SUIVDEIN_User_Roles::get_client_projets($user_id);
        
        if (!empty($client_projets)) {
            // Afficher seulement les interventions des projets autorisés
            $tax_query = $query->get('tax_query') ?: array();
            $tax_query[] = array(
                'taxonomy' => 'projet',
                'field'    => 'term_id',
                'terms'    => $client_projets,
                'operator' => 'IN'
            );
            $query->set('tax_query', $tax_query);
            
            // Log pour débogage
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Client ID ' . $user_id . ' filtré pour les projets: ' . implode(', ', $client_projets));
            }
        } else {
            // Aucun projet autorisé, ne rien afficher
            $query->set('post__in', array(0));
            
            // Log pour débogage
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Client ID ' . $user_id . ' n\'a aucun projet assigné - aucune intervention visible');
            }
        }
    }
    
    /**
     * Masquer les éléments d'interface pour les clients
     */
    public function hide_interface_elements() {
        $user_id = get_current_user_id();
        $client_projets = SUIVDEIN_User_Roles::get_client_projets($user_id);
        $projet_names = array();
        
        // Récupérer les noms des projets
        if (!empty($client_projets)) {
            foreach ($client_projets as $projet_id) {
                $term = get_term($projet_id, 'projet');
                if ($term && !is_wp_error($term)) {
                    $projet_names[] = $term->name;
                }
            }
        }
    }
    
    /**
     * Rediriger le dashboard vers les interventions
     */
    public function redirect_dashboard() {
        global $pagenow;
        
        // Debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Redirect check - Page: ' . $pagenow . ', User: ' . get_current_user_id());
        }
        
        // Rediriger depuis le dashboard
        if ($pagenow === 'index.php') {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Redirecting from dashboard to interventions');
            }
            
            wp_safe_redirect(admin_url('edit.php?post_type=intervention'));
            exit;
        }
    }
    public function remove_admin_bar_links($wp_admin_bar) {
        // Supprimer les liens du menu WordPress
        $wp_admin_bar->remove_node('wp-logo');
        $wp_admin_bar->remove_node('about');
        $wp_admin_bar->remove_node('wporg');
        $wp_admin_bar->remove_node('documentation');
        $wp_admin_bar->remove_node('support-forums');
        $wp_admin_bar->remove_node('feedback');
        $wp_admin_bar->remove_node('site-name');
        $wp_admin_bar->remove_node('view-site');
        $wp_admin_bar->remove_node('dashboard');
        $wp_admin_bar->remove_node('themes');
        $wp_admin_bar->remove_node('widgets');
        $wp_admin_bar->remove_node('menus');
        $wp_admin_bar->remove_node('new-content');
        $wp_admin_bar->remove_node('comments');
        $wp_admin_bar->remove_node('customize');
    }
    
    /**
     * Assurer que le menu interventions est visible pour les clients
     */
    public function ensure_intervention_menu_visible() {
        global $menu, $submenu;
        
        // Debug
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('=== ENSURE INTERVENTION MENU ===');
            error_log('User ID: ' . get_current_user_id());
            error_log('Menu items: ' . print_r(array_keys($menu), true));
        }
        
        // Vérifier si le menu interventions existe
        $intervention_menu_exists = false;
        foreach ($menu as $menu_item) {
            if (isset($menu_item[2]) && strpos($menu_item[2], 'post_type=intervention') !== false) {
                $intervention_menu_exists = true;
                break;
            }
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Intervention menu exists: ' . ($intervention_menu_exists ? 'YES' : 'NO'));
        }
        
        // Si le menu n'existe pas, on a un problème avec l'enregistrement du post type
        if (!$intervention_menu_exists) {
            error_log('ERREUR: Le menu Interventions n\'existe pas !');
        }
    }
    
    /**
     * Forcer le masquage des menus via CSS en dernier recours
     */
    public function force_hide_menus() {
        global $pagenow;
        
    }
    
    /**
     * Supprimer les widgets du dashboard (au cas où)
     */
    public function remove_dashboard_widgets() {
        global $wp_meta_boxes;
        $wp_meta_boxes['dashboard'] = array();
    }
    
    /**
     * Restreindre les capacités des clients
     */
    public function restrict_client_capabilities($allcaps, $caps, $args) {
        // Empêcher l'édition/suppression des interventions
        $restricted_caps = array(
            'edit_intervention',
            'delete_intervention',
            'edit_interventions',
            'edit_others_interventions',
            'publish_interventions',
            'delete_interventions',
            'delete_private_interventions',
            'delete_published_interventions',
            'delete_others_interventions',
            'edit_private_interventions',
            'edit_published_interventions'
        );
        
        foreach ($restricted_caps as $cap) {
            if (isset($allcaps[$cap])) {
                $allcaps[$cap] = false;
            }
        }
        
        return $allcaps;
    }
    
    /**
     * Notice d'information pour les clients
     */
    public function client_info_notice() {
        global $pagenow;
        
        // Afficher seulement sur les pages d'interventions
        if ($pagenow !== 'edit.php' || 
            !isset($_GET['post_type']) || 
            $_GET['post_type'] !== 'intervention') {
            return;
        }
        
        $user_id = get_current_user_id();
        $client_info = SUIVDEIN_User_Roles::get_client_info($user_id);
        
        if (empty($client_info['projets'])) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_attr_e('Attention :', 'suivi-des-interventions'); ?></strong>
                    <?php esc_attr_e('Aucun projet ne vous a été assigné. Contactez l\'administrateur pour accéder aux interventions.', 'suivi-des-interventions'); ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Vérifier si un client peut voir une intervention spécifique
     */
    public static function can_client_view_intervention($post_id, $user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        // Récupérer les projets du client
        $client_projets = SUIVDEIN_User_Roles::get_client_projets($user_id);
        
        if (empty($client_projets)) {
            return false;
        }
        
        // Vérifier si l'intervention appartient à un des projets du client
        $intervention_projets = wp_get_post_terms($post_id, 'projet', array(
            'fields' => 'ids'
        ));
        
        if (is_wp_error($intervention_projets)) {
            return false;
        }
        
        // Vérifier l'intersection entre les projets du client et ceux de l'intervention
        return !empty(array_intersect($client_projets, $intervention_projets));
    }
    
    /**
     * Obtenir les statistiques pour un client
     */
    public static function get_client_stats($user_id = null) {
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        $client_projets = SUIVDEIN_User_Roles::get_client_projets($user_id);
        
        if (empty($client_projets)) {
            return array(
                'total_interventions' => 0,
                'interventions_terminees' => 0,
                'interventions_en_cours' => 0,
                'projets_count' => 0
            );
        }
        
        // Compter toutes les interventions
        $args_total = array(
            'post_type' => 'intervention',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'fields' => 'ids',
            'tax_query' => array(
                array(
                    'taxonomy' => 'projet',
                    'field' => 'term_id',
                    'terms' => $client_projets,
                    'operator' => 'IN'
                )
            )
        );
        
        $total_query = new WP_Query($args_total);
        $total_interventions = $total_query->found_posts;
        
        // Compter les interventions terminées
        $args_completed = $args_total;
        $args_completed['meta_query'] = array(
            array(
                'key' => '_intervention_terminee',
                'value' => '1',
                'compare' => '='
            )
        );
        
        $completed_query = new WP_Query($args_completed);
        $interventions_terminees = $completed_query->found_posts;
        
        return array(
            'total_interventions' => $total_interventions,
            'interventions_terminees' => $interventions_terminees,
            'interventions_en_cours' => $total_interventions - $interventions_terminees,
            'projets_count' => count($client_projets)
        );
    }
}