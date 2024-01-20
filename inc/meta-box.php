<?php
if(!defined('ABSPATH')) {
    exit;
}

// Add the meta box
add_action('add_meta_boxes', 'ai_tts_add_meta_box');
function ai_tts_add_meta_box() {
    add_meta_box(
        'ai-tts-meta-box',
        'AI Text to Speech',
        'ai_tts_meta_box_callback',
        'post',
        'side',
        'high'
    );
}

// Meta box display callback
function ai_tts_meta_box_callback($post) {
    // Add a nonce field for security
    wp_nonce_field('ai_tts_save_meta_box_data', 'ai_tts_meta_box_nonce');
    ?>

    <!-- Voice selection field for generation only -->
    <p>
        <label for="ai-tts-voice">Voice:</label>
        <select id="ai-tts-voice" name="ai-tts-voice" style="width: 50%;">
            <option value="alloy" <?php selected(get_post_meta($post->ID, 'ai_tts_voice', true), 'alloy'); ?>>Alloy</option>
            <option value="echo" <?php selected(get_post_meta($post->ID, 'ai_tts_voice', true), 'echo'); ?>>Echo</option>
            <option value="fable" <?php selected(get_post_meta($post->ID, 'ai_tts_voice', true), 'fable'); ?>>Fable</option>
            <option value="onyx" <?php selected(get_post_meta($post->ID, 'ai_tts_voice', true), 'onyx'); ?>>Onyx</option>
            <option value="nova" <?php selected(get_post_meta($post->ID, 'ai_tts_voice', true), 'nova'); ?>>Nova</option>
            <option value="shimmer" <?php selected(get_post_meta($post->ID, 'ai_tts_voice', true), 'shimmer'); ?>>Shimmer</option>
        </select>
    </p>
    
    <!-- Estimate of cost -->
    <p id="tts-cost">
        <span id="tts-cost-characters" style="display: inline-block;">0</span> characters.
        <span title="Estimate for $0.015 per character.">Estimate cost: <span id="tts-cost-amount" style="display: inline-block;">$0.00</span></span>
    </p>

    <!-- Generate TTS button -->
    <p id="generate-tts-content">
        <button id="generate-tts" data-postid="<?php echo $post->ID; ?>"
        data-nonce="<?php echo wp_create_nonce('ai_tts_nonce'); ?>"
        class="button button-primary" style="width: 100%;">
            Generate TTS
        </button><br/>
    </p>

    <div id="tts-loading" style="display: none;">
        <p style="margin: 0;"><span class="dashicons dashicons-update dashicons-spin tts-spin"></span> Generating audio file...</p>
    </div>        

    <div id="tts-deleting" style="display: none;">
        <p>Deleting file...</p>
    </div>

    <!-- Player for the audio file -->
    <?php
    $tts_file_url = get_post_meta($post->ID, 'ai_tts_file_url', true);
    if(!file_exists(str_replace(content_url(), WP_CONTENT_DIR, $tts_file_url))) {
        $tts_file_url = '';
        delete_post_meta($post->ID, 'ai_tts_file_url');
        delete_post_meta($post->ID, 'ai_tts_voice');
        delete_post_meta($post->ID, 'ai_tts_location');
    }
    ?>
    <div id="tts-player" style="display: none;">

        <p style="margin: 20px 0 5px 0;">Generated TTS file: <?php if($tts_file_url && file_exists(str_replace(content_url(), WP_CONTENT_DIR, $tts_file_url))) { ?>
            <span id="tts-file-size">
                <?php echo round(filesize(str_replace(content_url(), WP_CONTENT_DIR, $tts_file_url)) / 1000, 2); ?> KB
            </span>
        <?php } ?></p>

        <audio id="tts-player-audio" controls src="<?php echo esc_url($tts_file_url); ?>" style="background: #f3f3f3; border-radius: 5px;"></audio>

        <!-- Copy File URL -->
        <p id="tts-copy-url" style="font-size: 10px; text-align: center; margin: 5px 0 5px 0; padding-bottom: 0;">
            <button id="copy-tts-url" class="button button-secondary" style="width: 49%;">
                Copy File URL
            </button>
            <!-- Delete File -->
            <button id="delete-tts" data-postid="<?php echo $post->ID; ?>"
            data-nonce="<?php echo wp_create_nonce('ai_tts_nonce'); ?>"
            class="button button-secondary" style="width: 49%;">
                Delete File
            </button>
        </p>

        </div>
    <?php
}