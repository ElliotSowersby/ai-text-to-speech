<?php
if(!defined('ABSPATH')) {
    exit;
}

// Delete the audio file and post meta
add_action('wp_ajax_delete_tts', 'ai_tts_delete_tts_callback');
function ai_tts_delete_tts_callback() {

    // If user is admin
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to delete this file.']);
    }
    // Check nonce
    if (!wp_verify_nonce(esc_html($_POST['nonce']), 'ai_tts_nonce')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $options = ai_tts_get_options();

    // Delete the audio file
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $file_url = get_post_meta($post_id, 'ai_tts_file_url', true);

    if ($file_url) {
        $file_path = str_replace(content_url(), WP_CONTENT_DIR, $file_url);
        unlink($file_path);
    }

    // Delete the post meta
    delete_post_meta($post_id, 'ai_tts_file_url');
    delete_post_meta($post_id, 'ai_tts_voice');

    if($file_path && !get_post_meta($post_id, 'ai_tts_file_url')) {
        wp_send_json_success(['message' => 'File deleted.']);
    } else {
        wp_send_json_error(['message' => 'Error deleting file.']);
    }

}