<?php
/**
 * Plugin Name: AI Text-to-Speech
 * Description: Generates a text-to-speech recording of posts.
 * Version: 1.0.0
 * Author: Elliot Sowersby, RelyWP
 */

// Define the path for the uploads
define('AI_TTS_UPLOAD_DIR', WP_CONTENT_DIR . '/uploads/ai-tts/');

// Includes
include(plugin_dir_path(__FILE__) . 'inc/generate.php');
include(plugin_dir_path(__FILE__) . 'inc/save.php');
include(plugin_dir_path(__FILE__) . 'inc/delete.php');
include(plugin_dir_path(__FILE__) . 'inc/player.php');
include(plugin_dir_path(__FILE__) . 'inc/meta-box.php');
include(plugin_dir_path(__FILE__) . 'inc/options.php');

// Activation hook to create the upload directory
register_activation_hook(__FILE__, 'ai_tts_create_upload_dir');
function ai_tts_create_upload_dir() {
    if (!file_exists(AI_TTS_UPLOAD_DIR)) {
        mkdir(AI_TTS_UPLOAD_DIR, 0755, true);
    }
}

// Enqueue JavaScript
add_action('admin_enqueue_scripts', 'ai_tts_enqueue_scripts');
function ai_tts_enqueue_scripts() {
    wp_enqueue_script('ai-tts-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0', true);
}