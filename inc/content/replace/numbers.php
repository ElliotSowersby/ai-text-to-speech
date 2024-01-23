<?php
if(!defined('ABSPATH')) {
    exit;
}

/**
 * Converts a number to words.
 * 
 * @param float|string $number The number to convert.
 * @return string The number in words.
 */
function ai_tts_convertNumberToWords($number) {
    $f = new NumberFormatter("en", NumberFormatter::SPELLOUT);
    return $f->format($number) . " ";
}

/**
 * Gets the scale (Million, Billion, etc.) in words.
 * 
 * @param string $scale The scale symbol.
 * @return string The scale in words.
 */
function ai_tts_getScaleInWords($scale) {
    switch ($scale) {
        case 'B': return 'billion';
        case 'M': return 'million';
        case 'T': return 'trillion';
        case 'b': return 'billion';
        case 'm': return 'million';
        case 't': return 'trillion';
        default: return '';
    }
}

/**
 * Function to replace numbers with words in the given content.
 *  
*/
function ai_tts_replace_numbers_with_words($post_content) {
    // Convert monitary values to words
    $post_content = preg_replace_callback('/\$(\d+(\.\d+)?)([MBTmbt]?)/', function($matches) {
        $numberInWords = ai_tts_convertNumberToWords($matches[1]);
        $scale = ai_tts_getScaleInWords($matches[3]);
        return $numberInWords . ' ' . $scale;
    }, $post_content);
    return $post_content;
}