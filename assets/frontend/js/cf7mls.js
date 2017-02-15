(function($){
    //jQuery time
    var current_fs, next_fs, previous_fs; //fieldsets
    var left, opacity, scale; //fieldset properties which we will animate
    var animating; //flag to prevent quick multi-click glitches
    var has_response = false;
    

    jQuery(document).ready(function($) {
        $('form.wpcf7-form').each(function(index, el) {
            var totalFieldset = 0;
            var findFieldset = $(el).find('fieldset.fieldset-cf7mls');
            if (findFieldset.length > 0) {
                $.each(findFieldset, function(i2, el2) {
                    if (i2 == 0) {
                        $(el2).addClass('cf7mls_current_fs');
                    }

                    jQuery(el2).attr('data-cf7mls-order', i2);
                    totalFieldset = totalFieldset + 1;
                    //disable next button if the fieldset has  wpcf7-acceptance
                    var acceptances = jQuery(el2).find('input:checkbox.wpcf7-acceptance');
                    if (acceptances.length) {
                        cf7mls_toggle_next_btn(acceptances, el2);
                    }
                });
                $.each(findFieldset, function(i2, el2) {
                    if (i2 == (totalFieldset - 1)) {
                        $(el2).find('.cf7mls_next').remove();
                    }
                });
                $(el).attr('data-count-fieldset', totalFieldset);
                //on form submit
                $(el).submit(function(event) {
                    var findFieldset = $(el).find('fieldset.fieldset-cf7mls.cf7mls_current_fs');
                    if (findFieldset.data('cf7mls-order') != (totalFieldset - 1)) {
                        findFieldset.find('.cf7mls_next').click();
                        return false;
                    }                
                });
            }
        });
    });
    jQuery(document).on('click', 'form.wpcf7-form input:checkbox.wpcf7-acceptance', function(event) {
        //event.preventDefault();
        var $this = jQuery(this);
        var parent_fieldset = $this.closest('fieldset.fieldset-cf7mls');
        if (parent_fieldset.length) {
            var acceptances = jQuery(parent_fieldset).find('input:checkbox.wpcf7-acceptance');
            if (acceptances.length) {
                cf7mls_toggle_next_btn(acceptances, parent_fieldset);
            }            
        }
    });

    $(document).on('click', '.cf7mls_next', function(event) {
        event.preventDefault();
        var $this = $(this);

        $this.addClass('sending');
        current_fs = $this.closest('.fieldset-cf7mls');
        next_fs = current_fs.next();

        //validation
        var form = $this.parent().closest('form.wpcf7-form');

        var fd = new FormData();
        $.each(form.find('input[type="file"]'), function(index, el) {

            fd.append($(el).attr('name'), $(el)[0].files[0]);
        });
        
        var other_data = form.serializeArray();
        $.each(other_data,function(key, input){
            fd.append(input.name, input.value);
        });


        $.ajax({
            url: cf7mls_object.ajax_url + '?action=cf7mls_validation',
            type: 'POST',
            data: fd,
            processData: false,
            contentType: false,
        })
        .done(function(msg) {
            $this.removeClass('sending');
            var json = $.parseJSON(msg);

            /*
             * Insert _form_data_id if 'json variable' has
             */
            if (typeof json._cf7mls_db_form_data_id != 'undefined') {
                if (!form.find('input[name="_cf7mls_db_form_data_id"]').length) {
                    form.append('<input type="hidden" name="_cf7mls_db_form_data_id" value="'+json._cf7mls_db_form_data_id+'" />');
                }
            }            
            
            if (!json.success) {
                var checkError = 0;
                //reset error messages
                current_fs.find('.wpcf7-form-control-wrap').removeClass('cf7mls-invalid');
                current_fs.find('.wpcf7-form-control-wrap .wpcf7-not-valid-tip').remove();
                if (has_response) {                    
                    current_fs.find('.wpcf7-response-output.wpcf7-validation-errors').removeClass('wpcf7-validation-errors');
                } else {
                    current_fs.find('.wpcf7-response-output.wpcf7-validation-errors').remove();
                }
                
                $.each(json.invalid_fields, function(index, el) {
                    if (current_fs.find('input[name="'+index+'"]').length || 
                        current_fs.find('input[name="'+index+'[]"]').length ||
                        current_fs.find('select[name="'+index+'"]').length || 
                        current_fs.find('select[name="'+index+'[]"]').length ||
                        current_fs.find('textarea[name="'+index+'"]').length || 
                        current_fs.find('textarea[name="'+index+'[]"]').length
                    ) {
                        checkError = checkError + 1;

                        var controlWrap = $('.wpcf7-form-control-wrap.' + index, form);
                        controlWrap.addClass('cf7mls-invalid');
                        controlWrap.find('span.wpcf7-not-valid-tip').remove();
                        controlWrap.append('<span role="alert" class="wpcf7-not-valid-tip">' + el.reason + '</span>');

                        //return false;
                    }
                });

                if (checkError == 0) {
                    json.success = true;
                    has_response = false;
                } else {
                    if (current_fs.find('.wpcf7-response-output').length) {
                        has_response = true;
                        current_fs.find('.wpcf7-response-output').addClass('wpcf7-validation-errors').show().text(json.message);
                    } else {
                        has_response = false;
                        current_fs.append('<div class="wpcf7-response-output wpcf7-display-none wpcf7-validation-errors" style="display: block;" role="alert">' + json.message + '</div>');
                    }
                    
                }
            }

            if (json.success) {
                
                /*
                current_fs.fadeOut('400', function() {
                    next_fs.fadeIn('400');
                });
                
                });
                */
               
                current_fs.css({
                    height: '0px',
                    overflow: 'hidden',
                    opacity: '0',
                    'visibility': 'hidden'
                }).removeClass('cf7mls_current_fs');
                next_fs.css({
                    height: 'auto',
                    overflow: 'visible',
                    opacity: '1',
                    'visibility': 'visible'
                }).addClass('cf7mls_current_fs');
                dhScrollTo(form);


                return false;
                
            } else {

            }
        })
        .fail(function() {
            $this.removeClass('sending');
            console.log("Validation error");
        })
        .always(function() {
            $this.removeClass('sending');
            console.log("Validation complete");
        });
        return false;
    });

    $(".cf7mls_back").click(function(){
        $('.wpcf7-response-output.wpcf7-display-none').removeClass('wpcf7-validation-errors').html('');
        $('.wpcf7-response-output.wpcf7-display-none.wpcf7-mail-sent-ok').hide();
        
        current_fs = $(this).closest('.fieldset-cf7mls');
        previous_fs = current_fs.prev();
        
        /*
        current_fs.fadeOut('400', function() {
            previous_fs.fadeIn('400');
        });
         */
        
        current_fs.css({
            height: '0px',
            overflow: 'hidden',
            opacity: '0',
            'visibility': 'hidden'
        }).removeClass('cf7mls_current_fs');
        previous_fs.css({
            height: 'auto',
            overflow: 'visible',
            opacity: '1',
            'visibility': 'visible'
        }).addClass('cf7mls_current_fs');

        var form = $(this).parent().closest('form.wpcf7-form');
        dhScrollTo(form);

        return false;
    });
    function cf7mls_is_not_last_fieldset(fieldset, total_fieldset) {
        var order = fieldset.data('cf7mls-order');
        return order != total_fieldset;
    }
    function dhScrollTo(el) {
        if (cf7mls_object.scroll_step == "true") {
            $('html, body').animate({
                scrollTop: el.offset().top
            }, 'slow');
        } else if (cf7mls_object.scroll_step == "scroll_to_top") {
            $('html, body').animate({
                scrollTop: $('body').offset().top
            }, 'slow');
        }
    }
    function cf7mls_toggle_next_btn(acceptances, fieldset) {
        if (acceptances.length > 0) {
            var ii = 0;
            jQuery.each(acceptances, function(i, v) {
                if (jQuery(v).is(':checked')) {
                    //console.log('checked');
                } else {
                    ii++;
                }
            });
            if (ii > 0) {
                //console.log(ii);
                jQuery(fieldset).find('.cf7mls_next').attr('disabled', 'disabled');
            } else {
                jQuery(fieldset).find('.cf7mls_next').removeAttr('disabled');
            }
        }
    }
})(jQuery)