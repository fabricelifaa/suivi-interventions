<?php
/**
 * Gestion des rôles utilisateurs personnalisés
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class SI_User_Roles {
    
    /**
     * Constructeur
     */
    public function __construct() {
        add_action('init', array($this, 'setup_user_hooks'));
    }
    
    /**
     * Configuration des hooks utilisateurs
     */
    public function setup_user_hooks() {
        add_action('edit_user_profile', array($this, 'add_client_projet_fields'));
        add_action('edit_user_profile_update', array($this, 'save_client_projet_fields'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_user_scripts'));
    }
    
    /**
     * Ajouter le rôle client
     */
    public function add_client_role() {
        // Supprimer le rôle s'il existe déjà pour le recréer proprement
        remove_role('bsdclient');
        
        // Créer le rôle avec toutes les capacités nécessaires
        add_role('bsdclient', __('Client', 'suivi-interventions'), array(
            'read' => true,
            'read_intervention' => true,
            'read_interventions' => true,
            'edit_posts' => false,
            'delete_posts' => false,
        ));
        
        // S'assurer que les capacités sont bien ajoutées
        $role = get_role('bsdclient');
        if ($role) {
            $role->add_cap('read');
            $role->add_cap('read_intervention');
            $role->add_cap('read_interventions');
        }
        
        // Log pour vérification
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Rôle client "bsdclient" créé avec les capacités: ' . print_r($role->capabilities ?? 'none', true));
        }
    }
    
    /**
     * Supprimer le rôle client
     */
    public function remove_client_role() {
        remove_role('bsdclient');
    }
    
    /**
     * Ajouter les champs projets pour les clients
     */
    public function add_client_projet_fields($user) {
        // Debug pour voir ce qui se passe
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('=== DEBUG CHAMPS PROJETS ===');
            error_log('User ID: ' . $user->ID);
            error_log('User roles: ' . print_r($user->roles, true));
            error_log('Current user can administrator: ' . (current_user_can('administrator') ? 'OUI' : 'NON'));
            error_log('Should show fields: ' . ($this->should_show_projet_fields($user) ? 'OUI' : 'NON'));
        }
        
        // Vérifier si c'est un client ou si l'admin édite un client
        if (!$this->should_show_projet_fields($user)) {
            // Debug si les champs ne s'affichent pas
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('Champs projets NON affichés pour user ID: ' . $user->ID);
            }
            return;
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Champs projets affichés pour user ID: ' . $user->ID);
        }
        
        $selected_projets = get_user_meta($user->ID, 'client_projets', true);
        if (!is_array($selected_projets)) {
            $selected_projets = array();
        }
        
        // Récupérer tous les projets
        $projets = get_terms(array(
            'taxonomy' => 'projet',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        // Debug des projets trouvés
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Projets trouvés: ' . (is_array($projets) ? count($projets) : 'ERREUR'));
            if (is_wp_error($projets)) {
                error_log('Erreur projets: ' . $projets->get_error_message());
            }
        }
        
        include SUIVI_INTERVENTIONS_PLUGIN_DIR . 'admin/partials/client-projet-fields.php';
        
        // Ajouter du JavaScript pour la gestion en temps réel
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('=== DEBUG JAVASCRIPT PROJETS ===');
            console.log('Projets sélectionnés:', <?php echo json_encode($selected_projets); ?>);
            console.log('Nombre de projets disponibles:', $('.projet-checkbox').length);
            
            // Mise à jour en temps réel de la prévisualisation
            $('.projet-checkbox').on('change', function() {
                updateClientProjectPreview();
            });
            
            function updateClientProjectPreview() {
                var selectedProjects = [];
                $('.projet-checkbox:checked').each(function() {
                    var projectName = $(this).closest('.projet-item').find('strong').text();
                    selectedProjects.push(projectName);
                });
                
                console.log('Projets sélectionnés:', selectedProjects);
                
                var $preview = $('#client-project-preview');
                if (!$preview.length) {
                    $('.client-projets-list').after('<div id="client-project-preview" class="project-preview"></div>');
                    $preview = $('#client-project-preview');
                }
                
                if (selectedProjects.length > 0) {
                    $preview.html('<h4>Projets sélectionnés (' + selectedProjects.length + '):</h4><ul><li>' + selectedProjects.join('</li><li>') + '</li></ul>');
                    $preview.addClass('has-projects');
                } else {
                    $preview.html('<h4 style="color: #d63384;">⚠ Aucun projet sélectionné - Ce client ne pourra voir aucune intervention</h4>');
                    $preview.removeClass('has-projects');
                }
            }
            
            // Initialiser la prévisualisation
            updateClientProjectPreview();
        });
        </script>
        
        <style>
        .project-preview {
            margin-top: 15px;
            padding: 15px;
            border-radius: 6px;
            border: 2px solid #ddd;
            background: #f9f9f9;
        }
        .project-preview.has-projects {
            border-color: #00a0d2;
            background: #e7f3ff;
        }
        .project-preview h4 {
            margin-top: 0;
            color: #0073aa;
        }
        .project-preview ul {
            margin-bottom: 0;
        }
        </style>
        <?php
    }
    
    /**
     * Sauvegarder les champs projets pour les clients
     */
    public function save_client_projet_fields($user_id) {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }
        
        // Vérifier le nonce
        if (!isset($_POST['client_projets_nonce']) || 
            !wp_verify_nonce($_POST['client_projets_nonce'], 'save_client_projets')) {
            return false;
        }
        
        $client_projets = isset($_POST['client_projets']) ? 
            array_map('intval', $_POST['client_projets']) : 
            array();
        
        update_user_meta($user_id, 'client_projets', $client_projets);
        
        // Sauvegarder les informations supplémentaires
        if (isset($_POST['client_company'])) {
            update_user_meta(
                $user_id, 
                'client_company', 
                sanitize_text_field($_POST['client_company'])
            );
        }
        
        if (isset($_POST['client_contact_phone'])) {
            update_user_meta(
                $user_id, 
                'client_contact_phone', 
                sanitize_text_field($_POST['client_contact_phone'])
            );
        }
    }
    
    /**
     * Vérifier si les champs projets doivent être affichés
     */
    private function should_show_projet_fields($user) {
        // Toujours afficher pour les administrateurs
        if (current_user_can('administrator')) {
            // Vérifier si l'utilisateur édité est un client
            return in_array('bsdclient', (array) $user->roles);
        }
        
        // Si c'est l'utilisateur lui-même et qu'il est client
        if (get_current_user_id() === $user->ID && in_array('bsdclient', (array) $user->roles)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Enqueue les scripts pour les pages utilisateurs
     */
    public function enqueue_user_scripts($hook) {
        if ('profile.php' === $hook || 'user-edit.php' === $hook) {
            wp_enqueue_script(
                'si-user-scripts',
                SUIVI_INTERVENTIONS_PLUGIN_URL . 'admin/js/admin-script.js',
                array('jquery'),
                SUIVI_INTERVENTIONS_VERSION,
                true
            );
        }
    }
    
    /**
     * Obtenir les projets d'un client
     */
    public static function get_client_projets($user_id) {
        $projets = get_user_meta($user_id, 'client_projets', true);
        if (!is_array($projets)) {
            return array();
        }
        return array_map('intval', $projets);
    }
    
    /**
     * Vérifier si un client a accès à un projet
     */
    public static function client_has_projet_access($user_id, $projet_id) {
        $client_projets = self::get_client_projets($user_id);
        return in_array((int) $projet_id, $client_projets);
    }
    
    /**
     * Obtenir les informations supplémentaires d'un client
     */
    public static function get_client_info($user_id) {
        return array(
            'company' => get_user_meta($user_id, 'client_company', true),
            'phone' => get_user_meta($user_id, 'client_contact_phone', true),
            'projets' => self::get_client_projets($user_id)
        );
    }
}