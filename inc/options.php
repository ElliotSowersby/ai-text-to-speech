<?php
if(!defined('ABSPATH')) {
    exit;
}

// Add the menu item to the admin menu for settings
add_action('admin_menu', 'ai_tts_add_admin_menu');
function ai_tts_add_admin_menu() {
    add_options_page(esc_html__('AI Text-to-Speech', 'ai-text-to-speech'), esc_html__('AI Text-to-Speech', 'ai-text-to-speech'), 'manage_options', 'ai-tts', 'ai_tts_options_page');
}

// Get Options
function ai_tts_get_options() {
    $options = get_option('ai_tts_settings');
    if(!$options) {
        $options = [
            'file_storage_location' => 'local',
            'dropbox_app_key' => '',
            'dropbox_app_secret' => '',
            'dropbox_access_token' => '',
        ];
    }
    return $options;
}

// Options page callback
function ai_tts_options_page() {
    $options = ai_tts_get_options();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('AI Text-to-Speech', 'ai-text-to-speech'); ?></h1>
        <p><?php echo esc_html__('Settings for the AI Text-to-Speech plugin. When configured, you will be able to generate a TTS audio file for any post on your website, which is automatically displayed in an audio player at the top of the post.', 'ai-text-to-speech'); ?></p>
        <!-- Key input -->
        <form method="post" action="options.php">
            <?php settings_fields('ai_tts_settings'); ?>
            <?php do_settings_sections('ai_tts_settings'); ?>
            <table class="form-table">
                <!-- OpenAI API Key (Not saved in the single option) -->
                <tr valign="top">
                    <th scope="row"><?php echo esc_html__('OpenAI API Key', 'ai-text-to-speech'); ?></th>
                    <td><input type="text" name="ai_tts_api_key" value="<?php echo esc_attr(get_option('ai_tts_api_key')); ?>" /></td>
                </tr>
                <!-- Pricing information -->
                <tr>
                    <th scope="row"><?php echo esc_html__('OpenAI TTS Pricing', 'ai-text-to-speech'); ?></th>
                    <td>
                        <p><?php echo esc_html__('OpenAI charges $0.015 per 1000 characters converted from text to spoken audio.', 'ai-text-to-speech'); ?> <a href="https://openai.com/pricing" target="_blank"><?php echo esc_html__('Read more about the pricing here.', 'ai-text-to-speech'); ?></a></p>
                        <p><?php echo esc_html__('Note: It is your responsibility to keep track of your usage and cost in your', 'ai-text-to-speech'); ?> <a href="https://platform.openai.com/" target="_blank"><?php echo esc_html__('OpenAI account', 'ai-text-to-speech'); ?></a>. <?php echo esc_html__('Be sure to set your own usage limits and notifications.', 'ai-text-to-speech'); ?> <a href="https://platform.openai.com/docs/guides/production-best-practices/managing-billing-limits" target="_blank"><?php echo esc_html__('Learn more here.', 'ai-text-to-speech'); ?></a></p>
                    </td>
                </tr>
                <!-- Show current OpenAI monthly spend -->
                <tr valign="top">
                    <th scope="row"><?php echo esc_html__('OpenAI Usage Today', 'ai-text-to-speech'); ?></th>
                    <td>
                        <?php
                        $api_key = get_option('ai_tts_api_key');
                        if ($api_key) {
                            $ch = curl_init();
                            $date = date('Y-m-d');
                            curl_setopt($ch, CURLOPT_URL, 'https://api.openai.com/v1/usage?date=' . $date);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                'Authorization: Bearer ' . $api_key,
                            ]);
                            $response = curl_exec($ch);
                            $response = json_decode($response);

                            // Assuming $response is your stdClass object containing tts_api_data
                            $total_characters_used = 0;
                            $total_requests = 0;

                            if (isset($response->tts_api_data) && is_array($response->tts_api_data)) {
                                foreach ($response->tts_api_data as $item) {
                                    // Ensure that the required properties exist
                                    if (isset($item->num_characters) && isset($item->num_requests)) {
                                        $total_characters_used += $item->num_characters;
                                        $total_requests += $item->num_requests;
                                    }
                                }
                            }
                            echo esc_html__('Total Characters Used:', 'ai-text-to-speech') . " " . $total_characters_used . "<br>";
                            echo esc_html__('Total Requests:', 'ai-text-to-speech') . " " . $total_requests;
                            $total_cost = $total_characters_used / 1000 * 0.015;
                            $total_cost = round($total_cost, 2);
                            echo "<br>" . esc_html__('Total Cost:', 'ai-text-to-speech') . " $" . $total_cost;
                        } else {
                            echo esc_html__('No API key set.', 'ai-text-to-speech');
                        }
                        ?>
                    </td>
                </tr>
                </table>

                <hr/>

                <table class="form-table">
                <!-- Select file storage location -->
                <tr valign="top">
                    <th scope="row"><?php echo esc_html__('File Storage Location', 'ai-text-to-speech'); ?></th>
                    <td>
                        <select name="ai_tts_settings[file_storage_location]">
                            <option value="local" <?php selected($options['file_storage_location'], 'local'); ?>><?php echo esc_html__('Local', 'ai-text-to-speech'); ?></option>
                            <option value="dropbox" disabled><?php echo esc_html__('Third-Party Locations Coming Soon..', 'ai-text-to-speech'); ?></option>
                        </select>
                    </td>
                </tr>
                <!-- If Dropbox is selected, show the Dropbox API credentials -->
                <tr valign="top" class="ai-tts-dropbox-credentials" <?php if ($options['file_storage_location'] != 'dropbox') {
                    echo 'style="display: none;"';
                } ?>>
                    <th scope="row"><?php echo esc_html__('Dropbox App Key', 'ai-text-to-speech'); ?></th>
                    <td><input type="text" name="ai_tts_settings[dropbox_app_key]" value="<?php echo esc_attr($options['dropbox_app_key']); ?>" /></td>
                </tr>
                <tr valign="top" class="ai-tts-dropbox-credentials" <?php if ($options['file_storage_location'] != 'dropbox') {
                    echo 'style="display: none;"';
                } ?>>
                    <th scope="row"><?php echo esc_html__('Dropbox App Secret', 'ai-text-to-speech'); ?></th>
                    <td><input type="text" name="ai_tts_settings[dropbox_app_secret]" value="<?php echo esc_attr($options['dropbox_app_secret']); ?>" /></td>
                </tr>
                <tr valign="top" class="ai-tts-dropbox-credentials" <?php if ($options['file_storage_location'] != 'dropbox') {
                    echo 'style="display: none;"';
                } ?>>
                    <th scope="row"><?php echo esc_html__('Dropbox Access Token', 'ai-text-to-speech'); ?></th>
                    <td><input type="text" name="ai_tts_settings[dropbox_access_token]" value="<?php echo esc_attr($options['dropbox_access_token']); ?>" /></td>
                </tr>
                <!-- If Local is selected, show the Local credentials -->
                <tr valign="top" class="ai-tts-local-credentials" <?php if ($options['file_storage_location'] != 'local') {
                    echo 'style="display: none;"';
                } ?>>
                    <th scope="row"><?php echo esc_html__('Local Storage', 'ai-text-to-speech'); ?></th>
                    <td>
                        <p><?php echo esc_html__('Files are stored in the uploads directory at', 'ai-text-to-speech'); ?> <code>/wp-content/uploads/ai-tts/</code></p>
                    </td>
                </tr>
                <script>
                jQuery(document).ready(function($) {
                    // Show Dropbox credentials if Dropbox is selected
                    $('select[name="ai_tts_settings[file_storage_location]"]').change(function() {
                        if ($(this).val() == 'dropbox') {
                            $('.ai-tts-dropbox-credentials').show();
                        } elseif ($(this).val() == 'local') {
                            $('.ai-tts-local-credentials').show();
                        } else {
                            $('.ai-tts-dropbox-credentials').hide();
                        }
                    });
                    $('select[name="ai_tts_settings[file_storage_location]"]').trigger('change');
                });
                </script>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Register and define the settings
add_action('admin_init', 'ai_tts_admin_init');
function ai_tts_admin_init() {
    register_setting('ai_tts_settings', 'ai_tts_api_key');
    register_setting('ai_tts_settings', 'ai_tts_settings');
}