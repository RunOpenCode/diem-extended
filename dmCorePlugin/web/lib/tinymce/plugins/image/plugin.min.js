/**
 * plugin.js
 *
 * Copyright, Moxiecode Systems AB
 * Released under LGPL License.
 *
 * License: http://www.tinymce.com/license
 * Contributing: http://www.tinymce.com/contributing
 */

/*global tinymce:true */

tinymce.PluginManager.add('image', function(editor) {

    function showDialog() {
        var win, data, dom = editor.dom, imgElm = editor.selection.getNode();
        var width, height, imageListCtrl;

        function buildImageList() {
            var linkImageItems = [{text: 'None', value: ''}];

            tinymce.each(editor.settings.image_list, function(link) {
                linkImageItems.push({
                    text: link.text || link.title,
                    value: link.value || link.url,
                    menu: link.menu
                });
            });

            return linkImageItems;
        }

        function recalcSize(e) {
            var widthCtrl, heightCtrl, newWidth, newHeight;

            widthCtrl = win.find('#width')[0];
            heightCtrl = win.find('#height')[0];

            newWidth = widthCtrl.value();
            newHeight = heightCtrl.value();

            if (win.find('#constrain')[0].checked() && width && height && newWidth && newHeight) {
                if (e.control == widthCtrl) {
                    newHeight = Math.round((newWidth / width) * newHeight);
                    heightCtrl.value(newHeight);
                } else {
                    newWidth = Math.round((newHeight / height) * newWidth);
                    widthCtrl.value(newWidth);
                }
            }

            width = newWidth;
            height = newHeight;
        }

        function onSubmitForm() {
            function waitLoad(imgElm) {
                imgElm.onload = imgElm.onerror = function() {
                    imgElm.onload = imgElm.onerror = null;
                    editor.selection.select(imgElm);
                    editor.nodeChanged();
                };
            }

            var data = win.toJSON();

            if (data.width === '') {
                data.width = null;
            }

            if (data.height === '') {
                data.height = null;
            }

            if (data.style === '') {
                data.style = null;
            }

            data = {
                src: data.src,
                alt: data.alt,
                width: data.width,
                height: data.height,
                style: data.style
            };

            // Diem Extended injection
            var $mediaId = $('#' + win.find('#src')[0]._id + '-inp-media-id');
            if ($mediaId.length > 0 && $mediaId.val() != '') {
                data['data-dme-media-id'] = $mediaId.val();
                if (win.find('#method')[0].value()) {
                    data['data-dme-media-resize-method'] = win.find('#method')[0].value();
                }
            } else {
                delete data['data-dme-media-id'];
                delete data['data-dme-media-resize-method'];
            }


            function executeInsertImage() {
                if (!imgElm) {
                    data.id = '__mcenew';
                    editor.insertContent(dom.createHTML('img', data));
                    imgElm = dom.get('__mcenew');
                    dom.setAttrib(imgElm, 'id', null);
                } else {
                    dom.setAttribs(imgElm, data);
                    if (!data['data-dme-media-id']) {
                        dom.setAttrib(imgElm, 'data-dme-media-id', null);
                        dom.setAttrib(imgElm, 'data-dme-media-resize-method', null);
                    }
                }

                waitLoad(imgElm);
            }

            if (data['data-dme-media-id']) {
                $.ajax($.dm.ctrl.getHref('+/dmRichEditor/getProcessedImageURL'), {
                    data: {
                        media_id: data['data-dme-media-id'],
                        width: data.width,
                        height: data.height,
                        method: data['data-dme-media-resize-method']
                    },
                    success: function(response){
                        data.src = response.src;
                        executeInsertImage();
                    },
                    error: function(){
                        executeInsertImage();
                        editor.windowManager.alert('There has been an server error with fetching media.');
                    }
                });
            } else {
                executeInsertImage();
            }
        }

        function removePixelSuffix(value) {
            if (value) {
                value = value.replace(/px$/, '');
            }

            return value;
        }

        width = dom.getAttrib(imgElm, 'width');
        height = dom.getAttrib(imgElm, 'height');

        if (imgElm.nodeName == 'IMG' && !imgElm.getAttribute('data-mce-object')) {
            data = {
                src: dom.getAttrib(imgElm, 'src'),
                alt: dom.getAttrib(imgElm, 'alt'),
                width: width,
                height: height,
                'data-dme-media-id': dom.getAttrib(imgElm, 'data-dme-media-id'), // Diem Extended injection
                'data-dme-media-resize-method': dom.getAttrib(imgElm, 'data-dme-media-resize-method'), // Diem Extended injection
            };
        } else {
            imgElm = null;
        }

        if (editor.settings.image_list) {
            imageListCtrl = {
                name: 'target',
                type: 'listbox',
                label: 'Image list',
                values: buildImageList(),
                onselect: function(e) {
                    var altCtrl = win.find('#alt');

                    if (!altCtrl.value() || (e.lastControl && altCtrl.value() == e.lastControl.text())) {
                        altCtrl.value(e.control.text());
                    }

                    win.find('#src').value(e.control.value());
                }
            };
        }

        // General settings shared between simple and advanced dialogs
        var generalFormItems = [
            {name: 'src', type: 'filepicker', filetype: 'image', label: 'Source', autofocus: true},
            imageListCtrl,
            {name: 'alt', type: 'textbox', label: 'Image description'},
            {
                type: 'container',
                label: 'Dimensions',
                layout: 'flex',
                direction: 'row',
                align: 'center',
                spacing: 5,
                items: [
                    {name: 'width', type: 'textbox', maxLength: 3, size: 3, onchange: recalcSize},
                    {type: 'label', text: 'x'},
                    {name: 'height', type: 'textbox', maxLength: 3, size: 3, onchange: recalcSize},
                    {name: 'constrain', type: 'checkbox', checked: true, text: 'Constrain proportions'}
                ]
            },
            // Diem Extended injection
            {name: 'method', type: 'listbox', label: 'Resize method', values: [
                {text: 'None', value: ''},
                {text: 'Fit', value: 'fit'},
                {text: 'Scale', value: 'scale'},
                {text: 'Inflate', value: 'inflate'},
                {text: 'Center', value: 'center'},
                {text: 'Top', value: 'top'},
                {text: 'Right', value: 'right'},
                {text: 'Left', value: 'left'},
                {text: 'Bottom', value: 'bottom'}
            ]}
        ];

        function updateStyle() {
            function addPixelSuffix(value) {
                if (value.length > 0 && /^[0-9]+$/.test(value)) {
                    value += 'px';
                }

                return value;
            }

            var data = win.toJSON();
            var css = dom.parseStyle(data.style);

            delete css.margin;
            css['margin-top'] = css['margin-bottom'] = addPixelSuffix(data.vspace);
            css['margin-left'] = css['margin-right'] = addPixelSuffix(data.hspace);
            css['border-width'] = addPixelSuffix(data.border);

            win.find('#style').value(dom.serializeStyle(dom.parseStyle(dom.serializeStyle(css))));
        }

        if (editor.settings.image_advtab) {
            // Parse styles from img
            if (imgElm) {
                data.hspace = removePixelSuffix(imgElm.style.marginLeft || imgElm.style.marginRight);
                data.vspace = removePixelSuffix(imgElm.style.marginTop || imgElm.style.marginBottom);
                data.border = removePixelSuffix(imgElm.style.borderWidth);
                data.style = editor.dom.serializeStyle(editor.dom.parseStyle(editor.dom.getAttrib(imgElm, 'style')));
            }

            // Advanced dialog shows general+advanced tabs
            win = editor.windowManager.open({
                title: 'Edit image',
                data: data,
                bodyType: 'tabpanel',
                body: [
                    {
                        title: 'General',
                        type: 'form',
                        items: generalFormItems
                    },

                    {
                        title: 'Advanced',
                        type: 'form',
                        pack: 'start',
                        items: [
                            {
                                label: 'Style',
                                name: 'style',
                                type: 'textbox'
                            },
                            {
                                type: 'form',
                                layout: 'grid',
                                packV: 'start',
                                columns: 2,
                                padding: 0,
                                alignH: ['left', 'right'],
                                defaults: {
                                    type: 'textbox',
                                    maxWidth: 50,
                                    onchange: updateStyle
                                },
                                items: [
                                    {label: 'Vertical space', name: 'vspace'},
                                    {label: 'Horizontal space', name: 'hspace'},
                                    {label: 'Border', name: 'border'}
                                ]
                            }
                        ]
                    }
                ],
                onSubmit: onSubmitForm
            });
        } else {
            // Simple default dialog
            win = editor.windowManager.open({
                title: 'Edit image',
                data: data,
                body: generalFormItems,
                onSubmit: onSubmitForm
            });
        }

        // Diem Extended integration

        var $source = $('#' + win.find('#src')[0]._id + '-inp'),
            $hiddenField = $('<input type="hidden" id="' + $source.attr('id') + '-media-id' + '"></input>').appendTo($source.parent()),
            $resizeMethod = $('#' + win.find('#method')[0]._id),
            $resizeMethodContainer = $resizeMethod.closest('div.mce-container');
        if (data && data['data-dme-media-id']) {
            $hiddenField.val(data['data-dme-media-id']);
        }
        $source.change(function(){
            $hiddenField.val('');
            $resizeMethodContainer.css('display', 'none');
            win.find('#method')[0].value('');
        });
        $source.droppable({
            accept: '#dm_media_bar li.file.image',
            activeClass: 'droppable_active',
            hoverClass: 'droppable_hover',
            tolerance: 'touch',
            drop: function(e, ui) {
                $.ajax($.dm.ctrl.getHref('+/dmRichEditor/getDmMediaMetadata'), {
                    data: {
                        media_id: 'media:' + ui.draggable.attr('id').replace(/dmm/, '') + ' ' + ui.draggable.find('span.name:first').text().replace(/\s/g, '')
                    },
                    success: function(response){
                        $source.val(response.src);
                        win.find('#width')[0].value(response.width);
                        win.find('#height')[0].value(response.height);
                        win.find('#alt')[0].value(response.legend);
                        $hiddenField.val(response.id);
                        $resizeMethodContainer.css('display', 'block');
                        win.find('#method')[0].value('');
                    },
                    error: function(){
                        editor.windowManager.alert('There has been an server error with fetching media.');
                    }
                });
            }
        });
        if (data && data['data-dme-media-resize-method']) {
            $resizeMethodContainer.css('display', 'block');
            win.find('#method')[0].value(data['data-dme-media-resize-method']);
        } else if (data && data['data-dme-media-id']) {
            $resizeMethodContainer.css('display', 'block');
            win.find('#method')[0].value('');
        } else {
            $resizeMethodContainer.css('display', 'none');
            win.find('#method')[0].value('');
        }
    }

    editor.addButton('image', {
        icon: 'image',
        tooltip: 'Insert/edit image',
        onclick: showDialog,
        stateSelector: 'img:not([data-mce-object])'
    });

    editor.addMenuItem('image', {
        icon: 'image',
        text: 'Insert image',
        onclick: showDialog,
        context: 'insert',
        prependToContext: true
    });
});