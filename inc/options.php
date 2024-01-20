<?php
if(!defined('ABSPATH')) {
    exit;
}

// Add the menu item to the admin menu for settings
add_action('admin_menu', 'ai_tts_add_admin_menu');
function ai_tts_add_admin_menu() {
    add_options_page('AI Text-to-Speech', 'AI Text-to-Speech', 'manage_options', 'ai-tts', 'ai_tts_options_page');
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
        <h1>AI Text-to-Speech</h1>
        <p>Settings for the AI Text-to-Speech plugin. When configured, you will be able to generate a TTS audio file for any post on your website, which is automatically displayed in an audio player at the top of the post.</p>
        <!-- Key input -->
        <form method="post" action="options.php">
            <?php settings_fields('ai_tts_settings'); ?>
            <?php do_settings_sections('ai_tts_settings'); ?>
            <table class="form-table">
                <!-- OpenAI API Key (Not saved in the single option) -->
                <tr valign="top">
                    <th scope="row">OpenAI API Key</th>
                    <td><input type="text" name="ai_tts_api_key" value="<?php echo esc_attr(get_option('ai_tts_api_key')); ?>" /></td>
                </tr>
                <!-- Pricing information -->
                <tr>
                    <th scope="row">OpenAI TTS Pricing</th>
                    <td>
                        <p>OpenAI charges $0.015 per 1000 characters converted from text to spoken audio. <a href="https://openai.com/pricing" target="_blank">Read more about the pricing here.</a></p>
                        <p>Note: It is your responsibility to keep track of your usage and cost in your <a href="https://platform.openai.com/" target="_blank">OpenAI account</a>. Be sure to set your own usage limits and notifications. <a href="https://platform.openai.com/docs/guides/production-best-practices/managing-billing-limits" target="_blank">Learn more here.</a></p>
                    </td>
                </tr>
                <!-- Show current OpenAI monthly spend -->
                <tr valign="top">
                    <th scope="row">OpenAI Usage Today</th>
                    <td>
                        <?php
                        $api_key = get_option('ai_tts_api_key');
                        if($api_key) {
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
                            echo "Total Characters Used: " . $total_characters_used . "<br>";
                            echo "Total Requests: " . $total_requests;
                            $total_cost = $total_characters_used / 1000 * 0.015;
                            $total_cost = round($total_cost, 2);
                            echo "<br>Total Cost: $" . $total_cost;

                        } else {
                            echo 'No API key set.';
                        }
                        ?>
                    </td>
                </tr>
                </table>

                <hr/>

                <table class="form-table">
                <!-- Select file storage location -->
                <tr valign="top">
                    <th scope="row">File Storage Location</th>
                    <td>
                        <select name="ai_tts_settings[file_storage_location]">
                            <option value="local" <?php selected($options['file_storage_location'], 'local'); ?>>Local</option>
                            <option value="dropbox" disabled>Third-Party Options Coming Soon..</option>
                        </select>
                    </td>
                </tr>
                <!-- If Dropbox is selected, show the Dropbox API credentials -->
                <tr valign="top" class="ai-tts-dropbox-credentials" <?php if ($options['file_storage_location'] != 'dropbox') { echo 'style="display: none;"'; } ?>>
                    <th scope="row">Dropbox App Key</th>
                    <td><input type="text" name="ai_tts_settings[dropbox_app_key]" value="<?php echo esc_attr($options['dropbox_app_key']); ?>" /></td>
                </tr>
                <tr valign="top" class="ai-tts-dropbox-credentials" <?php if ($options['file_storage_location'] != 'dropbox') { echo 'style="display: none;"'; } ?>>
                    <th scope="row">Dropbox App Secret</th>
                    <td><input type="text" name="ai_tts_settings[dropbox_app_secret]" value="<?php echo esc_attr($options['dropbox_app_secret']); ?>" /></td>
                </tr>
                <tr valign="top" class="ai-tts-dropbox-credentials" <?php if ($options['file_storage_location'] != 'dropbox') { echo 'style="display: none;"'; } ?>>
                    <th scope="row">Dropbox Access Token</th>
                    <td><input type="text" name="ai_tts_settings[dropbox_access_token]" value="<?php echo esc_attr($options['dropbox_access_token']); ?>" /></td>
                </tr>
                <!-- If Local is selected, show the Local credentials -->
                <tr valign="top" class="ai-tts-local-credentials" <?php if ($options['file_storage_location'] != 'local') { echo 'style="display: none;"'; } ?>>
                    <th scope="row">Local Storage</th>
                    <td>
                        <p>Files are stored in the uploads directory at <code>/wp-content/uploads/ai-tts/</code></p>
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