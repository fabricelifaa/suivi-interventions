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
$client_company = get_user_meta($user->ID, 'client_company', true);
$client_phone = get_user_meta($user->ID, 'client_contact_phone', true);
?>

<h3><?php esc_attr_e('Informations Client', 'suivi-interventions'); ?></h3>

<table class="form-table">
    <tr>
        <th>
            <label for="client_company"><?php esc_attr_e('Société/Organisation', 'suivi-interventions'); ?></label>
        </th>
        <td>
            <input 
                type="text" 
                name="client_company" 
                id="client_company" 
                value="<?php echo esc_attr($client_company); ?>" 
                class="regular-text" 
            />
            <p class="description"><?php esc_attr_e('Nom de la société ou organisation du client.', 'suivi-interventions'); ?></p>
        </td>
    </tr>
    
    <tr>
        <th>
            <label for="client_contact_phone"><?php esc_attr_e('Téléphone de contact', 'suivi-interventions'); ?></label>
        </th>
        <td>
            <input 
                type="tel" 
                name="client_contact_phone" 
                id="client_contact_phone" 
                value="<?php echo esc_attr($client_phone); ?>" 
                class="regular-text" 
            />
            <p class="description"><?php esc_attr_e('Numéro de téléphone de contact du client.', 'suivi-interventions'); ?></p>
        </td>
    </tr>
</table>

<h3><?php esc_attr_e('Projets autorisés', 'suivi-interventions'); ?></h3>

<table class="form-table">
    <tr>
        <th>
            <label><?php esc_attr_e('Projets', 'suivi-interventions'); ?></label>
        </th>
        <td>
            <?php wp_nonce_field('save_client_projets', 'client_projets_nonce'); ?>
            
            <?php if ($projets && !is_wp_error($projets)) : ?>
                <div class="client-projets-list">
                    <?php foreach ($projets as $projet) : 
                        $projet_meta = SI_Taxonomies::get_projet_meta($projet->term_id);
                        $progression = SI_Taxonomies::get_projet_progression($projet->term_id);
                    ?>
                        <div class="projet-item">
                            <label class="projet-checkbox-label">
                                <input 
                                    type="checkbox" 
                                    name="client_projets[]" 
                                    value="<?php echo $projet->term_id; ?>"
                                    <?php checked(in_array($projet->term_id, $selected_projets)); ?>
                                    class="projet-checkbox"
                                />
                                <strong><?php echo esc_html($projet->name); ?></strong>
                            </label>
                            
                            <div class="projet-details">
                                <?php if ($projet_meta['quota']) : ?>
                                    <div class="projet-quota">
                                        <small>
                                            <?php
                                            /* translators: %1$d = quota, %2$d = used, %3$.1f = percentage with one decimal */printf(
                                                __('Quota: %1$d interventions | Utilisé: %2$d (%3$.1f%%)', 'suivi-interventions'),
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
                                            <div class="mini-progress-fill <?php echo $color_class; ?>" 
                                                 style="width: <?php echo min(100, $progression['percentage']); ?>%"></div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($projet_meta['date_expiration']) : ?>
                                    <div class="projet-expiration">
                                        <small>
                                            <strong><?php esc_attr_e('Expiration:', 'suivi-interventions'); ?></strong>
                                            <?php echo date_i18n(get_option('date_format'), strtotime($projet_meta['date_expiration'])); ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($projet_meta['projet_url']) : ?>
                                    <div class="projet-url">
                                        <small>
                                            <a href="<?php echo esc_url($projet_meta['projet_url']); ?>" target="_blank" rel="noopener">
                                                <?php esc_attr_e('Voir le site', 'suivi-interventions'); ?> ↗
                                            </a>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <p class="description">
                    <?php esc_attr_e('Sélectionnez les projets que ce client peut voir. Le client ne pourra consulter que les interventions liées à ces projets.', 'suivi-interventions'); ?>
                </p>
                
                <div class="client-projets-actions">
                    <button type="button" id="select-all-projets" class="button">
                        <?php esc_attr_e('Tout sélectionner', 'suivi-interventions'); ?>
                    </button>
                    <button type="button" id="deselect-all-projets" class="button">
                        <?php esc_attr_e('Tout désélectionner', 'suivi-interventions'); ?>
                    </button>
                </div>
                
            <?php else : ?>
                <p class="no-projets">
                    <?php esc_attr_e('Aucun projet disponible.', 'suivi-interventions'); ?>
                    <a href="<?php echo admin_url('edit-tags.php?taxonomy=projet&post_type=intervention'); ?>">
                        <?php esc_attr_e('Créez d\'abord des projets', 'suivi-interventions'); ?> →
                    </a>
                </p>
            <?php endif; ?>
        </td>
    </tr>
</table>

<?php if (!empty($selected_projets)) : ?>
<h3><?php esc_attr_e('Résumé des accès', 'suivi-interventions'); ?></h3>
<div class="client-access-summary">
    <div class="access-stats">
        <?php 
        $stats = SI_Client_Restrictions::get_client_stats($user->ID);
        ?>
        <div class="stat-item">
            <span class="stat-number"><?php echo $stats['projets_count']; ?></span>
            <span class="stat-label"><?php esc_attr_e('Projet(s) autorisé(s)', 'suivi-interventions'); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo $stats['total_interventions']; ?></span>
            <span class="stat-label"><?php esc_attr_e('Intervention(s) visible(s)', 'suivi-interventions'); ?></span>
        </div>
        <div class="stat-item">
            <span class="stat-number"><?php echo $stats['interventions_terminees']; ?></span>
            <span class="stat-label"><?php esc_attr_e('Terminée(s)', 'suivi-interventions'); ?></span>
        </div>
    </div>
</div>
<?php endif; ?>


<style>
.client-projets-list {
    max-height: 400px;
    overflow-y: auto;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 10px;
    background: #fafafa;
}

.projet-item {
    margin-bottom: 15px;
    padding: 12px;
    background: white;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
    transition: all 0.2s ease;
}

.projet-item:hover {
    border-color: #0073aa;
    box-shadow: 0 2px 8px rgba(0,115,170,0.1);
}

.projet-checkbox-label {
    display: flex;
    align-items: center;
    font-weight: 600;
    margin-bottom: 8px;
    cursor: pointer;
}

.projet-checkbox {
    margin-right: 8px !important;
    margin-top: 0 !important;
}

.projet-details {
    margin-left: 24px;
    padding-top: 5px;
    border-top: 1px solid #f0f0f0;
    color: #666;
}

.projet-details > div {
    margin-bottom: 4px;
}

.mini-progress-bar {
    width: 100px;
    height: 8px;
    background-color: #f0f0f0;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 2px;
    display: inline-block;
    vertical-align: middle;
}

.mini-progress-fill {
    height: 100%;
    transition: width 0.3s ease;
}

.quota-green { background-color: #4CAF50; }
.quota-blue { background-color: #2196F3; }
.quota-red { background-color: #f44336; }

.client-projets-actions {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #ddd;
}

.client-projets-actions .button {
    margin-right: 10px;
}

.no-projets {
    padding: 20px;
    text-align: center;
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 4px;
    color: #856404;
}

.no-projets a {
    color: #0073aa;
    text-decoration: none;
    font-weight: 600;
}

.client-access-summary {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin-top: 15px;
}

.access-stats {
    display: flex;
    gap: 30px;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
    flex: 1;
    min-width: 120px;
}

.stat-number {
    display: block;
    font-size: 24px;
    font-weight: 700;
    color: #0073aa;
    line-height: 1;
}

.stat-label {
    display: block;
    font-size: 12px;
    color: #666;
    margin-top: 4px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

@media (max-width: 768px) {
    .access-stats {
        flex-direction: column;
        gap: 15px;
    }
    
    .stat-item {
        min-width: auto;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Boutons de sélection/désélection
    $('#select-all-projets').click(function() {
        $('.projet-checkbox').prop('checked', true);
        updateSelectionCount();
    });
    
    $('#deselect-all-projets').click(function() {
        $('.projet-checkbox').prop('checked', false);
        updateSelectionCount();
    });
    
    // Mise à jour du compteur de sélection
    $('.projet-checkbox').change(function() {
        updateSelectionCount();
    });
    
    function updateSelectionCount() {
        var checkedCount = $('.projet-checkbox:checked').length;
        var totalCount = $('.projet-checkbox').length;
        
        var $counter = $('#selection-counter');
        if (!$counter.length) {
            $('.client-projets-actions').append('<p id="selection-counter"></p>');
            $counter = $('#selection-counter');
        }
        
        if (checkedCount === 0) {
            $counter.html('<em style="color: #d63384;">Aucun projet sélectionné - le client ne pourra voir aucune intervention.</em>');
        } else {
            $counter.html('<strong>' + checkedCount + '</strong> projet(s) sélectionné(s) sur <strong>' + totalCount + '</strong>');
        }
    }
    
    // Initialiser le compteur
    updateSelectionCount();
    
    // Validation côté client
    $('form').submit(function(e) {
        var checkedCount = $('.projet-checkbox:checked').length;
        if (checkedCount === 0) {
            var confirm = window.confirm('<?php esc_attr_e("Aucun projet n\'est sélectionné. Le client ne pourra voir aucune intervention. Voulez-vous continuer ?", "suivi-interventions"); ?>');
            if (!confirm) {
                e.preventDefault();
                return false;
            }
        }
    });
});
</script>