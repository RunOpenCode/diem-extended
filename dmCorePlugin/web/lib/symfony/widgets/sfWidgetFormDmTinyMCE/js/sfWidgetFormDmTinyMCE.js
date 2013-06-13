;(function($) {
    function initSfWidgetFormDmTinyMCE($context) {
        var $editors = $context.find('[data-dme-richeditor="tiny_mce"]');

        if ($editors.length == 0) {
            return;
        };

        $.each($editors, function(){
            var $this = $(this);
            if (!$this.attr('data-dme-richeditor')) return;
            if ($this.attr('data-dme-richeditor') == 'tiny_mce') {
                $this.removeAttr('data-dme-richeditor');
                var config =  $this.metadata().tiny_mce_config;
                tinymce.baseURL = config.tiny_mce_base_path;
                window.tinymce.dom.Event.domLoaded = true;
                $this.tinymce(config);
            };
        });

    };

    // Admin backend
    if ($('#dm_admin_content').length >0) {
        initSfWidgetFormDmTinyMCE($('#dm_admin_content'));
    };

    // Widget
    $('#dm_page div.dm_widget').on('dmWidgetLaunch', function() {
        initSfWidgetFormDmTinyMCE($(this));
    });

    // Admin frontend
    $('div.dm.dm_widget_edit_dialog_wrap').live('dmAjaxResponse', function() {
        initSfWidgetFormDmTinyMCE($(this));
    });

})(jQuery);

