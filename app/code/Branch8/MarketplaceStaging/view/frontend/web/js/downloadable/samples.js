/*jshint browser:true jquery:true expr:true*/
define([
    'jquery',
    'uiRegistry',
    'mage/translate',
    'mage/template',
    'jquery/ui',
    'prototype'
], function (jQuery, registry, $t, mageTemplate) {
    'use strict';

    return {
        /**
         * Constructor component
         * @param {Object} dataInfo - this backend data
         */
        'Branch8_MarketplaceStaging/js/downloadable/samples': function (dataInfo) {
            registry.get('stagedownloadable', function (StageDownloadable) {
                var sampleStageTemplate = '<tr>'+
                    '<td class="col-sort" data-role="stage-draggable-handle">' +
                    '<input data-container="link-order" type="hidden" name="downloadable[sample][<%- data.id %>][sort_order]"'+
                    'value="<%- data.sort_order %>" class="sort" />' +
                    '<span class="stage-draggable-handle" title="' + $t('Sort Variations') + '"></span>' +
                '</td>'+
                '<td class="col-title">'+
                '<input type="hidden" class="__delete__" name="downloadable[sample][<%- data.id %>][is_delete]" '+
                'value="" />'+
                '<input type="hidden" name="downloadable[sample][<%- data.id %>][sample_id]" '+
                'value="<%- data.sample_id %>" />'+
                '<input type="text" class="required-entry input-text control-text" '+
                'name="downloadable[sample][<%- data.id %>][title]" value="<%- data.title %>" />'+
                '</td>'+
                '<td class="col-file">'+
                '<div class="field field-option">'+
                '<input type="radio" class="control-radio validate-one-required-by-name" '+
                'id="downloadable_staging_sample_<%- data.id %>_file_type" name="downloadable[sample][<%- data.id %>][type]" '+
                'value="file"<%- data.file_checked %> />' +
                '<label for="downloadable_staging_sample_<%- data.id %>_file_type" class="field-label">'+
                '<span>' + $t('File') + ':</span></label>'+
                '<input type="hidden" class="validate-downloadable-file" '+
                'id="downloadable_staging_sample_<%- data.id %>_file_save" '+
                'name="downloadable[sample][<%- data.id %>][file]" value="<%- data.file_save %>" />'+
                '<div id="downloadable_staging_sample_<%- data.id %>_file" class="field-uploader">'+
                '<div id="downloadable_staging_sample_<%- data.id %>_file-old" class="file-row-info"></div>'+
                '<div id="downloadable_staging_sample_<%- data.id %>_file-new" class="file-row-info new-file"></div>'+
                '<div class="fileinput-button">'+
                '<span>' + $t('Browse Files...') +'</span>' +
                '<input id="downloadable_staging_sample_<%- data.id %>_file" type="file" '+
                'name="samples" data-url="' + dataInfo.uploadUrl + '">' +
                '<script>' +
                'sampleStageUploader("#downloadable_staging_sample_<%- data.id %>_file","' + dataInfo.uploadUrl + '"); ' +
                '</scr'+'ipt>'+
                '</div>'+
                '</div>'+
                '</div>'+
                '<div class="field field-option field-file-url">'+
                '<input type="radio" class="control-radio validate-one-required-by-name"'+
                'id="downloadable_staging_sample_<%- data.id %>_url_type" name="downloadable[sample][<%- data.id %>][type]"'+
                'value="url"<%- data.url_checked %> />' +
                '<label for="downloadable_staging_sample_<%- data.id %>_url_type" class="field-label">'+
                '<span>' + $t('URL') + '</span></label>' +
                '<input type="text" class="validate-downloadable-url validate-url control-text" '+
                'name="downloadable[sample][<%- data.id %>][sample_url]" value="<%- data.sample_url %>" '+
                'placeholder="' + $t('URL') + '" />'+
                '</div>'+
                '<div>'+
                '<span id="downloadable_staging_sample_<%- data.id %>_container"></span>'+
                '</div>'+
                '</td>'+
                '<td class="col-actions">'+
                '<button type="button" class="action-delete" title="' + $t('Delete') + '">'+
                '<span>' + $t('Delete') + '</span></button>'+
                '</td>'+
                '</tr>';

                var sampleStageItems = {
                    tbody: $('sample_staging_items_body'),
                    templateText: sampleStageTemplate,
                    itemCount: 0,
                    add: function(data) {
                        alertStageAlreadyDisplayed = false;
                        this.template = mageTemplate(this.templateText);

                        if(!data.sample_id) {
                            data = {};
                            data.sample_type = 'file';
                            data.sample_id  = 0;
                            data.sort_order = this.itemCount + 1;
                        }

                        data.id = this.itemCount;

                        if (data.sample_type == 'url') {
                            data.url_checked = ' checked="checked"';
                        } else if (data.sample_type == 'file') {
                            data.file_checked = ' checked="checked"';
                        }

                        Element.insert(this.tbody, {
                            'bottom': this.template({
                                data: data
                            })
                        });

                        let scopeTitle = $('downloadable_staging_sample_'+data.id+'_title');
                        if (scopeTitle) {
                            Event.observe(scopeTitle, 'click', function(event){
                                let scopeElm = $(Event.findElement(event, 'input'));
                                let titleField = scopeElm.up(0).down('input[type="text"]');
                                if (scopeElm.checked == true) {
                                    titleField.disabled = true;
                                } else {
                                    titleField.disabled = false;
                                }
                            });
                        }
                        if (!data.store_title && scopeTitle) {
                            scopeTitle.up(0).down('input[type="text"]').disabled = true;
                            scopeTitle.checked = true;
                        }

                        let sampleUrl = $('downloadable_staging_sample_'+data.id+'_url_type');

                        if (!data.file_save) {
                            data.file_save = [];
                        }
                        new StageDownloadable.FileUploader(
                            'samples',
                            data.id,
                            sampleUrl.up('td').down('div.field-uploader'),
                            'downloadable[sample]['+data.id+']',
                            data.file_save,
                            'downloadable_staging_sample_'+data.id+'_file',
                            dataInfo.uploadJsonSampleData
                    );
                        sampleUrl.advaiceContainer = 'downloadable_staging_sample_'+data.id+'_container';
                        let sampleFile = $('downloadable_staging_sample_'+data.id+'_file_type');
                        sampleFile.advaiceContainer = 'downloadable_staging_sample_'+data.id+'_container';
                        $('downloadable_staging_sample_'+data.id+'_file_save').advaiceContainer =
                            'downloadable_staging_sample_'+data.id+'_container';

                        this.itemCount++;
                        this.bindRemoveButtons();
                    },
                    sorting: function () {
                        var list = jQuery(this.tbody);
                        list.sortable({
                            axis: 'y',
                            handle: '[data-role=stage-draggable-handle]',
                            items: 'tr',
                            update: function (event, data) {
                                list.find('[data-container=link-order]').each(function (i, el) {
                                    jQuery(el).val(i + 1);
                                });
                            },
                            tolerance: 'pointer'
                        });
                    },
                    remove: function(event) {
                        var element = $(Event.findElement(event, 'tr'));
                        alertStageAlreadyDisplayed = false;
                        if(element){
                            element.down('input[type="hidden"].__delete__').value = '1';
                            element.addClassName('no-display');
                            element.addClassName('ignore-validate');
                            element.hide();
                        }
                    },
                    bindRemoveButtons: function() {
                        var buttons = $$('tbody#sample_staging_items_body .action-delete');
                        for(var i=0;i<buttons.length;i++){
                            if(!$(buttons[i]).binded){
                                $(buttons[i]).binded = true;
                                Event.observe(buttons[i], 'click', this.remove.bind(this));
                            }
                        }
                    }
                };

                sampleStageItems.sorting();
                sampleStageItems.bindRemoveButtons();

                window.sampleStageUploader = function (id, url) {
                    (function ($, id) {
                        $(id).fileupload({
                            dataType: 'json',
                            url: url,
                            sequentialUploads: true,
                            maxFileSize: 2000000,
                            add: function (e, data) {
                            var progressTmpl = mageTemplate(id + '-template'),
                                fileSize,
                                tmpl;

                            $.each(data.files, function (index, file) {
                                fileSize = typeof file.size == "undefined" ?
                                    $.mage.__('We could not detect a size.') :
                                    byteConvert(file.size);

                                data.fileId = Math.random().toString(36).substr(2, 9);

                                tmpl = progressTmpl({
                                    data: {
                                        name: file.name,
                                        size: fileSize,
                                        id: data.fileId
                                    }
                                });

                                $(tmpl).appendTo(id);
                            });

                            $(this).fileupload('process', data).done(function () {
                                data.submit();
                            });
                        },
                        done: function (e, data) {
                            var progressSelector = '#' + data.fileId + ' .progressbar-container .progressbar';
                            $(progressSelector).css('width', '100%');
                            if (data.result && !data.result.hasOwnProperty('errorcode')) {
                                $(progressSelector).removeClass('upload-progress').addClass('upload-success');
                                new Downloadable.FileList(id.substr(1), null).handleUploadComplete(data.result);
                            } else {
                                $(progressSelector).removeClass('upload-progress').addClass('upload-failure');
                                var errorMsg = '<span class="file-info-error">' + data.result.error + '</span>';
                                $('#' + data.fileId + ' .file-info').append(errorMsg);
                            }
                        },
                        progress: function (e, data) {
                            var progress = parseInt(data.loaded / data.total * 100, 10);
                            var progressSelector = '#' + data.fileId + ' .progressbar-container .progressbar';
                            $(progressSelector).css('width', progress + '%');
                        },
                        fail: function (e, data) {
                            var progressSelector = '#' + data.fileId + ' .progressbar-container .progressbar';
                            $(progressSelector).removeClass('upload-progress').addClass('upload-failure');
                            if (data.result && data.result.hasOwnProperty('errorcode')) {
                                var errorMsg = '<span class="file-info-error">' + data.result.error + '</span>';
                                $('#' + data.fileId + ' .file-info').append(errorMsg);
                            }
                        }
                    });
                    })(jQuery, id);
                };

                if($('add_sample_staging_item')){
                    Event.observe('add_sample_staging_item', 'click', sampleStageItems.add.bind(sampleStageItems));
                }

                jQuery.each(dataInfo.downloadableLinkInfo, function(key, value) {
                    sampleStageItems.add(value);
                });

            });
        }
    };
});
