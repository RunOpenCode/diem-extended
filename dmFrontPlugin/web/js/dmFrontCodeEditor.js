(function($)
{

  $.widget('ui.dmFrontCodeEditor', {
  
    _init: function()
    {
      var self = this;
      self.element.css('overflow', 'hidden');
      
      $('#dm_code_editor_file_open', self.element).css({
        height: (self.element.height() - 20) + 'px',
        overflowY: 'auto'
      });
      
      self.$tabs = self.element.find('div.dm_code_editor').tabs({
        cache: true,
        load: function(evt, ui) {
            self.tab(ui);
        }
      });
      
      self.$dialog = self.element.parent();
      
      self.$dialog.bind('resize', function()
      {
        $('textarea', self.element).each(function() {
            var $txt = $(this),
                height = self.$dialog.height() - 90,
                width = self.$dialog.width() - 38;
             $txt.css('resize', 'none')
                 .css('max-width', width + 'px')
                 .css('min-width', width + 'px')
                 .css('max-height', height + 'px')
                 .css('min-height', height + 'px')
                 .css('height', height + 'px')
                 .css('width', width + 'px');
        });
      });
      
      self.element.find('div#dm_code_editor_file_open ul.level2 a').click(function(e)
      {
            var path = $(this).attr('href').replace(/#/, ''),
                url = $.dm.ctrl.getHref('+/dmCodeEditor/file') + '?file=' + path,
                html = '<span title="' + path + '">' + $(this).parent().parent().parent().find('>a').text() + '/' + $(this).text() + '</span>';

          $(this).closest('div.ui-tabs').find('ul.ui-tabs-nav').append($('<li></li>').append($('<a href="' + url + '"></a>').html(html)));
          self.$tabs.tabs('refresh');
          self.$tabs.tabs('option', 'active', $(this).closest('div.ui-tabs').find('li:last-child').index());
          return false;
      });

      self.$dialog.find('a[title]').tipsy({gravity: $.fn.tipsy.autoSouth});
      
      if ($.isFunction(self.options.callback || null)) 
      {
        self.options.callback($dialog);
      }
    },
    
    tab: function(ui)
    {
      var self = this,
      $tab = $(ui.tab);

      $tab.attr('title', $tab.find('span').attr('title')).tipsy({gravity: $.fn.tipsy.autoSouth});

      
      $tab.append('<img class="close" width="9px" height="8px" src="' + $.dm.ctrl.options.dm_core_asset_root + 'images/cross-small.png' + '" />');
      
      $('img.close', $tab).click(function()
      {

            var id = $(this).closest('li').attr('aria-labelledby');
            $(this).closest('.ui-tabs').find('li[aria-labelledby="'+id+'"], div[aria-labelledby="'+id+'"]').remove();
            self.$tabs.tabs('refresh');
            $.dm.removeTipsy();
            return false;
      });
      
      // resize textarea
      self.$dialog.trigger('resize');

      setTimeout(function()
      {
        $panel.find('textarea').dmCodeArea({
          save: function()
          {
            $panel.find('a.save').trigger('click');
            return false;
          }
        });
      }, 50);

      var $panel =  $tab.closest('.ui-tabs').find('div[aria-labelledby="'+$tab.attr('aria-labelledby')+'"]');

      $panel.find('a.save').click(function()
      {
        self.save($panel);
      });
    },
    
    save: function($panel)
    {
      if (!$panel.is(':visible')) 
      {
        return false;
      }
      
      $panel.block();
      var file = $panel.find('input.path').val(), self = this;
      $.ajax({
        dataType: 'json',
        type: 'post',
        url: $.dm.ctrl.getHref('+/dmCodeEditor/save'),
        data: {
          file: file,
          code: $panel.find('textarea').val()
        },
        success: function(data)
        {
          $panel.find('span.info').html(data.message)[(data.type == 'error' ? 'add' : 'remove') + 'Class']('error');
          $panel.unblock();
          
          if (data.type == 'css') 
          {
            self.updateCss(data.path);
          }
          else 
            if (data.type == 'php') 
            {
              self.updateWidgets(data.widgets);
            }
        },
        error: function(xhr)
        {
          $panel.unblock();

          $.dm.ctrl.errorDialog('Error in '+file, xhr.responseText);
        }
      });

      return true;
    },
    
    updateCss: function(path)
    {
      if ($css = $('link[rel=stylesheet][href*=' + path + ']').orNot()) 
      {
        $("head").append('<link rel="stylesheet" href="' + path + '?_=' + Math.floor(999999 * Math.random()) + '">');
        $css.remove();
      }
    },
    
    updateWidgets: function(widgets)
    {
      for (var id in widgets) 
      {
        $('#dm_widget_' + id + ' div.dm_widget_inner').html(widgets[id]);
      }
    }
    
  });
  
})(jQuery);
