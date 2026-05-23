jQuery(document).ready(function ($) {

    var $metabox = $('#wpr-featured-video');
    if ( ! $metabox.length ) {
        return;
    }

    var file_frame;

    function toggleSource( source ) {
        if ( 'url' === source ) {
            $metabox.find('.wpr-featured-video-upload-wrap').hide();
            $metabox.find('.wpr-featured-video-url-wrap').show();
        } else {
            $metabox.find('.wpr-featured-video-upload-wrap').show();
            $metabox.find('.wpr-featured-video-url-wrap').hide();
        }
    }

    $metabox.on('change', '#wpr_featured_video_source', function () {
        toggleSource( $(this).val() );
    });

    $metabox.on('click', '#wpr_featured_video_upload_button', function (e) {
        e.preventDefault();

        var $button = $(this);

        file_frame = wp.media({
            title: $button.data('uploader_title') || 'Choose a video',
            button: {
                text: $button.data('uploader_button_text') || 'Set featured video'
            },
            library: { type: 'video' },
            multiple: false
        });

        file_frame.on('select', function () {
            var attachment = file_frame.state().get('selection').first().toJSON();

            $('#wpr_featured_video_id').val( attachment.id );

            var $preview = $metabox.find('.wpr-featured-video-preview');
            $preview.html('<video src="' + attachment.url + '" controls muted preload="metadata" style="width:100%;max-height:180px;background:#000;"></video>');

            $('#wpr_featured_video_upload_button').hide();
            $('#wpr_featured_video_remove_button').show();
        });

        file_frame.open();
    });

    $metabox.on('click', '#wpr_featured_video_remove_button', function (e) {
        e.preventDefault();

        $('#wpr_featured_video_id').val('');

        var $preview = $metabox.find('.wpr-featured-video-preview');
        $preview.html('<div class="wpr-featured-video-placeholder" style="width:100%;height:120px;background:#f1f1f1;border:1px dashed #c3c4c7;display:flex;align-items:center;justify-content:center;color:#646970;font-size:12px;">No video selected</div>');

        $(this).hide();
        $('#wpr_featured_video_upload_button').show();
    });

});
