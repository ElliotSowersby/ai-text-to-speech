<?php
if(!defined('ABSPATH')) {
    exit;
}

// Add the audio player to the top of the post content
add_filter('the_content', 'ai_tts_add_audio_player');
function ai_tts_add_audio_player($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $tts_file_url = get_post_meta(get_the_ID(), 'ai_tts_file_url', true);
        $show_player_label = ai_tts_get_option('show_player_label');
        $player_label = ai_tts_get_option('player_label');
        if ($tts_file_url) {
            $audio_player = '<!-- AI Text-to-Speech Audio Player -->';
            $audio_player .= '<audio class="ai-tts-player" controls preload="metadata" src="' . esc_url($tts_file_url) . '"
            style="width: 100%; max-width: 790px; display: block; margin-bottom: 0px;"></audio>';
            if($show_player_label && $player_label) {
                $audio_player .= '<p style="font-size: 10px; text-align: center; margin-bottom: 25px; padding-bottom: 0;">'.esc_html($player_label).'</p>';
            }
            $content = $audio_player . $content;
        }
    }
    return $content;
}