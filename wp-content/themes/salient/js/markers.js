var $magnifier;
var imageScaleX;
var imageScaleY;

function isTouchSupported() {
    var msTouchEnabled = window.navigator.msMaxTouchPoints;
    var generalTouchEnabled = "ontouchstart" in document.createElement("div");

    if (msTouchEnabled || generalTouchEnabled) {
        return true;
    }
    return false;
}

function createMagnifier(position) {
    $magnifier = jQuery('<div id="magnifier" style="' +
        'position: absolute; ' +
        'display: inline-block; ' +
        //'overflow: hidden;' +
        'background-image: url(' + jQuery('#face img').attr('src') + ');' +
        'background-size: ' + jQuery('#face').width() * editScale + 'px ' + jQuery('#face').height() * editScale + 'px;' +
    '" />');
    //$magnifier.append(jQuery('<div id="magnifier-img" style="position: absolute; display: inline-block; width: ' + jQuery('#face img').width() * editScale + 'px; height: ' + jQuery('#face img').height() * editScale + 'px; background-image: url(' + jQuery('#face img').attr('src') + '); background-size: 100%;"/>'));
    $magnifier.append(jQuery('<div class="marker" style="position: absolute;"></div>'));
    $magnifier.appendTo('#face');

    moveMagnifier(position);
}

function moveMagnifier(position) {
    var magnifierWidth = $magnifier.width();
    var magnifierHeight = $magnifier.height();
    var margLeft = parseFloat(jQuery('#face').css('margin-left'))? parseFloat(jQuery('#face').css('margin-left')) : jQuery('#face').position().left;

    if (jQuery('#face').hasClass('mobile')) {
        var topper = 120;
    } else {
        var topper = 0;
    }

    $magnifier.css({
        'left': position.left - $magnifier.css('width').replace('px', '') / 2 + 'px',
        'top': position.top - $magnifier.css('height').replace('px', '') / 2 - topper + 'px',
        'background-position': ($magnifier.width() / 2 - (position.left - margLeft) * editScale) + 'px ' + ($magnifier.height() / 2 - position.top * editScale) + 'px'
    });
    /*
    jQuery('#magnifier-img').css({
        'left': $magnifier.width() / 2 - (position.left - margLeft) * editScale + 'px',
        'top': $magnifier.height() / 2 - position.top * editScale + 'px'
    });
    */
}


function markerDragStart(e, ui) {
    createMagnifier(ui.position);

    jQuery('.example .marker.static').hide();
    jQuery('.example .image').addClass('big');

    switch (jQuery(e.target).attr('title')) {
        case 'ml' :
        case 'mt' :
        case 'mr' :
        case 'mb' :
            jQuery('.example .image').addClass('bottom');
            break;
        case 'ylo' :
        case 'ylt' :
        case 'yli' :
        case 'ylb' :
        case 'blo' :
        case 'blm' :
        case 'bli' :
            jQuery('.example .image').addClass('left');
            break;
        case 'yri' :
        case 'yrt' :
        case 'yro' :
        case 'yrb' :
        case 'bri' :
        case 'brm' :
        case 'bro' :
            jQuery('.example .image').addClass('right');
            break;
    }

    jQuery('.example .marker.blink[title=' + jQuery(this).attr('title') + ']').show();
}

function markerDragStop(e, ui) {
    $magnifier.remove();
    $magnifier = null;

    jQuery('.example .marker.blink').hide();
    jQuery('.example .image').removeClass('left').removeClass('right').removeClass('bottom').removeClass('big');
    jQuery('.example .marker.static').show();
}

function markerDrag(e, ui) {
    moveMagnifier(ui.position);
}

/*
function recalcMarkers() {
    jQuery.ajax({
        url: '/wp-admin/admin-ajax.php',
        type: 'post',
        data: {
            action: 'facecheck_reset_markers',
            id: photoId
        },
        dataType: 'json',
        success: function(markers) {
            hideLoader();
            jQuery('#face .marker').remove();
            for (i in markers) {
                $marker = jQuery('<div class="marker" />').appendTo('#face');
                $marker.attr('title', i);
                $marker.css({
                    'left': markers[i].x * imageScaleX + 'px',
                    'top': markers[i].y * imageScaleY + 'px'
                });
                $marker.draggable({
                    delay: 1000,
                    distance: 0,
                    start: markerDragStart,
                    stop: markerDragStop,
                    drag: markerDrag
                });
            }
        }
    });
    showLoader();
}
*/

function resetMarkers() {
    jQuery('#face .marker').not('#example .marker').each(function() {
        var marker = markers[jQuery(this).attr('title')];
        jQuery(this).css({
            'left': marker.x * imageScaleX + 'px',
            'top': marker.y * imageScaleY + 'px'
        });
    });
}

function saveMarkers() {
    jQuery('#face .marker').each(function () {
        var margLeft = parseFloat(jQuery('#face').css('margin-left'))? parseFloat(jQuery('#face').css('margin-left')) : jQuery('#face').position().left;
        var marker = jQuery(this).attr('title');
        jQuery('#submitForm input[name="marker[' + marker + '][x]"]').val((jQuery(this).position().left - margLeft) / imageScaleX);
        jQuery('#submitForm input[name="marker[' + marker + '][y]"]').val(jQuery(this).position().top / imageScaleY);
    });

    jQuery('#submitForm').submit();
}

jQuery(function() {
    if (isTouchSupported()) {
        jQuery('#face').addClass('mobile');
    }
    
    imageScaleX = jQuery('#face img').width() / originalWidth;
    imageScaleY = jQuery('#face img').height() / originalHeight;

    var margLeft = parseFloat(jQuery('#face').css('margin-left'))? parseFloat(jQuery('#face').css('margin-left')) : jQuery('#face').position().left;

    //jQuery(document).enableTouch({tapAndHoldDelay: holdDelay});

    for (i in markers) {
        $marker = jQuery('<div class="marker" />');
        $marker.css({
            'left': imageScaleX * markers[i].x + margLeft + 'px',
            'top': imageScaleY * markers[i].y + 'px'
        });
        $marker.attr('title', i);
        $marker.appendTo('#face');
        $marker.draggable({
            delay: 0,
            distance: 0,
            start: markerDragStart,
            stop: markerDragStop,
            drag: markerDrag,
            containment: '#face'
        });
    }

    jQuery('a.reset').click(function (e) {
        e.preventDefault();
        resetMarkers();
    });

    jQuery('#canSubmit').change(function() {
        if (jQuery(this).is(':checked')) {
            jQuery('#saveMarkers').removeClass('extra-color-2').addClass('accent-color');
        } else {
            jQuery('#saveMarkers').removeClass('accent-color').addClass('extra-color-2');
        }
    })

    jQuery('#saveMarkers').click(function(e) {
        if (jQuery('#canSubmit').is(':checked')) {
            saveMarkers();
        }
    });
});