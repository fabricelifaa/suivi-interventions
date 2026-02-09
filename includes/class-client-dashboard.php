<?php
/**
 * Dashboard personnalisé pour les clients
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class SUIVDEIN_Client_Dashboard {
    
    /**
     * Constructeur
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'suivdein_add_client_menu'));
        add_action('admin_enqueue_scripts', array($this, 'suivdein_enqueue_scripts'));
    }
    
    /**
     * Ajouter le menu pour les clients
     */
    public function suivdein_add_client_menu() {
        // Menu principal - Mes Interventions
        add_menu_page(
            esc_html__('Mes Interventions', 'suivi-des-interventions'),
            esc_html__('Mes Interventions', 'suivi-des-interventions'),
            'read',
            'client-interventions',
            array($this, 'render_interventions_page'),
            'dashicons-clipboard',
            3
        );
    }
    
    /**
     * Enqueue les scripts et styles
     */
    public function suivdein_enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_client-interventions') {
            return;
        }
        
        wp_enqueue_style(
            'si-client-dashboard',
            SUIVDEIN_PLUGIN_URL . 'admin/css/client-dashboard.css',
            array(),
            SUIVDEIN_VERSION
        );
        
        wp_enqueue_script(
            'si-client-dashboard',
            SUIVDEIN_PLUGIN_URL . 'admin/js/client-dashboard.js',
            array('jquery'),
            SUIVDEIN_VERSION,
            true
        );
    }
    
    /**
     * Rendu de la page des interventions
     */
    public function render_interventions_page() {
        $user_id = get_current_user_id();
        $user = wp_get_current_user();
        
        // Récupérer les projets du client
        $client_projets = SUIVDEIN_User_Roles::get_client_projets($user_id);
        
        // Récupérer les statistiques
        $stats = SUIVDEIN_Client_Restrictions::get_client_stats($user_id);
        
        // Récupérer les interventions
        $interventions = $this->get_client_interventions($user_id, $client_projets);
        
        // Récupérer les informations des projets
        $projets_info = $this->get_projets_info($client_projets);
        
        ?>
        <div class="wrap si-client-dashboard">
            <h1><?php esc_html_e('Mes Interventions', 'suivi-des-interventions'); ?></h1>
            
            <!-- Bannière d'accueil -->
            <div class="client-welcome-banner">
                <div class="welcome-content">
                    <h2><?php /* translators: %s: Nom de l'utilisateur */ printf(esc_html__('Bonjour, %s', 'suivi-des-interventions'), esc_html($user->display_name)); ?></h2>
                    <p><?php esc_html_e('Bienvenue sur votre tableau de bord. Vous pouvez consulter toutes les interventions réalisées sur vos projets.', 'suivi-des-interventions'); ?></p>
                </div>
                <div class="welcome-badge">
                    <span class="badge-icon"><img src="<?php echo esc_attr(SUIVDEIN_PLUGIN_URL . 'assets/logo.svg'); ?>" alt="Suivi Interventions" style="width: 100px; margin-bottom: 20px;"></span>
                    <span class="badge-text"><?php esc_html_e('Mode Consultation', 'suivi-des-interventions'); ?></span>
                </div>
            </div>
            
            <!-- Statistiques -->
            <div class="stats-grid">
                <div class="stat-card stat-primary">
                    <div class="stat-icon">📊</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['projets_count']); ?></div>
                        <div class="stat-label"><?php esc_html_e('Projet(s)', 'suivi-des-interventions'); ?></div>
                    </div>
                </div>
                
                <div class="stat-card stat-success">
                    <div class="stat-icon">✓</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['interventions_terminees']); ?></div>
                        <div class="stat-label"><?php esc_html_e('Interventions terminées', 'suivi-des-interventions'); ?></div>
                    </div>
                </div>
                
                <div class="stat-card stat-warning">
                    <div class="stat-icon">⏳</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['interventions_en_cours']); ?></div>
                        <div class="stat-label"><?php esc_html_e('En cours', 'suivi-des-interventions'); ?></div>
                    </div>
                </div>
                
                <div class="stat-card stat-info">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <div class="stat-number"><?php echo esc_html($stats['total_interventions']); ?></div>
                        <div class="stat-label"><?php esc_html_e('Total interventions', 'suivi-des-interventions'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Projets -->
            <?php if (!empty($projets_info)) : ?>
            <div class="projects-section">
                <h2><?php esc_html_e('Vos Projets', 'suivi-des-interventions'); ?></h2>
                <div class="projects-grid">
                    <?php foreach ($projets_info as $projet) : ?>
                        <div class="project-card">
                            <div class="project-header">
                                <h3><?php echo esc_html($projet['name']); ?></h3>
                                <?php if ($projet['quota'] > 0) : ?>
                                    <span class="project-quota"><?php /* translators: %1$d: used, %2$d: quota */ printf(esc_html__('%1$d/%2$d', 'suivi-des-interventions'), $projet['progression']['used'], $projet['quota']); ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($projet['quota'] > 0) : ?>
                                <div class="project-progress">
                                    <?php
                                    $percentage = $projet['progression']['percentage'];
                                    $color_class = suivdein_get_progress_color_class($percentage);
                                    ?>
                                    <div class="progress-bar">
                                        <div class="progress-fill <?php echo esc_attr($color_class); ?>" style="width: <?php echo esc_attr(min(100, $percentage)); ?>%"></div>
                                    </div>
                                    <div class="progress-text">
                                        <?php /* translators: %1$d: remaining, %2$.1f: percentage with one decimal */ printf(esc_html__('%1$d interventions restantes (%2$.1f%% utilisé)', 'suivi-des-interventions'), $projet['progression']['remaining'], $percentage); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($projet['date_expiration']) : ?>
                                <div class="project-expiration">
                                    <span class="expiration-icon">📅</span>
                                    <?php /* translators: %1$s: expiration date */ printf(esc_html__('Expire le : %1$s', 'suivi-des-interventions'), esc_html(date_i18n(get_option('date_format'), strtotime($projet['date_expiration'])))); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($projet['url']) : ?>
                                <div class="project-url">
                                    <a href="<?php echo esc_url($projet['url']); ?>" target="_blank" rel="noopener">
                                        <?php esc_html_e('Voir le site', 'suivi-des-interventions'); ?> ↗
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php else : ?>
            <div class="no-projects-message">
                <div class="message-icon">⚠️</div>
                <h3><?php esc_html_e('Aucun projet assigné', 'suivi-des-interventions'); ?></h3>
                <p><?php esc_html_e('Aucun projet ne vous a été assigné pour le moment. Veuillez contacter l\'administrateur.', 'suivi-des-interventions'); ?></p>
            </div>
            <?php endif; ?>
            
            <!-- Filtres -->
            <?php if (!empty($interventions)) : ?>
            <div class="filters-bar">
                <div class="filters-left">
                    <select id="filter-projet" class="filter-select">
                        <option value=""><?php esc_html_e('Tous les projets', 'suivi-des-interventions'); ?></option>
                        <?php foreach ($projets_info as $projet) : ?>
                            <option value="<?php echo esc_attr($projet['term_id']); ?>"><?php echo esc_html($projet['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    
                    <select id="filter-status" class="filter-select">
                        <option value=""><?php esc_html_e('Tous les statuts', 'suivi-des-interventions'); ?></option>
                        <option value="completed"><?php esc_html_e('Terminées', 'suivi-des-interventions'); ?></option>
                        <option value="pending"><?php esc_html_e('En cours', 'suivi-des-interventions'); ?></option>
                    </select>
                    
                    <div class="date-filter-wrapper">
                        <div class="date-filter-group">
                            <input type="date" id="filter-date-from" class="filter-date" placeholder="<?php esc_html_e('Date de début', 'suivi-des-interventions'); ?>">
                            <span class="date-separator">→</span>
                            <input type="date" id="filter-date-to" class="filter-date" placeholder="<?php esc_html_e('Date de fin', 'suivi-des-interventions'); ?>">
                        </div>
                        <div class="date-shortcuts">
                            <button type="button" class="date-shortcut" data-period="today"><?php esc_html_e('Aujourd\'hui', 'suivi-des-interventions'); ?></button>
                            <button type="button" class="date-shortcut" data-period="week"><?php esc_html_e('Cette semaine', 'suivi-des-interventions'); ?></button>
                            <button type="button" class="date-shortcut" data-period="month"><?php esc_html_e('Ce mois', 'suivi-des-interventions'); ?></button>
                            <button type="button" class="date-shortcut" data-period="year"><?php esc_html_e('Cette année', 'suivi-des-interventions'); ?></button>
                        </div>
                    </div>
                    
                    <input type="search" id="search-interventions" class="filter-search" placeholder="<?php esc_html_e('Rechercher...', 'suivi-des-interventions'); ?>">
                </div>
                
                <div class="filters-right">
                    <button type="button" id="reset-filters" class="button"><?php esc_html_e('Réinitialiser', 'suivi-des-interventions'); ?></button>
                </div>
            </div>
            
            <!-- Liste des interventions -->
            <div class="interventions-list">
                <h2>
                    <?php esc_html_e('Liste des interventions', 'suivi-des-interventions'); ?>
                    <span class="interventions-count">(<?php echo count($interventions); ?>)</span>
                </h2>
                
                <div class="interventions-grid">
                    <?php foreach ($interventions as $intervention) : ?>
                        <div class="intervention-card" 
                             data-projet="<?php echo esc_attr($intervention['projet_id']); ?>" 
                             data-status="<?php echo $intervention['terminee'] ? 'completed' : 'pending'; ?>"
                             data-date="<?php echo esc_attr($intervention['date_intervention']); ?>"
                             data-date-timestamp="<?php echo $intervention['date_intervention'] ? esc_attr(strtotime($intervention['date_intervention'])) : 0; ?>">
                            
                            <div class="intervention-header">
                                <h3 class="intervention-title"><?php echo esc_html($intervention['title']); ?></h3>
                                <span class="intervention-status <?php echo $intervention['terminee'] ? 'status-completed' : 'status-pending'; ?>">
                                    <?php echo $intervention['terminee'] ? '✓ ' . esc_html__('Terminée', 'suivi-des-interventions') : '⏳ ' . esc_html__('En cours', 'suivi-des-interventions'); ?>
                                </span>
                            </div>
                            
                            <div class="intervention-meta">
                                <div class="meta-item">
                                    <span class="meta-icon">📅</span>
                                    <span class="meta-label"><?php esc_html_e('Date :', 'suivi-des-interventions'); ?></span>
                                    <span class="meta-value"><?php echo esc_html($intervention['date_formatted']); ?></span>
                                </div>
                                
                                <div class="meta-item">
                                    <span class="meta-icon">🏷️</span>
                                    <span class="meta-label"><?php esc_html_e('Projet :', 'suivi-des-interventions'); ?></span>
                                    <span class="meta-value"><?php esc_html($intervention['projet_name']); ?></span>
                                </div>
                            </div>
                            
                            <?php if (!empty($intervention['content'])) : ?>
                                <div class="intervention-content">
                                    <?php echo esc_html(wp_trim_words($intervention['content'], 30, '...')); ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($intervention['description'])) : ?>
                                <div class="intervention-description">
                                    <strong><?php esc_html_e('Description :', 'suivi-des-interventions'); ?></strong>
                                    <?php echo wpautop(wp_kses_post($intervention['description'])); ?>
                                </div>
                            <?php endif; ?>
                            
                            <div class="intervention-footer">
                                <span class="intervention-date-created">
                                    <?php /* translators: %1$s: Date */ printf(esc_html__('Créée le %1$s', 'suivi-des-interventions'), esc_html(date_i18n(get_option('date_format'), strtotime($intervention['date_created'])))); ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="no-results" style="display: none;">
                    <p><?php esc_html_e('Aucune intervention ne correspond à vos critères de recherche.', 'suivi-des-interventions'); ?></p>
                </div>
            </div>
            <?php else : ?>
            <div class="no-interventions-message">
                <div class="message-icon">📭</div>
                <h3><?php esc_html_e('Aucune intervention', 'suivi-des-interventions'); ?></h3>
                <p><?php esc_html_e('Aucune intervention n\'a encore été créée pour vos projets.', 'suivi-des-interventions'); ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Récupérer les interventions du client
     */
    private function get_client_interventions($user_id, $client_projets) {
        if (empty($client_projets)) {
            return array();
        }
        
        $args = array(
            'post_type' => 'suivdein_post',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => '_date_intervention',
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
        
        $posts = get_posts($args);
        $interventions = array();
        
        foreach ($posts as $post) {
            $meta = SUIVDEIN_Post_Types::get_intervention_meta($post->ID);
            $terms = wp_get_post_terms($post->ID, 'suivdein_projet');
            $projet_name = !empty($terms) ? $terms[0]->name : esc_html__('Sans projet', 'suivi-des-interventions');
            $projet_id = !empty($terms) ? $terms[0]->term_id : 0;
            
            $interventions[] = array(
                'ID' => $post->ID,
                'title' => $post->post_title,
                'content' => $post->post_content,
                'date_created' => $post->post_date,
                'date_intervention' => $meta['date_intervention'],
                'date_formatted' => $meta['date_intervention'] ? esc_html(date_i18n(get_option('date_format'), strtotime($meta['date_intervention']))) : esc_html__('Non définie', 'suivi-des-interventions'),
                'terminee' => $meta['intervention_terminee'] == '1',
                'description' => $meta['description'],
                'projet_name' => $projet_name,
                'projet_id' => $projet_id
            );
        }
        
        return $interventions;
    }
    
    /**
     * Récupérer les informations des projets
     */
    private function get_projets_info($client_projets) {
        if (empty($client_projets)) {
            return array();
        }
        
        $projets = get_terms(array(
            'taxonomy' => 'suivdein_projet',
            'include' => $client_projets,
            'hide_empty' => false
        ));
        
        $projets_info = array();
        
        foreach ($projets as $projet) {
            $meta = SUIVDEIN_Taxonomies::get_projet_meta($projet->term_id);
            $progression = SUIVDEIN_Taxonomies::get_projet_progression($projet->term_id);
            
            $projets_info[] = array(
                'term_id' => $projet->term_id,
                'name' => $projet->name,
                'quota' => $meta['quota'] ?? 0,
                'date_expiration' => $meta['date_expiration'] ?? '',
                'url' => $meta['projet_url'] ?? '',
                'progression' => $progression
            );
        }
        
        return $projets_info;
    }
}