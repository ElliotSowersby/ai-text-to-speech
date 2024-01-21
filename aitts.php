<?php
/**
* Plugin Name: AI Text-to-Speech
* Description: Easily generate and display an audio version for your posts using OpenAI's TTS API.
* Version: 1.0.0
* Author: Elliot Sowersby, RelyWP
* Author URI: https://relywp.com
* License: GPLv3
* Text Domain: ai-text-to-speech
* Domain Path: /languages
*/

if ( !defined( 'ABSPATH' ) ) {
    exit;
}

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

    $post_types_option = ai_tts_get_option('post_types');
    $post_types_to_enable = ai_tts_get_option('post_types_to_enable');
    if($post_types_option == 'all') {
        $post_types = get_post_types(array('public' => true));
    } else {
        $post_types = $post_types_to_enable;
    }

    $post_types = get_post_types(array('public' => true));
    if(!is_object($post) || !in_array($post->post_type, $post_types)) {
        return;
    }
    wp_enqueue_script('ai-tts-script', plugin_dir_url(__FILE__) . 'js/script.js', array('jquery'), '1.0.0', true);
    wp_enqueue_script('ai-tts-post-script', plugin_dir_url(__FILE__) . 'js/post.js', '', '1.0.0', true);
}

// Enqueue CSS
add_action('admin_enqueue_scripts', 'ai_tts_enqueue_styles');
function ai_tts_enqueue_styles() {

    // On all post type post edit screen
    global $post;

    $post_types_option = ai_tts_get_option('post_types');
    $post_types_to_enable = ai_tts_get_option('post_types_to_enable');
    if($post_types_option == 'all') {
        $post_types = get_post_types(array('public' => true));
    } else {
        $post_types = $post_types_to_enable;
    }

    if(!is_object($post) || !in_array($post->post_type, $post_types)) {
        return;
    }
    wp_enqueue_style('ai-tts-style', plugin_dir_url(__FILE__) . 'css/post.css', array(), '1.0.0');
}

// Enqueue CSS admin settings page
add_action('admin_enqueue_scripts', 'ai_tts_enqueue_styles_admin');
function ai_tts_enqueue_styles_admin() {
    // Only on the plugin settings page
    if(!isset($_GET['page']) || $_GET['page'] != 'ai-text-to-speech') {
        return;
    }
    wp_enqueue_style('ai-tts-style-admin', plugin_dir_url(__FILE__) . 'css/settings.css', array(), '1.0.0');
}