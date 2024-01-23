<?php
if(!defined('ABSPATH')) {
    exit;
}

/*
* Generate TTS
* 
* Generates the TTS audio file via the OpenAI API.
*/
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

    // If contains Twitter embed block, get the tweet text
    $post_content = replace_twitter_embeds_with_text($post_content);

    // Convert monitary values to words
    $post_content = ai_tts_replace_numbers_with_words($post_content);
    
    // Remove images from the post content
    $post_content = preg_replace('/<img[^>]+./', '', $post_content);

    // Remove https:// and http:// and www. from the post content
    $post_content = preg_replace('/(https?:\/\/)?(www\.)?/', '', $post_content);

    // Add [pause] before and after headers
    $post_content = preg_replace('/<h[1-6]>(.*?)<\/h[1-6]>/', '[pause] $0 [pause]', $post_content);

    // Convert to plain text and nothing else
    $post_content = html_entity_decode($post_content);

    // Remove HTML tags
    $post_content = wp_strip_all_tags($post_content, true);

    // Trim any whitespace
    $post_content = trim($post_content);

    // Split content into chunks of 1 sentence per chunk
    $chunks = preg_split('/(?<=[.?!])\s+(?=[a-z])/i', $post_content);
    // Combine every 2 chunks
    $chunks = array_map(function($chunk1, $chunk2) {
        return $chunk1 . ' ' . $chunk2;
    }, array_filter($chunks, function($key) {
        return $key % 2 == 0;
    }, ARRAY_FILTER_USE_KEY), array_filter($chunks, function($key) {
        return $key % 2 == 1;
    }, ARRAY_FILTER_USE_KEY));

    // Add title as first chunk
    $title = get_the_title($post_id) . '.';
    
    // Author name
    if( get_post_type($post_id) == 'post' && ai_tts_get_option('include_author')) {
        $author = ' [pause] by ' . get_the_author_meta('display_name', get_post_field('post_author', $post_id)) . ' [pause] ';
    } else {
        $author = '';
    }

    // Post date
    $post_date = get_the_date('F jS, Y', $post_id);

    if($post_date && ai_tts_get_option('include_date')) {
        $post_date = ' [pause] Published on ' . $post_date . ' [pause] ';
    } else {
        $post_date = '';
    }

    $title = $title . $author . $post_date . ' ... ';
    $chunks = array_merge([$title], $chunks);

    // Remove empty chunks
    $chunks = array_filter($chunks);

    // Remove whitespace
    $chunks = array_map('trim', $chunks);

    // Filter Chunks
    $chunks = apply_filters('ai_tts_chunks', $chunks);

    // Get default values
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

        // Prepare API request data
        $api_url = 'https://api.openai.com/v1/audio/speech';
        $body = wp_json_encode(array(
            'model' => 'tts-1',
            'input' => sanitize_text_field($chunk),
            'voice' => sanitize_text_field($_POST['voice']),
            'response_format' => 'aac',
        ));

        $args = array(
            'method'      => 'POST',
            'headers'     => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'        => $body,
            'data_format' => 'body',
        );

        // Execute the API request using wp_remote_post
        $response = wp_remote_post($api_url, $args);

        // Check for errors
        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Error generating audio.']);
        } else {
            $combined_audio_data .= wp_remote_retrieve_body($response);
        }

        // Clear memory
        unset($chunk, $response);

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