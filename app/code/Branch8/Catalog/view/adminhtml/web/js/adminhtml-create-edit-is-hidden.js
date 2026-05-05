require([
    'jquery',
    'Branch8_Catalog/js/adminhtml-voucher-lookup'
], function($, voucherLookup) {
    'use strict';

    let isHiddenField = 'select[name="product[is_hidden]"]';
    let disableTaxClass = 'select[name="product[tax_class_id]"]';
    let statusField = '.page-content input[name="product[status]"]';
    let statusFieldStaging = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[status]"]';
    let shortDesField = 'textarea[name="product[short_description]"]';
    let nameField = 'input[name="product[name]"]';
    let skuField = 'input[name="product[sku]"]';
    let isOfflineOperationField = '.page-content select[name="product[is_offline_operation]"]';
    let isOfflineOperationFieldStaging = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal select[name="product[is_offline_operation]"]';
    let exchangeHintField = '.page-content input[name="product[exchange_hint]"]';
    let exchangeHintFieldStaging = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[exchange_hint]"]';
    let exchangeUrlField = '.page-content input[name="product[exchange_url]"]';
    let exchangeUrlFieldStaging = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal input[name="product[exchange_url]"]';
    let virtualProductTypeField = '.page-content select[name="product[virtual_product_type]"]';
    let virtualProductTypeFieldStaging = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal select[name="product[virtual_product_type]"]';
    let customOptionArea = '.page-content div[data-index="custom_options"]';
    let customOptionAreaStaging = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="custom_options"]';
    let maxLength = 30;
    let virtualSetMap = {
        'ticket': 1,
        'ticket_yoxi': 2,
        'ticket_edenred': 3,
        'ticket_fami': 4,
        'ticket_redeem': 5,
        'ticket_non_redeem': 6,
        'ticket_7ELEVEN': 7,
        'ticket_openhub': 8,
    };
    let virtualImportSetMap = {
        'ticket_yoxi': 2,
        'ticket_fami': 4,
        'ticket_redeem': 5,
        'ticket_non_redeem': 6
    };

    jQuery(document).ajaxComplete(function() {
        if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="attribute_set_id"]').length) {
            $('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="attribute_set_id"]').hide();
        }
        if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="quantity_and_stock_status"]').length) {
            $('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="quantity_and_stock_status"]').hide();
        }
        if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="gallery"]').length) {
            $('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="gallery"]').hide();
        }
        if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="configurable"]').length) {
            $('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="configurable"]').hide();
        }
        if ($('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="salable_qty"]').length) {
            $('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal div[data-index="salable_qty"]').hide();
        }
        if ($(customOptionAreaStaging).length) {
            $(customOptionAreaStaging).hide();
        }
        if ($('div[data-index="product_has_weight"]').length) {
            $('div[data-index="product_has_weight"]').hide();
        }
        if ($('div[data-index="use_config_is_returnable"]').length) {
            $('input[name="product[use_config_is_returnable]"]').prop('checked', false).trigger('change');
            $('div[data-index="use_config_is_returnable"]').hide();
        }
        toggleStatus($(statusField).val(), 'edit');
        toggleStatus($(statusFieldStaging).val(), 'staging');

        $(statusField).on('change', function() {
            let productStatus = $(statusField).val();
            toggleStatus(productStatus, 'edit');
        });
        $(statusFieldStaging).on('change', function() {
            let productStatus = $(statusFieldStaging).val();
            toggleStatus(productStatus, 'staging');
        });
        if ($(isOfflineOperationField).val() > 0) {
            $(exchangeUrlField).parent().parent().toggle(false);
        } else {
            if ($(exchangeUrlField).val() && $(exchangeUrlField).val().trim()) {
                $(isOfflineOperationField).find('option[value="1"]').prop('disabled', true);
            }
        }
        if ($(isOfflineOperationFieldStaging).val() > 0) {
            $(exchangeUrlFieldStaging).parent().parent().toggle(false);
        } else {
            if ($(exchangeUrlFieldStaging).val() && $(exchangeUrlFieldStaging).val().trim()) {
                $(isOfflineOperationFieldStaging).find('option[value="1"]').prop('disabled', true);
            }
        }
        $('body').on('change', isOfflineOperationField, function() {
            if ($(this).val() > 0) {
                $(exchangeHintField).parent().parent().toggle(false);
                $(exchangeUrlField).parent().parent().toggle(false);
            } else {
                $(exchangeHintField).parent().parent().toggle(true);
                $(exchangeUrlField).parent().parent().toggle(true);
            }
        });
        $('body').on('change', isOfflineOperationFieldStaging, function() {
            if ($(this).val() > 0) {
                $(exchangeHintFieldStaging).parent().parent().toggle(false);
                $(exchangeUrlFieldStaging).parent().parent().toggle(false);
            } else {
                $(exchangeHintFieldStaging).parent().parent().toggle(true);
                $(exchangeUrlFieldStaging).parent().parent().toggle(true);
            }
        });
        $('body').on('change', exchangeUrlField, function() {
            if (!$(this).val() || !$(this).val().trim()) {
                $(isOfflineOperationField).find('option[value="1"]').prop('disabled', false);
            } else {
                $(isOfflineOperationField).find('option[value="1"]').prop('disabled', true);
            }
        });
        $('body').on('change', exchangeUrlFieldStaging, function() {
            if (!$(this).val() || !$(this).val().trim()) {
                $(isOfflineOperationFieldStaging).find('option[value="1"]').prop('disabled', false);
            } else {
                $(isOfflineOperationFieldStaging).find('option[value="1"]').prop('disabled', true);
            }
        });
        let attributeSetName = $('.page-content [data-index="attribute_set_id"]').find('.admin__action-multiselect-text').text();
        let attributeSetNameStaging = $('.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal [data-index="attribute_set_id"]').find('.admin__action-multiselect-text').text();
        $(virtualProductTypeField).prop('disabled', true);
        $(virtualProductTypeFieldStaging).prop('disabled', true);
        $(disableTaxClass).prop('disabled', true);
        if (attributeSetName) {
            if (attributeSetName in virtualSetMap) {
                $(virtualProductTypeField).val(virtualSetMap[attributeSetName]).change();
            } else {
                $(virtualProductTypeField).val(1).change();
            }
            if (attributeSetName in virtualImportSetMap) {
                $(customOptionArea).hide();
            } else {
                $(customOptionArea).show();
            }

            // 如果是 ticket_7ELEVEN，增加 GUID 查詢按鈕
            if (attributeSetName === 'ticket_7ELEVEN') {
                voucherLookup.addGuidLookupButton();
            }
        }
        if (attributeSetNameStaging) {
            if (attributeSetNameStaging in virtualSetMap) {
                $(virtualProductTypeFieldStaging).val(virtualSetMap[attributeSetNameStaging]).change();
            } else {
                $(virtualProductTypeFieldStaging).val(1).change();
            }
        }
    });
    $('body').on('input focus keydown keyup', shortDesField, function() {
        //get Textearea text
        let text = $(this).val();
        //Split with \n carriage return
        let lines = text.split("\n");

        for (let i = 0; i < lines.length; i++) {
            if (lines[i].length > maxLength) {
                lines[i] = lines[i].substring(0, maxLength);
            }
        }
        while (lines.length > 3){
            lines.pop();
        }
        //Join with \n.
        //Set textarea
        $(this).val(lines.join("\n"));
    });
    $('body.catalog-product-new').on('keyup', nameField, function() {
        //get seller id
        let prefix = false,
            val = $(this).val();
        if (val){
            if (val.indexOf('HOTAI') !== -1) {
                let check = val.split('-');
                if (check[0].length < 6) {
                    prefix = true;
                }
            } else {
                prefix = true;
            }
            if (prefix) {
                $(skuField).val('HOTAI' + Math.floor(Date.now() / 1000) + '-' + val);
            }
        }
    });
    $('body').on('blur', 'input[name="product[name]"]', function() {

        var pNameValue = $(this).val();
        pNameValue = removeTags(pNameValue);
        if (pNameValue) {
            $(this).val(pNameValue);
            $(this).trigger('change');
        }
    });
    function removeTags(str) {
        if ((str === null) || (str === ""))
            return false;
        else
            str = str.toString();
        return str.replace( /(<([^>]+)>)/ig, "");
    }

    $('body').on('keyup', skuField, function() {
        //get seller id
        let prefix = false,
            val = $(this).val();
        if (val){
            if (val.indexOf('HOTAI') !== -1) {
                let check = val.split('-');
                if (check[0].length < 6) {
                    prefix = true;
                }
            } else {
                prefix = true;
            }
            if (prefix) {
                $(skuField).val('HOTAI' + Math.floor(Date.now() / 1000) + '-' + val);
            }
        }
    });

    function toggleStatus(productStatus, mode) {
        var status = parseInt(productStatus, 10);
        var prefix = '.page-content ';
        if(mode == 'staging'){
            prefix = '.catalogstaging_upcoming_form_catalogstaging_upcoming_form_catalogstaging_update_form_modal ';
        }
        //Start/ end date
        if(status == 2) {
            $(prefix+isHiddenField).parent().parent().toggle(true);
        }else if(status == 1){
            $(prefix+isHiddenField).parent().parent().toggle(false);
        }
    }
});
