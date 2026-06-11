jQuery(document).ready(function ($) {
    var mediaUploader;

    $('#afc-upload-logo').on('click', function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: 'Select Partner Logo',
            button: { text: 'Use this logo' },
            multiple: false,
            library: { type: 'image' }
        });

        mediaUploader.on('select', function () {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#afc_logo_id').val(attachment.id);
            $('#afc-logo-preview').html(
                '<img src="' + attachment.url + '" style="max-width:200px;display:block;margin-bottom:8px;" />'
            );
            if ($('#afc-remove-logo').length === 0) {
                $('#afc-upload-logo').after(
                    '<button type="button" class="button" id="afc-remove-logo" style="margin-left:8px;">Remove Logo</button>'
                );
                bindRemoveLogo();
            }
        });

        mediaUploader.open();
    });

    function bindRemoveLogo() {
        $(document).on('click', '#afc-remove-logo', function (e) {
            e.preventDefault();
            $('#afc_logo_id').val('');
            $('#afc-logo-preview').html('');
            $(this).remove();
        });
    }

    bindRemoveLogo();
});
