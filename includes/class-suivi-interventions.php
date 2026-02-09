<?php
/**
 * Classe principale du plugin Suivi des Interventions
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class Suivi_Interventions {
    
    /**
     * Version du plugin
     * @var string
     */
    public $version = '1.0.0';
    
    /**
     * Instance unique de la classe
     * @var Suivi_Interventions
     */
    private static $instance = null;
    
    /**
     * Instances des classes du plugin
     */
    public $post_types;
    public $taxonomies;
    public $user_roles;
    public $admin;
    public $client_restrictions;
    public $client_management;
    public $client_dashboard;
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Instance unique (Singleton)
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        // Classes principales
        require_once SUIVDEIN_PLUGIN_DIR . 'includes/class-post-types.php';
        require_once SUIVDEIN_PLUGIN_DIR . 'includes/class-taxonomies.php';
        require_once SUIVDEIN_PLUGIN_DIR . 'includes/class-user-roles.php';
        require_once SUIVDEIN_PLUGIN_DIR . 'includes/class-admin.php';
        require_once SUIVDEIN_PLUGIN_DIR . 'includes/class-client-restrictions.php';
        require_once SUIVDEIN_PLUGIN_DIR . 'includes/class-client-management.php';
        require_once SUIVDEIN_PLUGIN_DIR . 'includes/class-client-dashboard.php';
    }
    
    /**
     * Initialiser les hooks WordPress
     */
    private function init_hooks() {
        add_action('init', array($this, 'init'), 3);        
        // Fallback de sécurité au cas où les classes ne s'enregistrent pas
        add_action('init', array($this, 'fallback_register_post_types'), 3);
    }
    
    /**
     * Fallback pour enregistrer les post types si nécessaire
     */
    public function fallback_register_post_types() {
        // Si le post type n'existe toujours pas, l'enregistrer directement
        if (!post_type_exists('suivdein_post')) {
            register_post_type('suivdein_post', array(
                'labels' => array(
                    'name' => 'Interventions',
                    'singular_name' => 'Intervention',
                    'menu_name' => 'Interventions',
                    'add_new' => 'Ajouter une intervention',
                    'add_new_item' => 'Ajouter une nouvelle intervention',
                    'edit_item' => 'Modifier l\'intervention',
                    'new_item' => 'Nouvelle intervention',
                    'view_item' => 'Voir l\'intervention',
                    'search_items' => 'Rechercher des interventions',
                    'not_found' => 'Aucune intervention trouvée',
                    'not_found_in_trash' => 'Aucune intervention dans la corbeille'
                ),
                'public' => false,
                'show_ui' => true,
                'show_in_menu' => true,
                'menu_icon' => 'dashicons-clipboard',
                'supports' => array('title', 'editor'),
                'capability_type' => 'post',
                'hierarchical' => false,
                'menu_position' => 20
            ));
        }
        
        // Si la taxonomie n'existe toujours pas, l'enregistrer directement
        if (!taxonomy_exists('suivdein_projet')) {
            register_taxonomy('suivdein_projet', array('suivdein_post'), array(
                'labels' => array(
                    'name' => 'Projets',
                    'singular_name' => 'Projet',
                    'menu_name' => 'Projets'
                ),
                'show_ui' => true,
                'show_admin_column' => true,
                'hierarchical' => false
            ));
        }
    }
    
    /**
     * Initialiser le plugin
     */
    public function init() {
        // Instancier les classes - elles s'enregistreront automatiquement
        $this->post_types = new SUIVDEIN_Post_Types();
        $this->taxonomies = new SUIVDEIN_Taxonomies();
        $this->user_roles = new SUIVDEIN_User_Roles();
        
        if (is_admin()) {
            $this->admin = new SUIVDEIN_Admin();
            $this->client_management = new SUIVDEIN_Client_Management();
            // Dashboard client uniquement pour les clients
            if (current_user_can('bsdclient') && !current_user_can('administrator')) {
                $this->client_dashboard = new SUIVDEIN_Client_Dashboard();
            }
        }
        
        // Restrictions clients
        if (current_user_can('bsdclient') && !current_user_can('administrator')) {
            $this->client_restrictions = new SUIVDEIN_Client_Restrictions();
        }
    }
    
    /**
     * Charger le domaine de traduction
     */
    // public function load_textdomain() {
    //     load_plugin_textdomain(
    //         'suivi-des-interventions',
    //         false,
    //         dirname(SUIVI_INTERVENTIONS_PLUGIN_BASENAME) . '/languages/'
    //     );
    // }
    
    /**
     * Exécuter le plugin
     */
    public function run() {
        // Le plugin est maintenant initialisé
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les post types et taxonomies
        $this->post_types = new SUIVDEIN_Post_Types();
        $this->taxonomies = new SUIVDEIN_Taxonomies();
        $this->user_roles = new SUIVDEIN_User_Roles();
        
        $this->post_types->suivdein_register_post_type();
        $this->taxonomies->suivdein_register_taxonomy();
        $this->user_roles->add_client_role();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Créer les capacités
        $this->add_capabilities();
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Ajouter les capacités nécessaires
     */
    private function add_capabilities() {
        // Ajouter les capacités au rôle administrateur
        $admin_role = get_role('administrator');
        if ($admin_role) {
            $admin_role->add_cap('edit_intervention');
            $admin_role->add_cap('read_intervention');
            $admin_role->add_cap('delete_intervention');
            $admin_role->add_cap('edit_interventions');
            $admin_role->add_cap('edit_others_interventions');
            $admin_role->add_cap('publish_interventions');
            $admin_role->add_cap('read_private_interventions');
            $admin_role->add_cap('delete_interventions');
            $admin_role->add_cap('delete_private_interventions');
            $admin_role->add_cap('delete_published_interventions');
            $admin_role->add_cap('delete_others_interventions');
            $admin_role->add_cap('edit_private_interventions');
            $admin_role->add_cap('edit_published_interventions');
        }
    }
    
    /**
     * Obtenir la version du plugin
     */
    public function get_version() {
        return $this->version;
    }
}