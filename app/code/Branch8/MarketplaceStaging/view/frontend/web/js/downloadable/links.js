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
        'Branch8_MarketplaceStaging/js/downloadable/links': function (dataInfo) {
            registry.get('stagedownloadable', function (StageDownloadable) {
                var linkStageTemplate = '<tr>'+
                    '<td class="col-sort" data-role="stage-draggable-handle"><input data-container="link-order" type="hidden" ' +
                    'name="downloadable[link][<%- ' +
                    'data.id' +
                    ' %>][sort_order]" ' +
                    'value="<%- data.sort_order %>" class="input-text control-text sort" />' +
                    '<span class="stage-draggable-handle" title="'+$t('Sort Variations')+'"></span>' +
                    '</td>'+
                    '<td class="col-title">'+
                    '<input type="hidden" class="__delete__" '+
                    'name="downloadable[link][<%- data.id %>][is_delete]" value="" />'+
                    '<input type="hidden" name="downloadable[link][<%- data.id %>][link_id]"'+
                    ' value="<%- data.link_id %>"/>'+
                    '<input type="text" class="required-entry input-text control-text" '+
                    'name="downloadable[link][<%- data.id %>][title]" value="<%- data.title %>" />'+
                    '</td>'+
                    '<td class="col-price">'+
                    '<div class="control-addon">' +
                    '<input type="text" id="downloadable_staging_link_<%- data.id %>_price_value"'+
                    ' class="input-text control-text validate-number staging-link-prices"'+
                    'name="downloadable[link][<%- data.id %>][price]" value="<%- data.price %>" /> ' +
                    '<label class="addon-prefix"><span>'+dataInfo.currencySymbol+'</span>'+
                    '</label>' +
                    '</div>' +
                    '</td>' +
                    '<td class="col-file">'+
                    '<div class="field field-option">'+
                    '<input type="radio" class="control-radio validate-one-required-by-name"'+
                    'id="downloadable_staging_link_<%- data.id %>_file_type" name="downloadable[link][<%- data.id %>][type]"'+
                    'value="file"<%- data.file_checked %> />' +
                    '<label for="downloadable_staging_link_<%- data.id %>_file_type" '+
                    'class="field-label"><span>'+$t('File')+'</span></label>'+
                    '<input type="hidden" class="validate-downloadable-file" '+
                    'id="downloadable_staging_link_<%- data.id %>_file_save" '+
                    'name="downloadable[link][<%- data.id %>][file]" value="<%- data.file_save %>" />'+

                    '<div id="downloadable_staging_link_<%- data.id %>_file" class="field-uploader">'+
                    '<div id="downloadable_staging_link_<%- data.id %>_file-old" class="file-row-info"></div>'+
                    '<div id="downloadable_staging_link_<%- data.id %>_file-new" class="file-row-info new-file"></div>'+
                    '<div class="fileinput-button form-buttons">'+
                    '<span>'+$t('Browse Files...')+'</span>' +
                    '<input id="downloadable_staging_link_<%- data.id %>_file" type="file" name="links">' +
                    '<script>' +
                    'linksStageUploader("#downloadable_staging_link_<%- data.id %>_file", "'+dataInfo.uploadUrl+'"); ' +
                    '</script>'+
                    '</div>'+
                    '</div>'+
                    '</div>'+
                    '<div class="field field-option field-file-url">'+
                    '<input type="radio" class="control-radio validate-one-required-by-name"'+
                    'id="downloadable_staging_link_<%- data.id %>_url_type" '+
                    'name="downloadable[link][<%- data.id %>][type]" value="url"<%- data.url_checked %> />' +
                    '<label for="downloadable_staging_link_<%- data.id %>_url_type" class="field-label">'+
                    '<span>'+$t('URL')+'</span></label>' +
                    '<input type="text" class="validate-downloadable-url validate-url control-text" '+
                    'name="downloadable[link][<%- data.id %>][link_url]" value="<%- data.link_url %>"'+
                    ' placeholder="'+$t('URL')+'" />'+
                    '</div>'+
                    '<div>'+
                    '<span id="downloadable_staging_link_<%- data.id %>_link_container"></span>'+
                    '</div>'+
                    '</td>'+
                    '<td class="col-sample">'+
                    '<div class="field field-option">'+
                    '<input type="radio" class="control-radio" id="downloadable_staging_link_<%- data.id %>_sample_file_type"'+
                    'name="downloadable[link][<%- data.id %>][sample][type]" '+
                    'value="file"<%- data.sample_file_checked %> />' +
                    '<label for="downloadable_staging_link_<%- data.id %>_sample_file_type" class="field-label">'+
                    '<span>'+$t('File')+':</span></label>'+
                    '<input type="hidden" id="downloadable_staging_link_<%- data.id %>_sample_file_save" '+
                    'name="downloadable[link][<%- data.id %>][sample][file]" value="<%- data.sample_file_save %>"'+
                    ' class="validate-downloadable-file"/>'+
                    '<div id="downloadable_staging_link_<%- data.id %>_sample_file" class="field-uploader">'+
                    '<div id="downloadable_staging_link_<%- data.id %>_sample_file-old" class="file-row-info"></div>'+
                    '<div id="downloadable_staging_link_<%- data.id %>_sample_file-new"'+
                    ' class="file-row-info new-file"></div>'+
                    '<div class="fileinput-button form-buttons">'+
                    '<span>'+$t('Browse Files...')+'</span>' +
                    '<input id="downloadable_staging_link_<%- data.id %>_sample_file" type="file" name="link_samples">' +
                    '<script>'+
                    'linksStageUploader("#downloadable_staging_link_<%- data.id %>_sample_file", "'+dataInfo.uploadSampleUrl+'"); ' +
                    '</scr'+'ipt>'+
                    '</div>'+
                    '</div>'+
                    '</div>'+

                    '<div class="field field-option field-file-url">'+
                    '<input type="radio" class="control-radio validate-one-required-by-name" '+
                    'id="downloadable_staging_link_<%- data.id %>_sample_url_type" '+
                    'name="downloadable[link][<%- data.id %>][sample][type]" '+
                    'value="url"<%- data.sample_url_checked %> />' +
                    '<label for="downloadable_staging_link_<%- data.id %>_sample_url_type" '+
                    'class="field-label"><span>'+$t('URL')+'</span></label>'+
                    '<input type="text" class="validate-downloadable-url validate-url control-text"'+
                    'name="downloadable[link][<%- data.id %>][sample][url]"'+
                    'value="<%- data.sample_url %>" placeholder="'+$t('URL')+'" />'+
                    '</div>'+
                    '<div>'+
                    '<span id="downloadable_staging_link_<%- data.id %>_sample_container"></span>'+
                    '</div>'+
                    '</td>'+
                    '<td class="col-share">'+
                    '<select id="downloadable_staging_link_<%- data.id %>_shareable" class="control-select"'+
                    'name="downloadable[link][<%- data.id %>][is_shareable]">'+
                    '<option value="1">'+$t('Yes')+'</option>'+
                    '<option value="0">'+$t('No')+'</option>'+
                    '<option value="2" selected="selected">'+$t('Use config')+'</option>'+
                    '</select>'+
                    '</td>'+
                    '<td class="col-limit">' +
                    '<input type="text" id="downloadable_staging_link_<%- data.id %>_downloads"'+
                    'name="downloadable[link][<%- data.id %>][number_of_downloads]" '+
                    'class="input-text control-text downloads" value="<%- data.number_of_downloads %>" />'+
                    '<div class="field field-option">' +
                    '<input type="checkbox" class="control-checkbox" '+
                    'id="downloadable_staging_link_<%- data.id %>_is_unlimited" '+
                    'name="downloadable[link][<%- data.id %>][is_unlimited]" value="1" <%- data.is_unlimited %> />' +
                    '<label for="downloadable_staging_link_<%- data.id %>_is_unlimited" '+
                    'class="field-label"><span>'+$t('Unlimited')+'</span></label>' +
                    '</div>' +
                    '</td>'+
                    '<td class="col-action">'+
                    '<button id="downloadable_staging_link_<%- data.id %>_delete_button" type="button"'+
                    'class="action-delete" title="'+$t('Delete')+'">'+
                    '<span>'+$t('Delete')+'</span></button>'+
                    '</td>'+
                    '</tr>';

                var linkStageItems = {
                    tbody : jQuery('#link_staging_items_body'),
                    templateText : linkStageTemplate,
                    itemCount : 0,
                    add : function(data) {
                        alertStageAlreadyDisplayed = false;
                        this.template = mageTemplate(this.templateText);

                        if(!data.link_id){
                            data = {};
                            data.link_id  = 0;
                            data.link_type = 'file';
                            data.sample_type = 'none';
                            data.number_of_downloads = dataInfo.maxDownloads;
                            data.sort_order = this.itemCount + 1;
                        }

                        data.id = this.itemCount;

                        if (data.link_type == 'url') {
                            data.url_checked = ' checked="checked"';
                        } else if (data.link_type == 'file') {
                            data.file_checked = ' checked="checked"';
                        }
                        if (data.sample_type == 'url') {
                            data.sample_url_checked = ' checked="checked"';
                        } else if (data.sample_type == 'file') {
                            data.sample_file_checked = ' checked="checked"';
                        }

                        this.tbody.append(this.template({data: data}));

                        let scopeTitle = $('downloadable_staging_link_'+data.id+'_title');
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

                        let scopePrice = $('downloadable_staging_link_'+data.id+'_price');
                        if (scopePrice) {
                            Event.observe(scopePrice, 'click', function(event){
                                let scopeElm = $(Event.findElement(event, 'input'));
                                let priceField = scopeElm.up(0).down('input[type="text"]');
                                if (scopeElm.checked == true) {
                                    priceField.disabled = true;
                                } else {
                                    priceField.disabled = false;
                                }
                            });
                        }
                        if (!data.website_price && scopePrice) {
                            scopePrice.up(0).down('input[type="text"]').disabled = true;
                            scopePrice.checked = true;
                        }
                        let downloadsElm = $('downloadable_staging_link_'+data.id+'_downloads');
                        let isUnlimitedElm = $('downloadable_staging_link_'+data.id+'_is_unlimited');
                        if (data.is_unlimited) {
                            downloadsElm.disabled = true;
                        }
                        Event.observe(isUnlimitedElm, 'click', function(event){
                            let elm = Event.element(event);
                            elm.up('td').down('input[type="text"].downloads').disabled = elm.checked;
                        });

                        if (data.is_shareable) {
                            let options = $('downloadable_staging_link_'+data.id+'_shareable').options;
                            for (var i=0; i < options.length; i++) {
                                if (options[i].value == data.is_shareable) {
                                    options[i].selected = true;
                                }
                            }
                        }

                        let sampleUrl = $('downloadable_staging_link_'+data.id+'_sample_url_type');
                        let linkUrl = $('downloadable_staging_link_'+data.id+'_url_type');

                        if (!data.file_save) {
                            data.file_save = [];
                        }
                        if (!data.sample_file_save) {
                            data.sample_file_save = [];
                        }
                        // link file
                        new StageDownloadable.FileUploader(
                            'links',
                            'links_'+data.id,
                            linkUrl.up('td'),
                            'downloadable[link]['+data.id+']',
                            data.file_save,
                            'downloadable_staging_link_'+data.id+'_file',
                            dataInfo.uploadJsonData
                        );

                        // link sample file
                        new StageDownloadable.FileUploader(
                            'linkssample',
                            'linkssample_'+data.id,
                            sampleUrl.up('td'),
                            'downloadable[link]['+data.id+'][sample]',
                            data.sample_file_save,
                            'downloadable_staging_link_'+data.id+'_sample_file',
                            dataInfo.uploadJsonSampleData
                        );

                        let linkFile = $('downloadable_staging_link_'+data.id+'_file_type');
                        linkFile.advaiceContainer = 'downloadable_staging_link_'+data.id+'_link_container';
                        linkUrl.advaiceContainer = 'downloadable_staging_link_'+data.id+'_link_container';
                        $('downloadable_staging_link_'+data.id+'_file_save').advaiceContainer =
                            'downloadable_staging_link_'+data.id+'_link_container';

                        let sampleFile = $('downloadable_staging_link_'+data.id+'_sample_file_type');

                        this.itemCount++;
                        this.togglePriceFields();
                        this.bindRemoveButtons();
                    },
                    sorting: function () {
                        var list = this.tbody;
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
                    remove : function(event){
                        var element = $(Event.findElement(event, 'tr'));
                        alertStageAlreadyDisplayed = false;
                        if(element){
                            element.down('input[type="hidden"].__delete__').value = '1';
                            Element.select(element, 'div.flex').each(function(elm){
                                elm.remove();
                            });
                            element.addClassName('no-display');
                            element.addClassName('ignore-validate');
                            element.hide();
                        }
                    },
                    bindRemoveButtons : function(){
                        var buttons = $$('tbody#link_staging_items_body .action-delete');
                        for(var i=0;i<buttons.length;i++){
                            if(!$(buttons[i]).binded && !$(buttons[i]).hasClassName('disabled')){
                                $(buttons[i]).binded = true;
                                Event.observe(buttons[i], 'click', this.remove.bind(this));
                            }
                        }
                    },
                    togglePriceFields : function(){
                        var toogleTo = jQuery('#staging-link-switcher1').is(':checked');
                        var disableFlag = true;
                        if (toogleTo) {
                            disableFlag = false;
                        }
                        $$('.staging-link-prices[type="text"]').each(function(elm){
                            var flag = disableFlag;
                            if (elm.hasClassName('disabled')) {
                                flag = true;
                            }
                            elm.disabled = flag;
                        });
                    }
                };

                linkStageItems.sorting();
                linkStageItems.bindRemoveButtons();

                window.linksStageUploader = function (id, url) {
                    (function ($) {
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
                                    if ($(id + ' .progressbar-container').length) {
                                        $(id + ' .progressbar-container').parent().remove();
                                    }

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
                                console.log(data.result.error);
                                if (data.result.error) {
                                    alert(data.result.error);
                                    $(progressSelector).removeClass('upload-progress').addClass('upload-failure');
                                    var errorMsg = '<span class="file-info-error">' + data.result.error + '</span>';
                                    $('#' + data.fileId + ' .file-info').append(errorMsg);
                                }
                                var progressSelector = '#' + data.fileId + ' .progressbar-container .progressbar';
                                $(progressSelector).css('width', '100%');
                                if (data.result && !data.result.hasOwnProperty('errorcode')) {
                                    $(progressSelector).removeClass('upload-progress').addClass('upload-success');
                                    new StageDownloadable.FileList(id.substr(1), null).handleUploadComplete(data.result);
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
                    })(jQuery);
                };

                if (jQuery('.staging-link-switcher input[name="product[links_purchased_separately]"]')) {
                    jQuery('.staging-link-switcher input[name="product[links_purchased_separately]"]').on('change', function () {
                        var toogleTo = jQuery('#staging-link-switcher1').is(':checked');
                        var disableFlag = true;
                        if (toogleTo) {
                            disableFlag = false;
                        }
                        $$('.staging-link-prices[type="text"]').each(function(elm){
                            var flag = disableFlag;
                            if (elm.hasClassName('disabled')) {
                                flag = true;
                            }
                            elm.disabled = flag;
                        });
                    });
                }

                if($('add_link_staging_item')) {
                    Event.observe('add_link_staging_item', 'click', linkStageItems.add.bind(linkStageItems));
                }

                jQuery.each(dataInfo.downloadableLinkInfo, function(key, value) {
                    linkStageItems.add(value);
                });

            });
        }
    };
});
