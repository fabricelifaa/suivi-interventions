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
$is_edit_mode = isset($term) && $term;
$quota = $is_edit_mode ? get_term_meta($term->term_id, 'quota', true) : '';
$date_expiration = $is_edit_mode ? get_term_meta($term->term_id, 'date_expiration', true) : '';
$client_info = $is_edit_mode ? get_term_meta($term->term_id, 'client_info', true) : '';
$projet_url = $is_edit_mode ? get_term_meta($term->term_id, 'projet_url', true) : '';

if ($is_edit_mode) {
    // Mode édition - utiliser les tr
    ?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="quota"><?php esc_attr_e('Quota d\'interventions', 'suivi-interventions'); ?></label>
        </th>
        <td>
            <input type="number" name="quota" id="quota" value="<?php echo esc_attr($quota); ?>" min="0" class="regular-text" />
            <p class="description"><?php esc_attr_e('Nombre maximum d\'interventions autorisées pour ce projet.', 'suivi-interventions'); ?></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="date_expiration"><?php esc_attr_e('Date d\'expiration du quota', 'suivi-interventions'); ?></label>
        </th>
        <td>
            <input type="date" name="date_expiration" id="date_expiration" value="<?php echo esc_attr($date_expiration); ?>" class="regular-text" />
            <p class="description"><?php esc_attr_e('Date à partir de laquelle le quota sera réinitialisé (optionnel).', 'suivi-interventions'); ?></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="projet_url"><?php esc_html_e('URL du projet', 'suivi-interventions'); ?></label>
        </th>
        <td>
            <input type="url" name="projet_url" id="projet_url" value="<?php echo esc_attr($projet_url); ?>" class="regular-text" />
            <p class="description"><?php esc_attr_e('URL du site web ou du projet (optionnel).', 'suivi-interventions'); ?></p>
        </td>
    </tr>
    
    <tr class="form-field">
        <th scope="row" valign="top">
            <label for="client_info"><?php esc_html_e('Informations client', 'suivi-interventions'); ?></label>
        </th>
        <td>
            <textarea name="client_info" id="client_info" rows="4" cols="50" class="large-text"><?php echo esc_textarea($client_info); ?></textarea>
            <p class="description"><?php esc_attr_e('Informations sur le client : contact, notes spéciales, etc. (optionnel).', 'suivi-interventions'); ?></p>
        </td>
    </tr>
    
    <?php if ($quota): ?>
    <tr class="form-field">
        <th scope="row" valign="top">
            <?php esc_html_e('Progression actuelle', 'suivi-interventions'); ?>
        </th>
        <td>
            <?php
            $progression = SI_Taxonomies::get_projet_progression($term->term_id);
            $percentage = $progression['percentage'];
            $color_class = 'quota-green';
            if ($percentage > 80) {
                $color_class = 'quota-red';
            } elseif ($percentage > 45) {
                $color_class = 'quota-blue';
            }
            ?>
            <div class="quota-progress-display">
                <div class="quota-progress-bar-large">
                    <div class="quota-progress-fill-large <?php esc_attr_e($color_class); ?>" style="width: <?php echo min(100, $percentage); ?>%"></div>
                </div>
                <p class="quota-stats">
                    <strong><?php /* translators: %1$d: used, %2$d: quota */ printf(_e('%1$d interventions terminées sur %2$d autorisées', 'suivi-interventions'), $progression['used'], $progression['quota']); ?></strong><br>
                    <span class="quota-details">
                        
                        <?php /* translators: %1$d: remaining, %2$.1f: percentage  */ printf(__('Restant : %1$d | Pourcentage utilisé : %2$.1f%%', 'suivi-interventions'), $progression['remaining'], $percentage); ?>
                    </span>
                </p>
            </div>
        </td>
    </tr>
    <?php endif; ?>
    
    <style>
    .quota-progress-display {
        max-width: 300px;
    }
    .quota-progress-bar-large {
        width: 100%;
        height: 25px;
        background-color: #f0f0f0;
        border-radius: 12px;
        overflow: hidden;
        margin-bottom: 10px;
        border: 1px solid #ddd;
    }
    .quota-progress-fill-large {
        height: 100%;
        transition: width 0.3s ease;
    }
    .quota-green { background: linear-gradient(90deg, #4CAF50, #45a049); }
    .quota-blue { background: linear-gradient(90deg, #2196F3, #1976d2); }
    .quota-red { background: linear-gradient(90deg, #f44336, #d32f2f); }
    .quota-stats {
        margin: 0;
        line-height: 1.4;
    }
    .quota-details {
        color: #666;
        font-size: 13px;
    }
    </style>
    
    <?php
} else {
    // Mode ajout - utiliser les div
    ?>
    <div class="form-field">
        <label for="quota"><?php esc_attr_e('Quota d\'interventions', 'suivi-interventions'); ?></label>
        <input type="number" name="quota" id="quota" min="0" class="regular-text" />
        <p><?php esc_attr_e('Nombre maximum d\'interventions autorisées pour ce projet.', 'suivi-interventions'); ?></p>
    </div>
    
    <div class="form-field">
        <label for="date_expiration"><?php esc_attr_e('Date d\'expiration du quota', 'suivi-interventions'); ?></label>
        <input type="date" name="date_expiration" id="date_expiration" class="regular-text" />
        <p><?php esc_attr_e('Date à partir de laquelle le quota sera réinitialisé (optionnel).', 'suivi-interventions'); ?></p>
    </div>
    
    <div class="form-field">
        <label for="projet_url"><?php esc_attr_e('URL du projet', 'suivi-interventions'); ?></label>
        <input type="url" name="projet_url" id="projet_url" class="regular-text" placeholder="https://" />
        <p><?php esc_attr_e('URL du site web ou du projet (optionnel).', 'suivi-interventions'); ?></p>
    </div>
    
    <div class="form-field">
        <label for="client_info"><?php esc_attr_e('Informations client', 'suivi-interventions'); ?></label>
        <textarea name="client_info" id="client_info" rows="4" cols="50"></textarea>
        <p><?php esc_attr_e('Informations sur le client : contact, notes spéciales, etc. (optionnel).', 'suivi-interventions'); ?></p>
    </div>
    <?php
}
?>

<script>
jQuery(document).ready(function($) {
    // Validation du quota
    $('#quota').on('input', function() {
        var value = parseInt($(this).val());
        if (value < 0) {
            $(this).val(0);
        }
    });
    
    // Validation de l'URL
    $('#projet_url').on('blur', function() {
        var url = $(this).val();
        if (url && !url.match(/^https?:\/\//)) {
            $(this).val('https://' + url);
        }
    });
    
    // Prévisualisation de l'expiration
    $('#date_expiration').on('change', function() {
        var selectedDate = new Date($(this).val());
        var today = new Date();
        var $preview = $('#expiration-preview');
        
        if (!$preview.length) {
            $(this).after('<p id="expiration-preview" style="margin-top: 5px; font-size: 12px;"></p>');
            $preview = $('#expiration-preview');
        }
        
        if (selectedDate && selectedDate > today) {
            var diffTime = Math.abs(selectedDate - today);
            var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            $preview.html('<strong style="color: green;">✓ Expire dans ' + diffDays + ' jour(s)</strong>');
        } else if (selectedDate && selectedDate <= today) {
            $preview.html('<strong style="color: red;">⚠ Cette date est déjà passée</strong>');
        } else {
            $preview.html('');
        }
    });
});
</script>