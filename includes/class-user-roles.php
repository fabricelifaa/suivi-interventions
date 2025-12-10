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

class SUIVDEIN_User_Roles
{

    /**
     * Constructeur
     */
    public function __construct()
    {
        add_action('init', array($this, 'setup_suivdein_user_hooks'));
    }

    /**
     * Configuration des hooks utilisateurs
     */
    public function setup_suivdein_user_hooks()
    {
        add_action('edit_user_profile', array($this, 'suivdein_add_client_projet_fields'));
        add_action('edit_user_profile_update', array($this, 'suivdein_save_client_projet_fields'));
        add_action('admin_enqueue_scripts', array($this, 'suivdein_enqueue_user_scripts'));
    }

    /**
     * Ajouter le rôle client
     */
    public function add_client_role()
    {
        // Supprimer le rôle s'il existe déjà pour le recréer proprement
        remove_role('bsdclient');

        // Créer le rôle avec toutes les capacités nécessaires
        add_role('bsdclient', __('Client', 'suivi-des-interventions'), array(
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
    public function remove_client_role()
    {
        remove_role('bsdclient');
    }

    /**
     * Ajouter les champs projets pour les clients
     */
    public function suivdein_add_client_projet_fields($user)
    {
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
        $suivdein_projets = get_terms(array(
            'taxonomy' => 'projet',
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));

        // Debug des projets trouvés
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('Projets trouvés: ' . (is_array($suivdein_projets) ? count($suivdein_projets) : 'ERREUR'));
            if (is_wp_error($suivdein_projets)) {
                error_log('Erreur projets: ' . $suivdein_projets->get_error_message());
            }
        }

        include SUIVDEIN_PLUGIN_DIR . 'admin/partials/client-projet-fields.php';
    }

    /**
     * Sauvegarder les champs projets pour les clients
     */
    public function suivdein_save_client_projet_fields($user_id)
    {
        if (!current_user_can('edit_user', $user_id)) {
            return false;
        }

        // Vérifier le nonce
        if (
            !isset($_POST['client_projets_nonce']) ||
            !wp_verify_nonce($_POST['client_projets_nonce'], 'suivdein_save_client_projets')
        ) {
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
    private function should_show_projet_fields($user)
    {
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
    public function suivdein_enqueue_user_scripts($hook)
    {
        wp_enqueue_style(
            'si-admin-style',
            SUIVDEIN_PLUGIN_URL . 'admin/css/admin-style.css',
            array(),
            SUIVDEIN_VERSION
        );
        if ('profile.php' === $hook || 'user-edit.php' === $hook) {
            wp_enqueue_script(
                'si-user-scripts',
                SUIVDEIN_PLUGIN_URL . 'admin/js/admin-script.js',
                array('jquery'),
                SUIVDEIN_VERSION,
                true
            );
        }
    }

    /**
     * Obtenir les projets d'un client
     */
    public static function get_client_projets($user_id)
    {
        $suivdein_projets = get_user_meta($user_id, 'client_projets', true);
        if (!is_array($suivdein_projets)) {
            return array();
        }
        return array_map('intval', $suivdein_projets);
    }

    /**
     * Vérifier si un client a accès à un projet
     */
    public static function client_has_projet_access($user_id, $suivdein_projet_id)
    {
        $client_projets = self::get_client_projets($user_id);
        return in_array((int) $suivdein_projet_id, $client_projets);
    }

    /**
     * Obtenir les informations supplémentaires d'un client
     */
    public static function get_client_info($user_id)
    {
        return array(
            'company' => get_user_meta($user_id, 'client_company', true),
            'phone' => get_user_meta($user_id, 'client_contact_phone', true),
            'projets' => self::get_client_projets($user_id)
        );
    }
}
