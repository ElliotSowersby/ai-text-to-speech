<?php
if(!defined('ABSPATH')) {
    exit;
}

// Implement ai_tts_save_audio_file function to save the file
function ai_tts_save_audio_file($response, $post_id) {

    // If user is admin
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to delete this file.']);
    }

    $options = ai_tts_get_options();
    
	// Save the file to the uploads directory
	$file_name = 'post-' . $post_id . '.mp3';
	$file_path = AI_TTS_UPLOAD_DIR . $file_name;
    
    // Convert the response to an MP3 file and keep file duration
    $mp3 = file_get_contents('data:audio/mp3;base64,' . base64_encode($response));

    // Save the file to the uploads directory
    file_put_contents($file_path, $mp3);

    $file_url = content_url('/uploads/ai-tts/' . $file_name);

    error_log("test:" . $file_url);

    // Sanitize
    $file_url = esc_url_raw($file_url);
    $voice = sanitize_text_field($_POST['voice']);

    // Save the file URL to post meta
    update_post_meta($post_id, 'ai_tts_file_url', $file_url);
    update_post_meta($post_id, 'ai_tts_voice', $voice);
    update_post_meta($post_id, 'ai_tts_location', 'local');

    if($file_url) {
        wp_send_json_success(['file_url' => $file_url]);
    } else {
        wp_send_json_error(['message' => 'Error saving file.']);
    }

}