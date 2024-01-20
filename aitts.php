<?php
/**
* Plugin Name: AI Text-to-Speech
* Description: Generates a text-to-speech recording of posts.
* Version: 1.0.0
* Author: Elliot Sowersby, RelyWP
* Author URI: https://relywp.com
* License: GPLv3
* Text Domain: ai-text-to-speech
* Domain Path: /languages
*/

// Define the path for the uploads
define('AI_TTS_UPLOAD_DIR', WP_CONTENT_DIR . '/uploads/ai-text-to-speech/');

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
    // Redirect to the settings page only once on activation
    update_option('ai_tts_do_activation_redirect', true);
}

// Redirect to the settings page on activation
add_action('admin_init', 'ai_tts_redirect');
function ai_tts_redirect() {
    if (get_option('ai_tts_do_activation_redirect', false)) {
        delete_option('ai_tts_do_activation_redirect');
        wp_redirect(admin_url('options-general.php?page=ai-tts'));
    }
}

// Enqueue JavaScript
add_action('admin_enqueue_scripts', 'ai_tts_enqueue_scripts');
function ai_tts_enqueue_scripts() {
    // Only on the post edit screen
    global $post;
    if(!is_object($post) || $post->post_type != 'post') {
        return;
    }
    wp_enqueue_script('ai-tts-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('ai-tts-post-script', plugin_dir_url(__FILE__) . 'js/post.js', '', '1.0.0', true);
}

// Enqueue CSS
add_action('admin_enqueue_scripts', 'ai_tts_enqueue_styles');
function ai_tts_enqueue_styles() {
    // Only on the post edit screen or plugin settings page
    global $post;
    if(!is_object($post) || $post->post_type != 'post') {
        if(!isset($_GET['page']) || $_GET['page'] != 'ai-tts') {
            return;
        }
    }
    wp_enqueue_style('ai-tts-style', plugin_dir_url(__FILE__) . 'css/style.css', array(), '1.0.0');
}