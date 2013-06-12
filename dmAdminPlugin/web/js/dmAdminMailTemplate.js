(function($) {
    $('#dm_mail_template_admin_form_is_html').click(function(){
        if (tinyMCE && $(this).is(':checked')) {
            tinyMCE.execCommand('mceAddControl', false, 'dm_mail_template_admin_form_body');
            $('#dm_mail_template_admin_form_dm_mail_decorator_id').prop('disabled', false);
        } else {
            tinyMCE.execCommand('mceFocus', false, 'dm_mail_template_admin_form_body');
            tinyMCE.triggerSave();
            tinyMCE.execCommand('mceRemoveControl', false, 'dm_mail_template_admin_form_body');
            $('#dm_mail_template_admin_form_dm_mail_decorator_id').prop('disabled', true).val('');
        };
    });
    if (dmTinyMceInitEditor && !$('#dm_mail_template_admin_form_is_html').is(':checked')) {
        var removeEditor = function() {
            $('#dm_mail_template_admin_form_dm_mail_decorator_id').prop('disabled', true).val('');
            if (window.tinyMCE && $('#dm_mail_template_admin_form_body').css('display') == 'none') {
                tinyMCE.execCommand('mceFocus', false, 'dm_mail_template_admin_form_body');
                tinyMCE.execCommand('mceRemoveControl', false, 'dm_mail_template_admin_form_body');
            } else {
                setTimeout(removeEditor, 50);
            };
        };
        setTimeout(removeEditor, 50);

    };
})(jQuery);