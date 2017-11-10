function isTouchSupported() {
    var msTouchEnabled = window.navigator.msMaxTouchPoints;
    var generalTouchEnabled = "ontouchstart" in document.createElement("div");

    if (msTouchEnabled || generalTouchEnabled) {
        return true;
    }
    return false;
}

function showRegistrationPopup() {
    jQuery.magnificPopup.open({
        items: {
            src: jQuery('#registration-popup'),
            type: 'inline'
        }
    });
}

function saveReport() {
    var groups = new Array();

    jQuery('.group_checkbox:checked').each(function() {
        groups.push(jQuery(this).val());
    });

    jQuery.ajax({
        url: '/wp-admin/admin-ajax.php?action=facecheck_save_report',
        method: 'post',
        data: {
            id: jQuery('#report_id').val(),
            name: jQuery('#report_name').val(),
            groups: groups,
            comment: jQuery('#report_comment').val()
        },
        success: function(data) {
            if (data == 'success') {
                jQuery.magnificPopup.open({
                    items: {
                        src: jQuery('#report_save_success-popup'),
                        type: 'inline',
                        closeOnContentClick: true,
                        showCloseBtn: false,
                        closeBtnInside: false
                    }
                });

                var timeout = setTimeout(function() {
                    if (jQuery.magnificPopup.instance) {
                        jQuery.magnificPopup.instance.close();
                    }
                }, 1000);
            } else {
                jQuery.magnificPopup.open({
                    items: {
                        src: jQuery('#report_save_fail-popup'),
                        type: 'inline',
                        closeOnContentClick: true,
                        showCloseBtn: false,
                        closeBtnInside: false
                    }
                });

                var timeout = setTimeout(function() {
                    if (jQuery.magnificPopup.instance) {
                        jQuery.magnificPopup.instance.close();
                    }
                }, 1000);
            }
        },
        error: function() {
            jQuery.magnificPopup.open({
                items: {
                    src: jQuery('#report_save_fail-popup'),
                    type: 'inline',
                    closeOnContentClick: true,
                    showCloseBtn: false,
                    closeBtnInside: false
                }
            });

            var timeout = setTimeout(function() {
                if (jQuery.magnificPopup.instance) {
                    jQuery.magnificPopup.instance.close();
                }
            }, 1000);
        }
    });
}

function sendLetter() {
    var emails = new Array();

    jQuery('.email_checkbox:checked').each(function() {
        emails.push(jQuery(this).val());
    });

    jQuery.ajax({
        url: '/wp-admin/admin-ajax.php?action=facecheck_report_letter',
        method: 'post',
        data: {
            id: jQuery('#report_id').val(),
            email: emails,
            profession: jQuery('#profession__list-inner li.active a').data('prof-id')
        },
        success: function(data) {
            if (data == 'success') {
                jQuery.magnificPopup.open({
                    items: {
                        src: jQuery('#report_letter_success-popup'),
                        type: 'inline',
                        closeOnContentClick: true,
                        showCloseBtn: false,
                        closeBtnInside: false
                    }
                });

                var timeout = setTimeout(function() {
                    if (jQuery.magnificPopup.instance) {
                        jQuery.magnificPopup.instance.close();
                    }
                }, 1000);
            } else {
                jQuery.magnificPopup.open({
                    items: {
                        src: jQuery('#report_letter_fail-popup'),
                        type: 'inline',
                        closeOnContentClick: true,
                        showCloseBtn: false,
                        closeBtnInside: false
                    }
                });

                var timeout = setTimeout(function() {
                    if (jQuery.magnificPopup.instance) {
                        jQuery.magnificPopup.instance.close();
                    }
                }, 1000);
            }
        },
        error: function() {
            jQuery.magnificPopup.open({
                items: {
                    src: jQuery('#report_letter_fail-popup'),
                    type: 'inline',
                    closeOnContentClick: true,
                    showCloseBtn: false,
                    closeBtnInside: false
                }
            });

            var timeout = setTimeout(function() {
                if (jQuery.magnificPopup.instance) {
                    jQuery.magnificPopup.instance.close();
                }
            }, 1000);
        }
    })
}

function addEmailField() {
    var $input = jQuery('<input type="text" />');
    jQuery('<div />').append($input).insertBefore(jQuery('#newAddress').parent());

    $input.keyup(function(e) {
        if (e.keyCode == 13) {
            if (jQuery(this).val()) {
                jQuery('#newAddress').data('emails', jQuery('#newAddress').data('emails') + 1);

                jQuery('<div />')
                    .append(jQuery('<input type="checkbox" class="email_checkbox" id="email_' + jQuery('#newAddress').data('emails') + '" value="' + jQuery(this).val() + '" checked />'))
                    .append(jQuery('<label for="email_' + jQuery('#newAddress').data('emails') + '">' + jQuery(this).val() + '</label>'))
                    .insertBefore(jQuery(this).parent());
            }

            jQuery(this).parent().remove();
        }
    });

    $input.blur(function() {
        if (jQuery(this).val()) {
            jQuery('#newAddress').data('emails', jQuery('#newAddress').data('emails') + 1);

            jQuery('<div />')
                .append(jQuery('<input type="checkbox" class="email_checkbox" id="email_' + jQuery('#newAddress').data('emails') + '" value="' + jQuery(this).val() + '" checked />'))
                .append(jQuery('<label for="email_' + jQuery('#newAddress').data('emails') + '">' + jQuery(this).val() + '</label>'))
                .insertBefore(jQuery(this).parent());
        }

        jQuery(this).parent().remove();
    });

    $input.focus();
}

function addGroupField() {
    var $input = jQuery('<input type="text" />');
    jQuery('<div />').append($input).insertBefore(jQuery('#newCategory').parent());

    $input.keyup(function(e) {
        e.stopPropagation();
        if (e.keyCode == 13) {
            if (jQuery(this).val()) {
                jQuery('#newCategory').data('groups', jQuery('#newCategory').data('groups') + 1);

                jQuery('<div />')
                    .append(jQuery('<input type="checkbox" id="newGroup_' + jQuery('#newCategory').data('groups') + '" class="group_checkbox" value="group_' + jQuery(this).val() + '" checked />'))
                    .append(jQuery('<label for="newGroup_' + jQuery('#newCategory').data('groups') + '">' + jQuery(this).val() + '</label>'))
                    .insertBefore(jQuery(this).parent());
            }

            jQuery(this).parent().remove();
        }
    });

    $input.focus();
}

function openFields() {
    jQuery('#report_name').removeAttr('readonly');
    jQuery('.group_checkbox').removeAttr('disabled');
    jQuery('#report_comment').removeAttr('readonly');
    jQuery('#newCategory').show();
}

function closeFields() {
    jQuery('#report_name').attr('readonly', '');
    jQuery('.group_checkbox').attr('disabled', '');
    jQuery('#report_comment').attr('readonly', '');
    jQuery('#newCategory').hide();
}

function changeProfession(catId, profId) {
    var profession = professions[catId].profs[profId];

    jQuery('#select-prof').text(profession.name);
    jQuery('.profession__img').addClass('active');

    var countIn = 0;

    jQuery('.report .legend').each(function() {
        var specId = jQuery(this).data('spec-id')
        var leftValue = specifications[specId].left_value;
        var rightValue = specifications[specId].right_value;
        var profLeftValue = specifications[specId].profs[profId].left_value;
        var profRightValue = specifications[specId].profs[profId].right_value;
        var profDescr = specifications[specId].profs[profId].description;

        var cssLeft = profLeftValue * 100 / (rightValue - leftValue);
        var cssRight = profRightValue * 100 / (rightValue - leftValue);
        var cssWidth = cssRight - cssLeft;

        jQuery(this).find('.legend__diagramm-progress-inner').css({
            left: cssLeft + '%',
            width: cssWidth + '%'
        });

        jQuery(this).find('p').text(profDescr);

        var specValue = jQuery(this).data('spec-val')
        if (parseInt(specValue) >= parseInt(profLeftValue) && parseInt(specValue) <= parseInt(profRightValue)) {
            countIn++;
        }
    });

    var percent = Math.round(countIn * 100 / specsCount);
    jQuery('.profession__img-val').html(percent + '<span>%</span>');
}

function showLegends() {
    jQuery('.candidate-menu__navig').children(':first').addClass('active');
    jQuery('.candidate-menu__navig').children(':last').removeClass('active');
    jQuery('.report .legend').show();
    jQuery('.report .section').hide();
}

function showSections() {
    jQuery('.candidate-menu__navig').children(':first').removeClass('active');
    jQuery('.candidate-menu__navig').children(':last').addClass('active');
    jQuery('.report .legend').hide();
    jQuery('.report .section').show();
}

jQuery(function() {
    if (isLogged) {
        jQuery('.report-buttons .edit').click(function() {
            jQuery(this).parent().hide();
            jQuery('.report-buttons .save').parent().show();

            openFields();
        })

        jQuery('.report-buttons .save').click(function() {
            saveReport();

            if (jQuery('.report-buttons .edit').length) {
                jQuery(this).parent().hide();
                jQuery('.report-buttons .edit').parent().show();

                closeFields();
            }
        });

        jQuery('.report-buttons .letter').click(function() {
            if (isTouchSupported()) {
                if (jQuery(this).hasClass('open')) {
                    sendLetter();
                    jQuery(this.removeClass('open'));
                } else {
                    jQuery(this).addClass('open');
                }
            } else {
                sendLetter();
            }
        });

        jQuery('.report-buttons .letter form').keyup(function(e) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }
        });

        jQuery('.report-buttons .letter form').keydown(function(e) {
            if (e.keyCode == 13) {
                e.preventDefault();
            }
        });

        jQuery('#newAddress').data('emails', 0);

        jQuery('#newAddress').click(function() {
            addEmailField();
        });

        jQuery('#newCategory').data('groups', 0);

        jQuery('#newCategory').click(function() {
            addGroupField();
        });

        jQuery('#showComment').click(function() {
            jQuery('#report_comment').toggle();
        });

        if (isTouchSupported()) {
            jQuery('.report-buttons .print').parent().hide();
        }
    }

    jQuery('#select-prof').click(function() {
        jQuery('#profession__list').show();
        jQuery('#profession__list-inner').show();
    });

    jQuery('#profession__list a').click(function () {
        var catId = jQuery(this).data('cat-id');
        jQuery(this).parent().siblings('.active').removeClass('active');
        jQuery(this).parent().addClass('active');

        jQuery('#profession__list-inner li').each(function() {
            if (jQuery(this).data('cat-id') == catId) {
                jQuery(this).show();
            } else {
                jQuery(this).hide();
            }
        });
    });

    jQuery('#profession__list-inner a').click(function() {
        jQuery(this).parent().siblings('.active').removeClass('active');
        jQuery(this).parent().addClass('active');
        jQuery('#profession__list-inner').hide();
        jQuery('#profession__list').hide();

        changeProfession(jQuery(this).parent().data('cat-id'), jQuery(this).data('prof-id'));
    });

    jQuery('.section a.toggle').click(function(e) {
        e.preventDefault();
        jQuery(this).parent().next().toggle();
        jQuery(this).toggleClass('closed');
    });

    jQuery(document).click(function(e) {
        if (!jQuery(e.target).is('#select-prof') && !jQuery(e.target).is('#profession__list') && !jQuery(e.target).parents('#profession__list').length) {
            jQuery('#profession__list-inner').hide();
            jQuery('#profession__list').hide();
        }
    });
});
