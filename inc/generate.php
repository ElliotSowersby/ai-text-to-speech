<?php
if(!defined('ABSPATH')) {
    exit;
}

add_action('wp_ajax_generate_tts', 'ai_tts_generate_tts_callback');
function ai_tts_generate_tts_callback() {

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

    // Add title to the beginning of the post content
    $post_title = get_post_field('post_title', $post_id);
    $post_content = $post_title . '. ' . $post_content;

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

    // Split content into chunks
    $chunks = ai_tts_split_content($post_content);

    $combined_audio_data = '';
    $i = 0;

    // Loop through the chunks
    foreach ($chunks as $chunk) {

        $i++;

        // Prepare API request using cURL
        $ch = curl_init('https://api.openai.com/v1/audio/speech');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . get_option('ai_tts_api_key'),
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'tts-1',
            'input' => $chunk,
            'voice' => sanitize_text_field($_POST['voice']),
            'response_format' => 'aac',
        ]));

        $response = curl_exec($ch);
        
        $combined_audio_data .= $response;

    }
    
    // Save the file and get the URL
    $file_url = ai_tts_save_audio_file($combined_audio_data, $post_id);

    error_log('File URL: ' . $file_url);

    if(!$file_url) {
        wp_send_json_error(['message' => 'Error saving file.']);
    }

    wp_send_json_success(['file_url' => $file_url]);

}

function ai_tts_split_content($content, $chunkSize = 4096) {
    $chunks = [];
    $length = strlen($content);
    $start = 0;

    while ($start < $length) {
        // Determine the end position of the chunk
        $end = min($start + $chunkSize, $length);

        // Find the last space in the current chunk to avoid splitting a word
        if ($end < $length) {
            $lastSpace = strrpos($content, ' ', $start - $end);
            if ($lastSpace !== false) {
                $end = $lastSpace;
            }
        }

        // Extract the chunk and add to the array
        $chunks[] = substr($content, $start, $end - $start);

        // Move the start position to the next character after the end of the chunk
        $start = $end + 1;
    }

    return $chunks;
}