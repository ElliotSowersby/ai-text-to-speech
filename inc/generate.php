<?php
if(!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_generate_tts', 'ai_tts_generate_tts_callback');
function ai_tts_generate_tts_callback() {

    // Clear memory

    // If user is admin
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'You do not have permission to delete this file.']);
    }
    // Check nonce
    if (!wp_verify_nonce($_POST['nonce'], 'ai_tts_nonce')) {
        wp_send_json_error(['message' => 'Nonce verification failed']);
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $post_content = get_post_field('post_content', $post_id);

    // Add [pause] before and after headers
    $post_content = preg_replace('/<h[1-6]>(.*?)<\/h[1-6]>/', '[pause] $0 [pause]', $post_content);
    // Remove images from the post content
    $post_content = preg_replace('/<img[^>]+./', '', $post_content);
    // Remove HTML tags from the post content
    $post_content = strip_tags($post_content);
    // Remove https:// and http:// and www. from the post content
    $post_content = preg_replace('/(https?:\/\/)?(www\.)?/', '', $post_content);
    // Convert to plain text and nothing else
    $post_content = html_entity_decode($post_content);
    $post_content = wp_strip_all_tags($post_content, true);
    // Trim any whitespace
    $post_content = trim($post_content);

    // Split content into chunks of 1 sentence per chunk
    $chunks = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $post_content);
    // Add title as first chunk
    $chunks = array_merge([get_the_title($post_id)], $chunks);

    // Add [pause] to the end of each chunk
    $chunks = array_map(function($chunk) {
        return ' - ' . $chunk;
    }, $chunks);
    // Remove empty chunks
    $chunks = array_filter($chunks);
    // Remove whitespace
    $chunks = array_map('trim', $chunks);

    $combined_audio_data = '';
    $i = 0;

    $api_key = get_option('ai_tts_api_key');
    // Get the API key from wp-config if it's defined
    if(defined('AI_TTS_API_KEY')) {
        $api_key = AI_TTS_API_KEY;
    }

    // Loop through the chunks
    foreach ($chunks as $chunk) {

        $i++;

        // Prepare API request using cURL
        $ch = curl_init('https://api.openai.com/v1/audio/speech');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $api_key,
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'tts-1',
            'input' => $chunk,
            'voice' => sanitize_text_field($_POST['voice']),
            'response_format' => 'aac',
        ]));

        // Execute the API request
        $response = curl_exec($ch);
        
        $combined_audio_data .= $response;

        // Clear memory
        unset($chunk);
        unset($response);

        // Close cURL request
        curl_close($ch);

    }
    
    // Save the file and get the URL
    $file_url = ai_tts_save_audio_file($combined_audio_data, $post_id);

    // File URL filter
    $file_url = apply_filters('ai_tts_file_url', $file_url);

    // If file URL is empty
    if(!$file_url) {
        wp_send_json_error(['message' => 'Error saving file.']);
    }

    // Return the file URL
    wp_send_json_success(['file_url' => $file_url]);

}