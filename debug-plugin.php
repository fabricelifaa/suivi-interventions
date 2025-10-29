<?php
/**
 * FICHIER DE DÉBOGAGE TEMPORAIRE
 * À placer dans le dossier du plugin pour diagnostiquer le problème
 * SUPPRIMER APRÈS RÉSOLUTION
 */

// Ajouter ceci temporairement dans functions.php ou comme plugin de test
add_action('admin_init', 'si_debug_plugin_status');

function si_debug_plugin_status() {
    if (!current_user_can('administrator')) {
        return;
    }
    
    // Vérifier si le post type est enregistré
    $post_types = get_post_types();
    $intervention_registered = isset($post_types['intervention']);
    
    // Vérifier si la taxonomie est enregistrée
    $taxonomies = get_taxonomies();
    $projet_registered = isset($taxonomies['projet']);
    
    // Vérifier les capacités de l'utilisateur
    $current_user = wp_get_current_user();
    $has_caps = array(
        'edit_interventions' => current_user_can('edit_interventions'),
        'edit_intervention' => current_user_can('edit_intervention'),
        'read_interventions' => current_user_can('read_interventions'),
    );
    
    // Vérifier si les classes existent
    $classes_exist = array(
        'SI_Post_Types' => class_exists('SI_Post_Types'),
        'SI_Taxonomies' => class_exists('SI_Taxonomies'),
        'Suivi_Interventions' => class_exists('Suivi_Interventions')
    );
    
    // Afficher le debug dans l'admin
    add_action('admin_notices', function() use ($intervention_registered, $projet_registered, $has_caps, $classes_exist) {
        echo '<div class="notice notice-info">';
        echo '<h3>DEBUG Plugin Suivi Interventions</h3>';
        echo '<p><strong>Post type "intervention" enregistré:</strong> ' . ($intervention_registered ? 'OUI' : 'NON') . '</p>';
        echo '<p><strong>Taxonomie "projet" enregistrée:</strong> ' . ($projet_registered ? 'OUI' : 'NON') . '</p>';
        echo '<p><strong>Capacités utilisateur:</strong></p>';
        echo '<ul>';
        foreach ($has_caps as $cap => $has_cap) {
            echo '<li>' . $cap . ': ' . ($has_cap ? 'OUI' : 'NON') . '</li>';
        }
        echo '</ul>';
        echo '<p><strong>Classes chargées:</strong></p>';
        echo '<ul>';
        foreach ($classes_exist as $class => $exists) {
            echo '<li>' . $class . ': ' . ($exists ? 'OUI' : 'NON') . '</li>';
        }
        echo '</ul>';
        
        // Vérifier les hooks
        global $wp_filter;
        $init_hooks = isset($wp_filter['init']) ? count($wp_filter['init']) : 0;
        echo '<p><strong>Hooks "init" enregistrés:</strong> ' . $init_hooks . '</p>';
        
        echo '</div>';
    });
}

// Test direct d'enregistrement du post type
add_action('init', 'si_force_register_post_type', 5);
function si_force_register_post_type() {
    if (!post_type_exists('intervention')) {
        register_post_type('intervention', array(
            'labels' => array(
                'name' => 'Interventions (Debug)',
                'singular_name' => 'Intervention',
                'menu_name' => 'Interventions Debug'
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_icon' => 'dashicons-clipboard',
            'supports' => array('title', 'editor')
        ));
        
        register_taxonomy('projet', array('intervention'), array(
            'labels' => array(
                'name' => 'Projets (Debug)',
                'singular_name' => 'Projet'
            ),
            'show_ui' => true,
            'show_admin_column' => true
        ));
    }
}
?>