<?php
/**
 * Template pour les champs personnalisés de la taxonomie projet
 * 
 * @package SuiviInterventions
 * @since 1.0.0
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Déterminer si on est en mode édition ou ajout
$suivdein_is_edit_mode = isset($term) && $term;
$suivdein_quota = $suivdein_is_edit_mode ? get_term_meta($term->term_id, 'quota', true) : '';
$suivdein_date_expiration = $suivdein_is_edit_mode ? get_term_meta($term->term_id, 'date_expiration', true) : '';
$suivdein_client_info = $suivdein_is_edit_mode ? get_term_meta($term->term_id, 'client_info', true) : '';
$suivdein_projet_url = $suivdein_is_edit_mode ? get_term_meta($term->term_id, 'projet_url', true) : '';

if ($suivdein_is_edit_mode) {
    // Mode édition - utiliser les tr
    ?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="quota"><?php esc_attr_e('Quota d\'interventions', 'suivi-des-interventions'); ?></label>
        </th>
        <td>
            <input type="number" name="quota" id="quota" value="<?php echo esc_attr($suivdein_quota); ?>" min="0" class="regular-text" />
            <p class="description"><?php esc_attr_e('Nombre maximum d\'interventions autorisées pour ce projet.', 'suivi-des-interventions'); ?></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="date_expiration"><?php esc_attr_e('Date d\'expiration du quota', 'suivi-des-interventions'); ?></label>
        </th>
        <td>
            <input type="date" name="date_expiration" id="date_expiration" value="<?php echo esc_attr($suivdein_date_expiration); ?>" class="regular-text" />
            <p class="description"><?php esc_attr_e('Date à partir de laquelle le quota sera réinitialisé (optionnel).', 'suivi-des-interventions'); ?></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="projet_url"><?php esc_html_e('URL du projet', 'suivi-des-interventions'); ?></label>
        </th>
        <td>
            <input type="url" name="projet_url" id="projet_url" value="<?php echo esc_attr($suivdein_projet_url); ?>" class="regular-text" />
            <p class="description"><?php esc_attr_e('URL du site web ou du projet (optionnel).', 'suivi-des-interventions'); ?></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="client_info"><?php esc_html_e('Informations client', 'suivi-des-interventions'); ?></label>
        </th>
        <td>
            <textarea name="client_info" id="client_info" rows="4" cols="50" class="large-text"><?php echo esc_textarea($suivdein_client_info); ?></textarea>
            <p class="description"><?php esc_attr_e('Informations sur le client : contact, notes spéciales, etc. (optionnel).', 'suivi-des-interventions'); ?></p>
        </td>
    </tr>
    
    <?php if ($suivdein_quota): ?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <?php esc_html_e('Progression actuelle', 'suivi-des-interventions'); ?>
        </th>
        <td>
            <?php
            $suivdein_progression = SUIVDEIN_Taxonomies::get_projet_progression($term->term_id);
            $suivdein_percentage = $suivdein_progression['percentage'];
            $color_class = 'quota-green';
            if ($suivdein_percentage > 80) {
                $color_class = 'quota-red';
            } elseif ($suivdein_percentage > 45) {
                $color_class = 'quota-blue';
            }
            ?>
            <div class="quota-progress-display">
                <div class="quota-progress-bar-large">
                        <div class="quota-progress-fill-large <?php echo esc_attr( $color_class ); ?>" style="width: <?php echo esc_html(min(100, $suivdein_percentage)); ?>%"></div>
                    </div>
                    <p class="quota-stats">
                        <?php /* translators: %1$d: used, %2$d: quota */ ?>
                        <strong><?php printf( esc_html__('%1$d interventions terminées sur %2$d autorisées', 'suivi-des-interventions'), (int) $suivdein_progression['used'], (int) $suivdein_progression['quota'] ); ?></strong><br>
                        <span class="quota-details">
                        
                            <?php /* translators: %1$d: remaining, %2$.1f: percentage  */ printf( esc_html__('Restant : %1$d | Pourcentage utilisé : %2$.1f%%', 'suivi-des-interventions'), (int) $suivdein_progression['remaining'], (float) $suivdein_percentage ); ?>
                        </span>
                    </p>
            </div>
        </td>
    </tr>
    <?php endif; ?>
    
    <?php
} else {
    // Mode ajout - utiliser les div
    ?>
    <div class="form-field">
        <label for="quota"><?php esc_attr_e('Quota d\'interventions', 'suivi-des-interventions'); ?></label>
        <input type="number" name="quota" id="quota" min="0" class="regular-text" />
        <p><?php esc_attr_e('Nombre maximum d\'interventions autorisées pour ce projet.', 'suivi-des-interventions'); ?></p>
    </div>
    
    <div class="form-field">
        <label for="date_expiration"><?php esc_attr_e('Date d\'expiration du quota', 'suivi-des-interventions'); ?></label>
        <input type="date" name="date_expiration" id="date_expiration" class="regular-text" />
        <p><?php esc_attr_e('Date à partir de laquelle le quota sera réinitialisé (optionnel).', 'suivi-des-interventions'); ?></p>
    </div>
    
    <div class="form-field">
        <label for="projet_url"><?php esc_attr_e('URL du projet', 'suivi-des-interventions'); ?></label>
        <input type="url" name="projet_url" id="projet_url" class="regular-text" placeholder="https://" />
        <p><?php esc_attr_e('URL du site web ou du projet (optionnel).', 'suivi-des-interventions'); ?></p>
    </div>
    
    <div class="form-field">
        <label for="client_info"><?php esc_attr_e('Informations client', 'suivi-des-interventions'); ?></label>
        <textarea name="client_info" id="client_info" rows="4" cols="50"></textarea>
        <p><?php esc_attr_e('Informations sur le client : contact, notes spéciales, etc. (optionnel).', 'suivi-des-interventions'); ?></p>
    </div>
    <?php
}
?>