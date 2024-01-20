<?php
if(!defined('ABSPATH')) {
    exit;
}

// Add the audio player to the top of the post content
add_filter('the_content', 'ai_tts_add_audio_player');
function ai_tts_add_audio_player($content) {
    if (is_single() && in_the_loop() && is_main_query()) {
        $tts_file_url = get_post_meta(get_the_ID(), 'ai_tts_file_url', true);
        $player_label = ai_tts_get_option('player_label');
        $player_background_color = ai_tts_get_option('player_background_color');
        if(!$player_background_color) {
            $player_background_color = '#f1f1f1';
        }
        if ($tts_file_url) {
            $audio_player = '<!-- AI Text-to-Speech Audio Player -->';
            $audio_player .= '<audio class="ai-tts-player" controls preload="metadata" src="' . esc_url($tts_file_url) . '"
            style="width: 100%; display: block; margin-bottom: 0px;"></audio>';
            
            $audio_player .= '<p style="font-size: 10px; text-align: center; margin-bottom: 25px; padding-bottom: 0;">'.esc_html($player_label).'</p>';
            if($player_background_color) {
                $audio_player .= '<style>.ai-tts-player::-webkit-media-controls-panel { background-color: ' . esc_html($player_background_color) . '; }</style>';
            }
            $content = $audio_player . $content;
        }
    }
    return $content;
}