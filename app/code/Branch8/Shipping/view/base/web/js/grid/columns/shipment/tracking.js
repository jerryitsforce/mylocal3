define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/grid/columns/column',
    'Magento_Ui/js/modal/modal',
    'text!Branch8_Shipping/template/tracking/displayRecordTemplate.html',
    'text!Branch8_Shipping/template/tracking/editRecordFormTemplate.html',
    'Branch8_Shipping/js/action/saveTracking',
    'mage/template',
    'plugins/DOMPurify'
], function ($, $t, Column, modal, displayTemplate, editTemplate, saveTracking, mageTemplate, DOMPurify) {
    'use strict';

    return Column.extend({
        defaults: {
            bodyTmpl: 'ui/grid/cells/html',
            recordTpl: '',
            domPurifyConfig: {
                ALLOWED_TAGS: ['table', 'tbody', 'tr', 'td', 'option', 'select', 'button', 'input'],
                ALLOWED_ATTR: ['class', 'style', 'id', 'data', 'data-url', 'data-role', 'data-id', 'data-carrier', 'data-shipment-id', 'data-tracking-number', 'name', 'value'],
                FORBID_TAGS: ['iframe', 'style', 'link', 'object', 'embed'],
                FORBID_ATTR: ['onerror', 'onload', 'onmouseover'],
                ALLOW_DATA_ATTR: true,
                SAFE_FOR_TEMPLATES: true
            },
            currentRow: null,
        },
        initialize: function () {
            this._super();
            this.initPopup();
            $(document).on('click', '[data-role=click-edit-tracking]', this.swapToEditForm.bind(this))
            $(document).on('click', '[data-role=cancel-edit-tracking]', this.swapToDisplayTemplate.bind(this))
            $(document).on('click', '[data-role=save-tracking]', this.saveTracking.bind(this))
            return this;
        },

        getCarriers: function (row) {
            return row[this.index + '_carriers'];
        },

        getTrackingListUrl: function (row) {
            return row[this.index + '_getlistaction'];
        },

        addTrackingUrl: function (row) {
            return row[this.index + '_addaction'];
        },

        removeTrackingUrl: function (row) {
            return row[this.index + '_removeaction'];
        },

        initPopup: function () {
            var trackingPopup = $('#tracking-popup');
            if (!trackingPopup.length) {
                $('body').append('<div id="tracking-popup" style="display:none;"></div>');
            }

            var options = {
                type: 'popup',
                responsive: true,
                title: $t('Manage Logistics Tracking Number'),
                buttons: []
            };
            this.trackingModal = modal(options, $('#tracking-popup'));
        },

        getFieldHandler: function (row) {
            if (row[this.index]) {
                return this.openPopup.bind(this, row);
            }
        },
        /**
         *
         * @param row
         */
        openPopup: function (row) {
            var self = this,
                orderId = row.entity_id || row.order_id;
            this.currentRow = row;
            $.ajax({
                url: self.getTrackingListUrl(row),
                type: 'GET',
                data: {order_id: orderId},
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        var trackingPopup = $('#tracking-popup');
                        trackingPopup.data('order-id', orderId);
                        trackingPopup.data('add-url', self.addTrackingUrl(row));
                        trackingPopup.data('remove-url', self.removeTrackingUrl(row));
                        trackingPopup.html(self.getPopupHtml(response.trackingData, row));
                        trackingPopup.modal('openModal');
                        self.initPopupActions();
                    } else {
                        alert(response.message);
                    }
                }
            });
        },

        getPopupHtml: function (trackingData, row) {
            var self = this,
                html = '<div id="tracking-error"></div>';
            html += '<table><thead><tr>' +
                '<th>' + $t('Carrier') + '</th>' +
                '<th class="logistics-provider-name-col" style="display:none;"></th>' +
                '<th>' + $t('Number') + '</th>' +
                '<th>' + $t('Action') + '</th>' +
                '</tr></thead><tbody>';

            html += '<tr>' +
                '<td><select id="tracking-title" name="title"></select></td>' +
                '<td class="logistics-provider-name-col" style="display:none;"><input type="text" id="tracking-lpname" name="logistics_provider_name"></td>' +
                '<td><input type="text" id="tracking-number" name="number"></td>' +
                '<td><button id="add-tracking" class="action-primary">' + $t('Add') + '</button></td>' +
                '</tr>';
            if (trackingData.length) {
                trackingData.forEach(function (track) {
                    html += '<tr data-track-id="' + track.id + '">';
                    html += self.createNewDisplayRecord({
                        id: track.id,
                        carrier: track.carrier,
                        tracking_number: track.tracking_number,
                        shipment_id: track.shipment_id
                    })
                    html += '</tr>';
                });
            }
            html += '</tbody></table>';
            setTimeout(function () {
                self.loadCarriers(row, '#tracking-title');
            }, 100);

            return html;
        },

        loadCarriers: function (row, selectId) {
            var $selectBox = $(selectId),
                jsonString = this.getCarriers(row),
                carriers = JSON.parse(jsonString);

            $selectBox.empty();

            $selectBox.append($('<option>', {
                value: '',
                text: $t('-- Please Select --'),
                selected: true,
                disabled: true
            }));

            $.each(carriers, function (name, url) {
                let option = $('<option>', {
                    value: name,
                    text: name,
                    'data-url': url
                });
                $selectBox.append(option);
            });
        },
        /**
         *
         * @param data
         * @returns {*}
         */
        createNewDisplayRecord: function (data) {
            return mageTemplate(displayTemplate, {
                data: data
            });
        },
        /**
         *
         * @param data
         * @returns {*}
         */
        createNewEditRecord: function (data) {
            const input = _.extend(data, {
                carrierList: JSON.parse(this.getCarriers(this.currentRow))
            })
            return mageTemplate(editTemplate, {
                data: input
            });
        },
        /**
         *
         */
        initPopupActions: function () {
            var self = this;
            $('#tracking-title').on('change', function () {
                if ($(this).val() === '其他：自行填寫名稱') {
                    $('.logistics-provider-name-col').show();
                } else {
                    $('.logistics-provider-name-col').hide();
                }
            });
            $('#add-tracking').on('click', function () {
                const errorBox = $('#tracking-error'),
                    popup = $('#tracking-popup'),
                    number = $('#tracking-number').val(),
                    logisticsProviderName = $('#tracking-lpname').val(),
                    orderId = popup.data('order-id'),
                    selectedTrackingTitle = $('#tracking-title').find(':selected'),
                    title = selectedTrackingTitle.val(),
                    logisticsCompanyUrl = selectedTrackingTitle.data('url'),
                    button = this;
                errorBox.hide();
                if (!title) {
                    self.showMessage(errorBox, $t('Please specify a carrier.'));
                    return;
                }

                if (!number) {
                    self.showMessage(errorBox, $t('Please enter a tracking number.'));
                    return;
                }
                $(button).prop('disabled', true);
                $.ajax({
                    url: popup.data('add-url'),
                    type: 'POST',
                    data: {
                        order_id: orderId,
                        title: title,
                        number: number,
                        logistics_provider_name: logisticsProviderName,
                        logistics_company_url: logisticsCompanyUrl
                    },
                    dataType: 'json',
                    showLoader: true,
                    success: function (response) {
                        if (response.success) {
                            let html = '<tr data-track-id="' + response.trackingData.id + '">';
                            html += self.createNewDisplayRecord(response.trackingData);
                            html += '</tr>';
                            const sanitizedData = DOMPurify.sanitize(html, self.domPurifyConfig);
                            $('#tracking-popup table tbody').append(sanitizedData);
                            $('#tracking-title').val('');
                            $('#tracking-number').val('');
                            $('#tracking-lpname').val('');
                            $('.logistics-provider-name-col').hide();
                        } else {
                            self.showMessage(errorBox, response.message);
                        }
                    }
                }).done(function () {
                    $(button).prop('disabled', false);
                });
            });
        },
        /**
         *
         * @param event
         */
        swapToEditForm: function (event) {
            const id = $(event.target).data('id'),
                tr = $('tr[data-track-id="' + id + '"]'),
                html = this.createNewEditRecord({
                    id: $(event.target).data('id'),
                    carrier: $(event.target).data('carrier'),
                    tracking_number: $(event.target).data('tracking-number'),
                    shipment_id: $(event.target).data('shipment-id')
                });
            tr.html(html);
        },
        /**
         *
         * @param event
         */
        swapToDisplayTemplate: function (event) {
            const id = $(event.target).data('id'),
                tr = $('tr[data-track-id="' + id + '"]'),
                html = this.createNewDisplayRecord({
                    id: $(event.target).data('id'),
                    carrier: $(event.target).data('carrier'),
                    tracking_number: $(event.target).data('tracking-number'),
                    shipment_id: $(event.target).data('shipment-id')
                });
            //hack
            const fragment = "<table><tr>" + html + "</tr></table>";
            const sanitizedData = DOMPurify.sanitize(fragment, this.domPurifyConfig).match(/<tr>([\s\S]*)<\/tr>/)[1];
            tr.html(sanitizedData);
        },
        /**
         *
         * @param event
         */
        saveTracking: function (event) {
            const self = this,
                id = $(event.target).data('id'),
                editTracking = $('#edit-tracking-carrier-' + id + ''),
                errorBox = $('#tracking-error'),
                selectedTrackingTitle = editTracking.find(':selected'),
                data = {
                    id: id,
                    carrier: editTracking.val(),
                    tracking_number: $('#edit-tracking-number-' + id).val(),
                    shipment_id: $(event.target).data('shipment-id'),
                    logistics_provider_name: '',
                    logistics_company_url: selectedTrackingTitle.data('url')
                };
            saveTracking(data).done(function (response) {
                if (!response.success) {
                    self.showMessage(errorBox, response.message);
                }
                const tr = $('tr[data-track-id="' + response.trackingData.id + '"]'),
                    html = self.createNewDisplayRecord(response.trackingData);
                tr.html(html);
                self.showMessage(errorBox, response.message, 'success');
            });
        },
        /**
         *
         * @param errorBox
         * @param message
         * @param type
         */
        showMessage: function (errorBox, message, type = 'err') {
            let cssClass = '';
            switch (type) {
                case 'err':
                    cssClass = 'tracking-error';
                    break;
                case 'success':
                    cssClass = 'tracking-success';
                    break;
                default:
                    cssClass = 'tracking-error';
                    break;
            }
            errorBox.text(message)
                .addClass(cssClass)
                .show();
            setTimeout(function () {
                errorBox.fadeOut();
            }, 4000);
        }
    });
});
