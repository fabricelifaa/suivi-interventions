<?php
/**
 * Template pour les champs projets des clients
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les informations client existantes
$suivdein_client_company = get_user_meta($user->ID, 'client_company', true);
$suivdein_client_phone = get_user_meta($user->ID, 'client_contact_phone', true);
?>

<h3><?php esc_html_e('Informations Client', 'suivi-des-interventions'); ?></h3>

<table class="form-table">
    <tr>
        <th>
            <label for="client_company"><?php esc_html_e('Société/Organisation', 'suivi-des-interventions'); ?></label>
        </th>
        <td>
            <input 
                type="text" 
                name="client_company" 
                id="client_company" 
                value="<?php echo esc_attr($suivdein_client_company); ?>" 
                class="regular-text" 
            />
            <p class="description"><?php esc_html_e('Nom de la société ou organisation du client.', 'suivi-des-interventions'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th>
            <label for="client_contact_phone"><?php esc_html_e('Téléphone de contact', 'suivi-des-interventions'); ?></label>
        </th>
        <td>
            <input 
                type="tel" 
                name="client_contact_phone" 
                id="client_contact_phone" 
                value="<?php echo esc_attr($suivdein_client_phone); ?>" 
                class="regular-text" 
            />
            <p class="description"><?php esc_html_e('Numéro de téléphone de contact du client.', 'suivi-des-interventions'); ?></p>
        </td>
    </tr>
</table>

<h3><?php esc_html_e('Projets autorisés', 'suivi-des-interventions'); ?></h3>

<table class="form-table">
    <tr>
        <th>
            <label><?php esc_html_e('Projets', 'suivi-des-interventions'); ?></label>
        </th>
        <td>
            <?php wp_nonce_field('suivdein_save_client_projets', 'client_projets_nonce'); ?>
            
            <?php if ($suivdein_projets && !is_wp_error($suivdein_projets)) : ?>
                <div class="client-projets-list">
                    <?php foreach ($suivdein_projets as $suivdein_projet) : 
                        $suivdein_projet_meta = SUIVDEIN_Taxonomies::get_projet_meta($suivdein_projet->term_id);
                        $progression = SUIVDEIN_Taxonomies::get_projet_progression($suivdein_projet->term_id);
                    ?>
                        <div class="projet-item">
                            <label class="projet-checkbox-label">
                                <input 
                                    type="checkbox" 
                                    name="client_projets[]" 
                                    value="<?php echo esc_attr($suivdein_projet->term_id); ?>"
                                    <?php checked(in_array($suivdein_projet->term_id, $selected_projets)); ?>
                                    class="projet-checkbox"
                                />
                                <strong><?php echo esc_html($suivdein_projet->name); ?></strong>
                            </label>
                            
                            <div class="projet-details">
                                <?php if ($suivdein_projet_meta['quota']) : ?>
                                    <div class="projet-quota">
                                        <small>
                                            <?php
                                            /* translators: %1$d = quota, %2$d = used, %3$.1f = percentage with one decimal */printf(
                                                esc_html__('Quota: %1$d interventions | Utilisé: %2$d (%3$.1f%%)', 'suivi-des-interventions'),
                                                (int) $progression['quota'],
                                                (int) $progression['used'],
                                                (float) $progression['percentage']
                                            );
                                            ?>
                                        </small>
                                        
                                        <?php 
                                        $color_class = 'quota-green';
                                        if ($progression['percentage'] > 80) {
                                            $color_class = 'quota-red';
                                        } elseif ($progression['percentage'] > 45) {
                                            $color_class = 'quota-blue';
                                        }
                                        ?>
                                        
                                        <div class="mini-progress-bar">
                                            <div class="mini-progress-fill <?php echo esc_attr($color_class); ?>" 
                                                 style="width: <?php echo esc_html(min(100, $progression['percentage'])); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($suivdein_projet_meta['date_expiration']) : ?>
                                    <div class="projet-expiration">
                                        <small>
                                            <strong><?php esc_html_e('Expiration:', 'suivi-des-interventions'); ?></strong>
                                            <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($suivdein_projet_meta['date_expiration']))); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($suivdein_projet_meta['projet_url']) : ?>
                                    <div class="projet-url">
                                        <small>
                                            <a href="<?php echo esc_url($suivdein_projet_meta['projet_url']); ?>" target="_blank" rel="noopener">
                                                <?php esc_attr_e('Voir le site', 'suivi-des-interventions'); ?> ↗
                                            </a>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <p class="description">
                    <?php esc_attr_e('Sélectionnez les projets que ce client peut voir. Le client ne pourra consulter que les interventions liées à ces projets.', 'suivi-des-interventions'); ?>
                </p>
                
                <div class="client-projets-actions">
                    <button type="button" id="select-all-projets" class="button">
                        <?php esc_attr_e('Tout sélectionner', 'suivi-des-interventions'); ?>
                    </button>
                    <button type="button" id="deselect-all-projets" class="button">
                        <?php esc_attr_e('Tout désélectionner', 'suivi-des-interventions'); ?>
                    </button>
                </div>
                
            <?php else : ?>
                <p class="no-projets">
                    <?php esc_attr_e('Aucun projet disponible.', 'suivi-des-interventions'); ?>
                    <a href="<?php echo esc_url(admin_url('edit-tags.php?taxonomy=projet&post_type=intervention')); ?>">
                        <?php esc_attr_e('Créez d\'abord des projets', 'suivi-des-interventions'); ?> →
                    </a>
                </p>
            <?php endif; ?>
        </td>
    </tr>
</table>

<?php if (!empty($selected_projets)) : ?>
<h3><?php esc_attr_e('Résumé des accès', 'suivi-des-interventions'); ?></h3>
<div class="client-access-summary">
    <div class="access-stats">
        <?php 
        $stats = SUIVDEIN_Client_Restrictions::get_client_stats($user->ID);
        ?>
        <div class="stat-item">
            <span class="stat-number"><?php echo esc_html($stats['projets_count']); ?></span>
            <span class="stat-label"><?php esc_attr_e('Projet(s) autorisé(s)', 'suivi-des-interventions'); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo esc_html($stats['total_interventions']); ?></span>
            <span class="stat-label"><?php esc_attr_e('Intervention(s) visible(s)', 'suivi-des-interventions'); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo esc_html($stats['interventions_terminees']); ?></span>
            <span class="stat-label"><?php esc_attr_e('Terminée(s)', 'suivi-des-interventions'); ?></span>
        </div>
    </div>
</div>
<?php endif; ?>