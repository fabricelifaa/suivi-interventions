<?php
/**
 * Template pour la meta box des interventions
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les données existantes
$intervention_meta = SI_Post_Types::get_intervention_meta($post->ID);
$date_intervention = $intervention_meta['date_intervention'];
$intervention_terminee = $intervention_meta['intervention_terminee'];
$description = $intervention_meta['description'];

// Nonce pour la sécurité
wp_nonce_field('intervention_meta_box', 'intervention_meta_nonce');
?>

<table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row">
                <label for="date_intervention">
                    <?php esc_html_e('Date d\'intervention', 'suivi-interventions'); ?>
                    <span class="description"><?php esc_html_e('(requis)', 'suivi-interventions'); ?></span>
                </label>
            </th>
            <td>
                <input 
                    type="date" 
                    id="date_intervention" 
                    name="date_intervention" 
                    value="<?php echo esc_attr($date_intervention); ?>"
                    class="regular-text"
                    required
                />
                <p class="description">
                    <?php _e('Date à laquelle l\'intervention a été ou sera réalisée.', 'suivi-interventions'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <?php esc_html_e('Statut de l\'intervention', 'suivi-interventions'); ?>
            </th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e('Statut de l\'intervention', 'suivi-interventions'); ?></span>
                    </legend>
                    <label for="intervention_terminee">
                        <input 
                            type="checkbox" 
                            id="intervention_terminee" 
                            name="intervention_terminee" 
                            value="1" 
                            <?php checked($intervention_terminee, '1'); ?>
                        />
                        <?php esc_html_e('Intervention terminée', 'suivi-interventions'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Cochez cette case si l\'intervention est terminée. Cela comptera dans le quota du projet.', 'suivi-interventions'); ?>
                    </p>
                </fieldset>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="intervention_description">
                    <?php esc_html_e('Description détaillée', 'suivi-interventions'); ?>
                </label>
            </th>
            <td>
                <?php
                wp_editor(
                    $description,
                    'intervention_description',
                    array(
                        'textarea_rows' => 5,
                        'media_buttons' => false,
                        'teeny' => true,
                        'quicktags' => false
                    )
                );
                ?>
                <p class="description">
                    <?php esc_html_e('Description détaillée de l\'intervention (optionnel).', 'suivi-interventions'); ?>
                </p>
            </td>
        </tr>
    </tbody>
</table>

<script>
jQuery(document).ready(function($) {
    // Validation côté client
    $('#post').submit(function(e) {
        var dateIntervention = $('#date_intervention').val();
        if (!dateIntervention) {
            e.preventDefault();
            alert('<?php esc_html_e("La date d'intervention est requise.", "suivi-interventions"); ?>');
            $('#date_intervention').focus();
            return false;
        }
    });
    
    // Mettre à jour l'aperçu du statut
    $('#intervention_terminee').change(function() {
        var $status = $('.intervention-status-preview');
        if (!$status.length) {
            $('#intervention_terminee').parent().append('<p class="intervention-status-preview"></p>');
            $status = $('.intervention-status-preview');
        }
        
        if ($(this).is(':checked')) {
            $status.html('<strong style="color: green;">✓ <?php esc_html_e("Cette intervention sera comptabilisée dans le quota", "suivi-interventions"); ?></strong>');
        } else {
            $status.html('<strong style="color: orange;">⏳ <?php esc_html_e("Cette intervention ne sera pas comptabilisée dans le quota", "suivi-interventions"); ?></strong>');
        }
    }).trigger('change');
});
</script>

<style>
.form-table th {
    width: 200px;
    font-weight: 600;
}

.form-table .description {
    font-size: 13px;
    color: #666;
    margin-top: 5px;
    font-weight: normal;
}

.intervention-status-preview {
    margin-top: 8px !important;
    padding: 8px;
    border-radius: 4px;
    background-color: #f9f9f9;
}

#date_intervention {
    max-width: 200px;
}

.required-field {
    color: #d63384;
}
</style>