<?php
/**
 * Plugin Name: Suivi des Interventions
 * Plugin URI: https://github.com/fabricelifaa/suivi-interventions/releases
 * Description: 📋 Plugin professionnel de suivi des interventions et mises à jour
 * Version: 1.1.0
 * Author: FAB2DEV
 * Author URI: https://fab2dev.com
 * Text Domain: suivi-interventions
 * Domain Path: /languages
 * License: GPLv2
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
if (!defined('SUIVI_INTERVENTIONS_VERSION')) {
    define('SUIVI_INTERVENTIONS_VERSION', '1.0.0');
}

if (!defined('SUIVI_INTERVENTIONS_PLUGIN_DIR')) {
    define('SUIVI_INTERVENTIONS_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('SUIVI_INTERVENTIONS_PLUGIN_URL')) {
    define('SUIVI_INTERVENTIONS_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('SUIVI_INTERVENTIONS_PLUGIN_BASENAME')) {
    define('SUIVI_INTERVENTIONS_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

/**
 * Charger les fichiers requis
 */
function suivi_interventions_load_files() {
    // Charger la classe principale
    require_once SUIVI_INTERVENTIONS_PLUGIN_DIR . 'includes/functions-helper.php';
    require_once SUIVI_INTERVENTIONS_PLUGIN_DIR . 'includes/class-suivi-interventions.php';
}

/**
 * Initialiser le plugin
 */
function suivi_interventions_init() {
    suivi_interventions_load_files();
    
    // Instancier la classe principale
    $plugin = Suivi_Interventions::get_instance();
    $plugin->run();
}

/**
 * Activation du plugin
 */
function suivi_interventions_activate() {
    suivi_interventions_load_files();
    $plugin = Suivi_Interventions::get_instance();
    $plugin->activate();
}

/**
 * Désactivation du plugin
 */
function suivi_interventions_deactivate() {
    suivi_interventions_load_files();
    $plugin = Suivi_Interventions::get_instance();
    $plugin->deactivate();
}

// Hooks d'activation et désactivation
register_activation_hook(__FILE__, 'suivi_interventions_activate');
register_deactivation_hook(__FILE__, 'suivi_interventions_deactivate');

// Initialiser le plugin - PRIORITÉ HAUTE pour s'assurer du chargement
add_action('init', 'suivi_interventions_init', 2);