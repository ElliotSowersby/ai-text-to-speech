jQuery(document).ready(function($) {
    // If #tts-player-audio audio src exists, show #tts-player
    if ($('#tts-player-audio').attr('src')) {
        $('#tts-player').show();
        $('#delete-tts').show();
    }
    // Generate TTS click
    $('#generate-tts').click(function(e) {
        e.preventDefault();
        var postId = $(this).data('postid');
        var nonce = $(this).data('nonce');
        var ajaxUrl = '/wp-admin/admin-ajax.php';

        // Show loading spinner
        $('#generate-tts-content').hide();
        $('#tts-player').hide();
        $('#tts-loading').show();

        // Stop audio if it's playing
        $('#tts-player-audio').trigger('pause');
        $('#tts-player-audio').prop('currentTime', 0);
        $('#tts-player-audio').attr('src', '');

        // Set voice select to disabled
        $('#ai-tts-voice').attr('disabled', true);

        // AJAX request to generate TTS
        $.post(ajaxUrl, {
            action: 'generate_tts',
            voice: $('#ai-tts-voice').val(),
            post_id: postId,
            nonce: nonce,
        }, function(response) {
            console.log(response);
            if (response.success) {
                $('#generate-tts-content').hide();
                $('#tts-cost').hide();
                $('#tts-player').show();
                $('#delete-tts').show();
                $('#tts-loading').hide();
                $('#tts-file-size').hide();
                $('#ai-tts-voice').attr('disabled', true);
                $('#tts-link-text').val(response.data.file_url);
                $('#tts-player-audio').attr('src', response.data.file_url);
                $('#tts-player-audio').trigger('load');
                // If tab not open
                if (!document.hasFocus()) {
                    // Set browser tab title
                    var originalTitle = document.title;
                    document.title = '*TTS Generated* - ' + originalTitle;
                    $(window).focus(function() {
                        document.title = originalTitle;
                    });
                }
            } else {
                alert('Something went wrong!');
            }
        });
    });
    // Delete TTS click
    $('#delete-tts').click(function(e) {
        e.preventDefault();
        var postId = $(this).data('postid');
        var nonce = $(this).data('nonce');
        var ajaxUrl = '/wp-admin/admin-ajax.php';

        // Show loading spinner
        $('#delete-tts').hide();
        $('#tts-deleting').show();

        // AJAX request to delete TTS
        $.post(ajaxUrl, {
            action: 'delete_tts',
            post_id: postId,
            nonce: nonce,
        }, function(response) {
            console.log(response);
            if (response.success) {
                $('#generate-tts-content').show();
                $('#tts-cost').show();
                $('#delete-tts').show();
                $('#tts-deleting').hide();
                $('#tts-player').hide();
                $('#delete-tts').hide();
                $('#tts-file-size').hide();
                $('#ai-tts-voice').attr('disabled', false);
                $('#tts-player-audio').attr('src', '');
            } else {
                alert('Something went wrong!' + response.data);
            }
        });
    });
    // Copy the file URL to the clipboard
    $('#copy-tts-url').click(function() {
        const el = document.createElement('textarea');
        el.value = $('#tts-player-audio').attr('src');
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        // Hightlight the text field for 1 second
        $('#tts-link-text').select();
        setTimeout(function() {
            $('#tts-link-text').blur();
        }, 1000);
    });
});