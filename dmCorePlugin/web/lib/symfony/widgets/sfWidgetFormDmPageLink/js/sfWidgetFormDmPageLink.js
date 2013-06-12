(function($) {
    function initSfWidgetFormDmPageLink($context) {
        
        var $inputs = $context.find('.sfWidgetFormDmPageLink');
        $.each($inputs, function(){
            var $input = $(this), metadata = $input.metadata();
            var $field = $('<span class="sfWidgetFormDmPageLink field"></span>');
            var $clearButton = $('<span class="sfWidgetFormDmPageLink button ui-state-default"><span class="ui-icon ui-icon-closethick"></span></span>');
            var $gotoPageButton = $('<span class="sfWidgetFormDmPageLink button ui-state-default"><span class="ui-icon ui-icon-extlink"></span></span>');
            
            $clearButton.attr('title', metadata.clear_page_message).tipsy();
            $gotoPageButton.attr('title', metadata.goto_page_message).tipsy();
            
            if (metadata.page) {
                $field.text(metadata.page);                
            } else {
                $field.text(metadata.title);
                $clearButton.css('display', 'none');
                $gotoPageButton.css('display', 'none');
            };
            
            
            $input.after($clearButton).after($gotoPageButton).after($field);
            
            $clearButton.hover(
                function(){ $(this).addClass('ui-state-hover'); }, 
                function(){ $(this).removeClass('ui-state-hover'); }
            ).click(function(){
                $input.val('');
                $field.text(metadata.title);
                $clearButton.css('display', 'none');
                $gotoPageButton.css('display', 'none');
            });
            
            $gotoPageButton.click(function(){
                var script = dm_configuration.script_name.replace('admin_dev.php', 'dev.php');
                script = script.replace('admin.php', 'index.php');
                window.open(dm_configuration.relative_url_root + script + '+/dmPage/openPageById?id=' + $input.val(), '_blank');
            });
            
            $field.droppable({
                accept:       '#dm_page_bar li > a',
                activeClass:  'droppable_active',
                hoverClass:   'droppable_hover',
                tolerance:    'touch',
                drop:         function(event, ui) {
                    $input.val(ui.draggable.attr('data-page-id'));
                    $field.text(ui.draggable.text());
                    $clearButton.css('display', 'block');
                    $gotoPageButton.css('display', 'block');
                }
            });
        });        
    };
    
    // Admin backend
    if ($('#dm_admin_content').length >0) {
        initSfWidgetFormDmPageLink($('#dm_admin_content'));
    };
    
    // Widget
    $('#dm_page div.dm_widget').bind('dmWidgetLaunch', function() {
        initSfWidgetFormDmPageLink($(this));
    });

    // Admin frontend
    $('div.dm.dm_widget_edit_dialog_wrap').live('dmAjaxResponse', function() {
        initSfWidgetFormDmPageLink($(this));
    });
})(jQuery);
