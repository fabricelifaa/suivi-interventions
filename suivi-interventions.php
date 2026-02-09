<?php
/**
 * Plugin Name: Suivi des Interventions
 * Plugin URI: https://github.com/fabricelifaa/suivi-interventions/releases
 * Description: 📋 Professional plugin for tracking interventions and updates
 * Version: 1.2.3
 * Author: FAB2DEV
 * Text Domain: suivi-des-interventions
 * Domain Path: /languages
 * License: GPLv2
 */

// Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
if (!defined('SUIVDEIN_VERSION')) {
    define('SUIVDEIN_VERSION', '1.0.0');
}

if (!defined('SUIVDEIN_PLUGIN_DIR')) {
    define('SUIVDEIN_PLUGIN_DIR', plugin_dir_path(__FILE__));
}

if (!defined('SUIVDEIN_PLUGIN_URL')) {
    define('SUIVDEIN_PLUGIN_URL', plugin_dir_url(__FILE__));
}

if (!defined('SUIVI_INTERVENTIONS_PLUGIN_BASENAME')) {
    define('SUIVI_INTERVENTIONS_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

/**
 * Charger les fichiers requis
 */
function suivdein_load_files() {
    // Charger la classe principale
    require_once SUIVDEIN_PLUGIN_DIR . 'includes/functions-helper.php';
    require_once SUIVDEIN_PLUGIN_DIR . 'includes/class-suivi-interventions.php';
}

/**
 * Initialiser le plugin
 */
function suivdein_init() {
    suivdein_load_files();
    
    // Instancier la classe principale
    $plugin = Suivi_Interventions::get_instance();
    $plugin->run();
}

/**
 * Activation du plugin
 */
function suivdein_activate() {
    suivdein_load_files();
    $plugin = Suivi_Interventions::get_instance();
    $plugin->activate();
}

/**
 * Désactivation du plugin
 */
function suivdein_deactivate() {
    suivdein_load_files();
    $plugin = Suivi_Interventions::get_instance();
    $plugin->deactivate();
}

// Hooks d'activation et désactivation
register_activation_hook(__FILE__, 'suivdein_activate');
register_deactivation_hook(__FILE__, 'suivdein_deactivate');

// Initialiser le plugin - PRIORITÉ HAUTE pour s'assurer du chargement
add_action('init', 'suivdein_init', 2);