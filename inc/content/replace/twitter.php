<?php
if(!defined('ABSPATH')) {
    exit;
}

/**
 * Replaces Twitter embed code with tweet text in the given content.
 *
 * @param string $post_content The content to search for Twitter embeds.
 * @return string Updated content with tweet texts.
 */
function replace_twitter_embeds_with_text($post_content) {
    $pattern = '/<!-- wp:embed {.*?"url":"(https:\/\/twitter\.com\/.*?\/status\/\d+)".*?} -->.*?<!-- \/wp:embed -->/s';
    return preg_replace_callback($pattern, 'fetch_tweet_text', $post_content);
}

/**
 * Fetches the text of a tweet from its URL.
 *
 * @param array $matches Array of regex matches.
 * @return string The tweet text or original embed code on failure.
 */
function fetch_tweet_text($matches) {
    $tweet_url = $matches[1];
    $tweet_id = basename(wp_parse_url($tweet_url, PHP_URL_PATH));

    // Make an API call to Twitter to get the tweet text.
    $response = wp_remote_get('https://publish.twitter.com/oembed?url=' . $tweet_url);
    if (is_wp_error($response)) {
        return $matches[0];
    }
    $tweet_text = json_decode($response['body'])->html;

    return $tweet_text ? $tweet_text : $matches[0] . " [pause]";
}