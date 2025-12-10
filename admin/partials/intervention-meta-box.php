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
$suivdein_intervention_meta = SUIVDEIN_Post_Types::get_intervention_meta($post->ID);
$suivdein_date_intervention = $suivdein_intervention_meta['date_intervention'];
$suivdein_intervention_terminee = $suivdein_intervention_meta['intervention_terminee'];
$suivdein_description = $suivdein_intervention_meta['description'];

// Nonce pour la sécurité
wp_nonce_field('intervention_meta_box', 'intervention_meta_nonce');
?>

<table class="form-table" role="presentation">
    <tbody>
        <tr>
            <th scope="row">
                <label for="date_intervention">
                    <?php esc_html_e('Date d\'intervention', 'suivi-des-interventions'); ?>
                    <span class="description"><?php esc_html_e('(requis)', 'suivi-des-interventions'); ?></span>
                </label>
            </th>
            <td>
                <input 
                    type="date" 
                    id="date_intervention" 
                    name="date_intervention" 
                    value="<?php echo esc_attr($suivdein_date_intervention); ?>"
                    class="regular-text"
                    required
                />
                <p class="description">
                    <?php esc_html_e('Date à laquelle l\'intervention a été ou sera réalisée.', 'suivi-des-interventions'); ?>
                </p>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <?php esc_html_e('Statut de l\'intervention', 'suivi-des-interventions'); ?>
            </th>
            <td>
                <fieldset>
                    <legend class="screen-reader-text">
                        <span><?php esc_html_e('Statut de l\'intervention', 'suivi-des-interventions'); ?></span>
                    </legend>
                    <label for="intervention_terminee">
                        <input 
                            type="checkbox" 
                            id="intervention_terminee" 
                            name="intervention_terminee" 
                            value="1" 
                            <?php checked($suivdein_intervention_terminee, '1'); ?>
                        />
                        <?php esc_html_e('Intervention terminée', 'suivi-des-interventions'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Cochez cette case si l\'intervention est terminée. Cela comptera dans le quota du projet.', 'suivi-des-interventions'); ?>
                    </p>
                </fieldset>
            </td>
        </tr>
        
        <tr>
            <th scope="row">
                <label for="intervention_description">
                    <?php esc_html_e('Description détaillée', 'suivi-des-interventions'); ?>
                </label>
            </th>
            <td>
                <?php
                wp_editor(
                    $suivdein_description,
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
                    <?php esc_html_e('Description détaillée de l\'intervention (optionnel).', 'suivi-des-interventions'); ?>
                </p>
            </td>
        </tr>
    </tbody>
</table>