<?php
/**
 * MU-Plugin: Auto-activate Imporlan Premium Content plugin
 *
 * Must-use plugins run automatically without needing database activation.
 * This ensures the Imporlan plugin is always loaded and creates the premium post on first run.
 */

if (!defined('ABSPATH')) exit;

// Load the plugin manually if it exists and isn't already active
add_action('plugins_loaded', function () {
    $plugin_file = WP_CONTENT_DIR . '/plugins/imporlan-premium-content/imporlan-premium-content.php';

    if (file_exists($plugin_file)) {
        // Check if plugin is already loaded via normal activation
        if (!class_exists('Imporlan_Premium_Content')) {
            require_once $plugin_file;
        }

        // Ensure activation hook runs (creates the premium post) on first load
        $activated = get_option('ipc_plugin_activated', false);
        if (!$activated) {
            // Trigger the activation logic
            $instance = Imporlan_Premium_Content::get_instance();
            $instance->activate();
            update_option('ipc_plugin_activated', true);
        }
    }
}, 0);

// Also ensure plugin appears in active plugins list for admin UI
add_action('admin_init', function () {
    $plugin = 'imporlan-premium-content/imporlan-premium-content.php';
    $active_plugins = get_option('active_plugins', []);

    if (!in_array($plugin, $active_plugins)) {
        $active_plugins[] = $plugin;
        update_option('active_plugins', $active_plugins);
    }
}, 1);
