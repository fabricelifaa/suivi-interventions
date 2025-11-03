<?php
/**
 * Page de gestion des liaisons client-projet
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class SI_Client_Management {
    
    /**
     * Constructeur
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_management_page'));
        add_action('wp_ajax_si_update_client_projects', array($this, 'ajax_update_client_projects'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }
    
    /**
     * Ajouter la page de gestion
     */
    public function add_management_page() {
        add_submenu_page(
            'edit.php?post_type=intervention',
            esc_attr__('Gestion des Clients', 'suivi-des-interventions'),
            esc_attr__('Clients & Projets', 'suivi-des-interventions'),
            'manage_options',
            'si-client-management',
            array($this, 'render_management_page')
        );
    }
    
    /**
     * Enqueue les scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'si-client-management') === false) {
            return;
        }
        
        wp_enqueue_script('jquery-ui-sortable');
        wp_enqueue_script(
            'si-client-management',
            SUIVI_INTERVENTIONS_PLUGIN_URL . 'admin/js/client-management.js',
            array('jquery', 'jquery-ui-sortable'),
            SUIVI_INTERVENTIONS_VERSION,
            true
        );
        
        wp_localize_script('si-client-management', 'siClientManagement', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('si_client_management'),
            'strings' => array(
                'confirm_delete' => esc_attr_e('Êtes-vous sûr de vouloir supprimer cette liaison ?', 'suivi-des-interventions'),
                'success_update' => esc_attr_e('Liaisons mises à jour avec succès', 'suivi-des-interventions'),
                'error_update' => esc_attr_e('Erreur lors de la mise à jour', 'suivi-des-interventions')
            )
        ));
    }
    
    /**
     * Rendu de la page de gestion
     */
    public function render_management_page() {
        // Traiter les soumissions de formulaire
        if (isset($_POST['action']) && $_POST['action'] === 'bulk_assign' && wp_verify_nonce($_POST['_wpnonce'], 'si_bulk_assign')) {
            $this->handle_bulk_assignment();
        }
        
        // Récupérer les données
        $clients = $this->get_clients_with_projects();
        $projects = $this->get_all_projects();
        $unassigned_clients = $this->get_unassigned_clients();
        ?>
        <div class="wrap">
            <h1><?php esc_attr_e('Gestion des Clients et Projets', 'suivi-des-interventions'); ?></h1>
            
            <!-- Statistiques rapides -->
            <div class="si-stats-cards">
                <div class="stats-card">
                    <h3><?php echo count($clients); ?></h3>
                    <p><?php esc_attr_e('Clients avec projets', 'suivi-des-interventions'); ?></p>
                </div>
                <div class="stats-card">
                    <h3><?php echo count($projects); ?></h3>
                    <p><?php esc_attr_e('Projets total', 'suivi-des-interventions'); ?></p>
                </div>
                <div class="stats-card">
                    <h3><?php echo count($unassigned_clients); ?></h3>
                    <p><?php esc_attr_e('Clients sans projet', 'suivi-des-interventions'); ?></p>
                </div>
            </div>
            
            <!-- Clients sans projets -->
            <?php if (!empty($unassigned_clients)) : ?>
            <div class="si-unassigned-section">
                <h2><?php esc_attr_e('⚠ Clients sans projets assignés', 'suivi-des-interventions'); ?></h2>
                <div class="unassigned-clients">
                    <?php foreach ($unassigned_clients as $client) : ?>
                        <div class="unassigned-client">
                            <strong><?php echo esc_html($client->display_name); ?></strong>
                            <span>(<?php echo esc_html($client->user_email); ?>)</span>
                            <a href="<?php echo admin_url('user-edit.php?user_id=' . $client->ID); ?>" class="button button-small">
                                <?php esc_attr_e('Assigner des projets', 'suivi-des-interventions'); ?>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Liaisons existantes -->
            <h2><?php esc_attr_e('Liaisons Client ↔ Projet existantes', 'suivi-des-interventions'); ?></h2>
            
            <div class="si-client-projects-grid">
                <?php foreach ($clients as $client_data) : ?>
                    <div class="client-project-card" data-client-id="<?php echo $client_data['client']->ID; ?>">
                        <div class="client-header">
                            <h3><?php echo esc_html($client_data['client']->display_name); ?></h3>
                            <span class="client-email"><?php echo esc_html($client_data['client']->user_email); ?></span>
                            <div class="client-actions">
                                <a href="<?php echo admin_url('user-edit.php?user_id=' . $client_data['client']->ID); ?>" class="button button-small">
                                    <?php esc_attr_e('Modifier', 'suivi-des-interventions'); ?>
                                </a>
                            </div>
                        </div>
                        
                        <div class="client-projects">
                            <h4><?php esc_attr_e('Projets assignés:', 'suivi-des-interventions'); ?></h4>
                            <?php if (!empty($client_data['projects'])) : ?>
                                <ul class="project-list">
                                    <?php foreach ($client_data['projects'] as $project) : ?>
                                        <li class="project-item">
                                            <span class="project-name"><?php echo esc_html($project->name); ?></span>
                                            <?php
                                            $progression = SI_Taxonomies::get_projet_progression($project->term_id);
                                            $color_class = si_get_progress_color_class($progression['percentage']);
                                            ?>
                                            <div class="mini-progress">
                                                <div class="mini-progress-bar">
                                                    <div class="mini-progress-fill <?php echo $color_class; ?>" 
                                                         style="width: <?php echo min(100, $progression['percentage']); ?>%"></div>
                                                </div>
                                                <span class="progress-text"><?php echo $progression['used']; ?>/<?php echo $progression['quota']; ?></span>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else : ?>
                                <p class="no-projects"><?php esc_attr_e('Aucun projet assigné', 'suivi-des-interventions'); ?></p>
                            <?php endif; ?>
                            
                            <!-- Statistiques du client -->
                            <div class="client-stats">
                                <?php
                                $stats = SI_Client_Restrictions::get_client_stats($client_data['client']->ID);
                                ?>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $stats['total_interventions']; ?></span>
                                    <span class="stat-label"><?php esc_attr_e('Interventions visibles', 'suivi-des-interventions'); ?></span>
                                </div>
                                <div class="stat-item">
                                    <span class="stat-number"><?php echo $stats['interventions_terminees']; ?></span>
                                    <span class="stat-label"><?php esc_attr_e('Terminées', 'suivi-des-interventions'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Assignation en lot -->
            <div class="si-bulk-assignment">
                <h2><?php esc_attr_e('Assignation rapide', 'suivi-des-interventions'); ?></h2>
                <form method="post" id="bulk-assignment-form">
                    <?php wp_nonce_field('si_bulk_assign'); ?>
                    <input type="hidden" name="action" value="bulk_assign">
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_attr_e('Sélectionner un projet', 'suivi-des-interventions'); ?></th>
                            <td>
                                <select name="bulk_project" id="bulk_project" required>
                                    <option value=""><?php esc_attr_e('-- Choisir un projet --', 'suivi-des-interventions'); ?></option>
                                    <?php foreach ($projects as $project) : ?>
                                        <option value="<?php echo $project->term_id; ?>"><?php echo esc_html($project->name); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_attr_e('Sélectionner des clients', 'suivi-des-interventions'); ?></th>
                            <td>
                                <div class="client-checkboxes">
                                    <?php
                                    $all_clients = get_users(array('role' => 'bsdclient'));
                                    foreach ($all_clients as $client) :
                                    ?>
                                        <label class="client-checkbox-label">
                                            <input type="checkbox" name="bulk_clients[]" value="<?php echo $client->ID; ?>">
                                            <?php echo esc_html($client->display_name); ?> (<?php echo esc_html($client->user_email); ?>)
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                                <p class="description"><?php esc_attr_e('Le projet sera ajouté aux clients sélectionnés (sans supprimer leurs projets existants)', 'suivi-des-interventions'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(esc_attr_e('Assigner le projet aux clients sélectionnés', 'suivi-des-interventions'), 'primary', 'submit', false); ?>
                </form>
            </div>
        </div>
        
        <style>
        .si-stats-cards {
            display: flex;
            gap: 20px;
            margin: 20px 0;
        }
        
        .stats-card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            min-width: 120px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        
        .stats-card h3 {
            font-size: 32px;
            margin: 0 0 10px 0;
            color: #0073aa;
        }
        
        .stats-card p {
            margin: 0;
            color: #646970;
            font-size: 13px;
        }
        
        .si-unassigned-section {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .unassigned-clients {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .unassigned-client {
            background: white;
            padding: 10px 15px;
            border-radius: 4px;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .si-client-projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .client-project-card {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 6px;
            padding: 20px;
            box-shadow: 0 1px 1px rgba(0,0,0,0.04);
        }
        
        .client-header {
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
            margin-bottom: 15px;
        }
        
        .client-header h3 {
            margin: 0 0 5px 0;
            color: #1d2327;
        }
        
        .client-email {
            color: #646970;
            font-size: 13px;
        }
        
        .client-actions {
            margin-top: 10px;
        }
        
        .project-list {
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .project-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .project-item:last-child {
            border-bottom: none;
        }
        
        .mini-progress {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .mini-progress-bar {
            width: 60px;
            height: 8px;
            background: #f0f0f0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .mini-progress-fill {
            height: 100%;
            transition: width 0.3s ease;
        }
        
        .progress-text {
            font-size: 11px;
            color: #646970;
        }
        
        .client-stats {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-number {
            display: block;
            font-size: 18px;
            font-weight: 600;
            color: #0073aa;
        }
        
        .stat-label {
            font-size: 11px;
            color: #646970;
            text-transform: uppercase;
        }
        
        .si-bulk-assignment {
            background: white;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .client-checkboxes {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 10px;
            background: #fafafa;
        }
        
        .client-checkbox-label {
            display: block;
            padding: 5px;
            margin-bottom: 5px;
        }
        
        .client-checkbox-label:hover {
            background: rgba(0,115,170,0.1);
            border-radius: 3px;
        }
        
        .no-projects {
            color: #d63384;
            font-style: italic;
            margin: 0;
        }
        </style>
        <?php
    }
    
    /**
     * Récupérer les clients avec leurs projets
     */
    private function get_clients_with_projects() {
        $clients = get_users(array('role' => 'bsdclient'));
        $clients_with_projects = array();
        
        foreach ($clients as $client) {
            $project_ids = SI_User_Roles::get_client_projets($client->ID);
            
            if (!empty($project_ids)) {
                $projects = get_terms(array(
                    'taxonomy' => 'projet',
                    'include' => $project_ids,
                    'hide_empty' => false
                ));
                
                $clients_with_projects[] = array(
                    'client' => $client,
                    'projects' => $projects
                );
            }
        }
        
        return $clients_with_projects;
    }
    
    /**
     * Récupérer tous les projets
     */
    private function get_all_projects() {
        return get_terms(array(
            'taxonomy' => 'projet',
            'hide_empty' => false
        ));
    }
    
    /**
     * Récupérer les clients sans projets
     */
    private function get_unassigned_clients() {
        $clients = get_users(array('role' => 'bsdclient'));
        $unassigned = array();
        
        foreach ($clients as $client) {
            $project_ids = SI_User_Roles::get_client_projets($client->ID);
            if (empty($project_ids)) {
                $unassigned[] = $client;
            }
        }
        
        return $unassigned;
    }
    
    /**
     * Gérer l'assignation en lot
     */
    private function handle_bulk_assignment() {
        $project_id = intval($_POST['bulk_project']);
        $client_ids = isset($_POST['bulk_clients']) ? array_map('intval', $_POST['bulk_clients']) : array();
        
        if (!$project_id || empty($client_ids)) {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Veuillez sélectionner un projet et au moins un client.</p></div>';
            });
            return;
        }
        
        $updated_count = 0;
        
        foreach ($client_ids as $client_id) {
            $existing_projects = SI_User_Roles::get_client_projets($client_id);
            
            if (!in_array($project_id, $existing_projects)) {
                $existing_projects[] = $project_id;
                update_user_meta($client_id, 'client_projets', $existing_projects);
                $updated_count++;
            }
        }
        
        add_action('admin_notices', function() use ($updated_count) {
            echo '<div class="notice notice-success"><p>' . sprintf('Projet assigné à %d client(s) avec succès.', $updated_count) . '</p></div>';
        });
    }
    
    /**
     * AJAX pour mise à jour des projets clients
     */
    public function ajax_update_client_projects() {
        if (!wp_verify_nonce($_POST['nonce'], 'si_client_management')) {
            wp_die('Erreur de sécurité');
        }
        
        if (!current_user_can('manage_options')) {
            wp_die('Permissions insuffisantes');
        }
        
        $client_id = intval($_POST['client_id']);
        $project_ids = isset($_POST['project_ids']) ? array_map('intval', $_POST['project_ids']) : array();
        
        update_user_meta($client_id, 'client_projets', $project_ids);
        
        wp_send_json_success(array(
            'message' => 'Projets mis à jour avec succès'
        ));
    }
}