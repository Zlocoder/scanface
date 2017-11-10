function initUploader() {
    var $input = jQuery('#file-input');
    var timer;

    $input.fileupload({
        url: '/wp-admin/admin-ajax.php',
        dataType: 'json',
        dropZone: $input,
        pasteZone: null,
        fileInput: $input,
        limitMultiFileUploads: 1,
        formData: {
            action: 'facecheck_upload_photo'
        },
        start: function() {
            jQuery.magnificPopup.open({
                items: {
                    src: jQuery('#upload-popup'),
                    type: 'inline'
                },
                closeOnBgClick: false
            })
        },
        done: function(e, data) {
            if (data.result) {
                var photo_id = data.result;
                timer = setInterval(function() {
                    jQuery.ajax({
                        url: '/wp-admin/admin-ajax.php',
                        type: 'post',
                        data: {
                            action: 'facecheck_process_photo',
                            id: photo_id
                        },
                        success: function(data) {
                            if(data == 'not_face') {
                                clearInterval(timer);
                                jQuery.magnificPopup.close();
                                jQuery.magnificPopup.open({
                                    items: {
                                        src: jQuery('#bad-photo'),
                                        type: 'inline'
                                    }
                                });
                            } else if (data == '1') {
                                clearInterval(timer);
                                document.location = redirectUrl + (redirectUrl.match(/\?/) ? '&facecheck_photo_id=' : '?facecheck_photo_id=' ) + photo_id;
                            }
                        }
                    })
                }, 2000);
            }
        }
    });
}

jQuery(function() {
    initUploader();

    jQuery(document).bind('drop dragover', function (e) {
        e.preventDefault();
    });
});