function submitFilter() {
    var url = page_url;
    var ampersand = false;

    if (url.search(/\?/) >= 0) {
        ampersand = true;
    } else {
        url += '?';
    }

    if (jQuery('#filterName').val()) {
        url += (ampersand ? '&': '') + 'filter=' + jQuery('#filterName').val();
        ampersand = true;
    }

    if (jQuery('#filterCategory').val()) {
        url += (ampersand ? '&': '') + 'category=' + jQuery('#filterCategory').val();
        ampersand = true;
    }

    if (jQuery('#filterOrder').val() == '1') {
        url += (ampersand ? '&': '') + 'reverse';
        ampersand = true;
    }

    document.location = url;
}

jQuery(function() {
    jQuery('#filterName').keyup(function(e) {
        if (e.keyCode == 13 && jQuery(this).val() != jQuery(this).data('oldValue')) {
            jQuery(this).data('oldValue', jQuery(this).val());
            submitFilter();
        }
    });

    jQuery('#filterName').focus(function() {
        jQuery(this).data('oldValue', jQuery(this).val());
    });

    jQuery('#filterName').blur(function() {
        jQuery(this).val(jQuery(this).data('oldValue'));
    });

    jQuery('#filterCategory').multiselect({
        header: false,
        click: function(event, ui) {
            if (ui.checked) {
                jQuery(event.currentTarget).parent().addClass('checked');
            } else {
                jQuery(event.currentTarget).parent().removeClass('checked');
            }
        },
        noneSelectedText: 'Категории: ',
        selectedText:  function (checked, total, checkboxes) {
            var str = 'Категории: ';

            for (var i = 0; (i < 3) && (i < checkboxes.length); i++) {
                str += jQuery(checkboxes[i]).attr('title') + ', ';
            }

            if (checkboxes.length > 3) {
                str += '...';
            } else {
                str = str.substr(0, str.length - 2);
            }

            return str;
        },
        open: function(event, ui) {
            jQuery('#filterCategory').data('oldValue', jQuery('#filterCategory').val());
        },
        close: function(event, ui) {
            var newVal, oldVal;
            if (jQuery('#filterCategory').val()) {
                newVal = jQuery('#filterCategory').val().toString();
            } else {
                newVal = '';
            }

            if (jQuery('#filterCategory').data('oldValue')) {
                oldVal = jQuery('#filterCategory').data('oldValue').toString();
            } else {
                oldVal = '';
            }

            if (newVal != oldVal) {
                submitFilter();
            }
        }
    });

    jQuery('#filterOrder').multiselect({
        header: false,
        multiple: false,
        noneSelectedText: 'Сортировка: ',
        selectedText: function(checked, total, radios) {
            return 'Сортировка: ' + jQuery(radios[0]).attr('title');
        },
        open: function(event, ui) {
            jQuery('#filterOrder').data('oldValue', jQuery('#filterOrder').val());
        },
        close: function(event, ui) {
            if (jQuery('#filterOrder').val() != jQuery('#filterOrder').data('oldValue')) {
                submitFilter();
            }
        }
    });
});
