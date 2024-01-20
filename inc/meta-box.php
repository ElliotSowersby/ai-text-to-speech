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
    <script>
    (function(wp){
        const { subscribe, select } = wp.data;

        let isSaving = false;
        let didSave = false;

        // Function to update cost and length
        function updateCostAndLength() {
            const editor = select('core/editor');
            if (!editor || !editor.getEditedPostContent) {
                // Retry after a short delay if the editor is not ready
                setTimeout(updateCostAndLength, 500);
                return;
            }

            const newContent = editor.getEditedPostContent();
            const newTitle = editor.getEditedPostAttribute('title');

            const textContent = newContent.replace(/<\/?[^>]+(>|$)/g, "").replace(/<!--[\s\S]*?-->/g, "");
            const totalLength = textContent.length + newTitle.length;
            const cost = (totalLength / 1000) * 0.015;

            document.getElementById('tts-cost-amount').innerHTML = '$' + cost.toFixed(4);
            document.getElementById('tts-cost-characters').innerHTML = totalLength;
        }

        // Try to run on page load after 4 seconds
        setTimeout(updateCostAndLength, 4000);

        subscribe(() => {
            const editor = select('core/editor');
            if (!editor) {
                return;
            }

            const isSavingPost = editor.isSavingPost();
            const didSaveSucceed = editor.didPostSaveRequestSucceed();
            const didSaveFail = editor.didPostSaveRequestFail();

            if (isSaving && (didSaveSucceed || didSaveFail)) {
                didSave = true;
            }

            isSaving = isSavingPost;

            if (didSave) {
                didSave = false;
                updateCostAndLength();
            }
        });
    })(window.wp);
    </script>

    <!-- Generate TTS button -->
    <p id="generate-tts-content">
        <button id="generate-tts" data-postid="<?php echo $post->ID; ?>"
        data-nonce="<?php echo wp_create_nonce('ai_tts_nonce'); ?>"
        class="button button-primary" style="width: 100%;">
            Generate TTS
        </button><br/>
    </p>

    <style>
    .tts-spin {
        animation: dashicons-tts-spin 1s infinite;
        animation-timing-function: linear;
    }
    @keyframes dashicons-tts-spin {
    0% {
        transform: rotate( 0deg );
    }
    100% {
        transform: rotate( 360deg );
    }
    }
    </style>

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