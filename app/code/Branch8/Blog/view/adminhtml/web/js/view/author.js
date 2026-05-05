/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal',
    'mage/translate',
    'plugins/DOMPurify'
], function ($, modal, $t, DOMPurify) {
    "use strict";

    $.widget('b8.blogAuthor', {
        options: {
            url: ''
        },
        isloaded: false,

        /**
         * This method constructs a new widget.
         * @private
         */
        _create: function () {
            this.initCustomerGrid();
            this.selectCustomer();
        },

        /**
         * Init popup
         * Popup will automatic open
         */
        initPopup: function () {
            var options = {
                type: 'popup',
                responsive: true,
                innerScroll: true,
                title: $t('Select Customer'),
                buttons: []
            },
            customerGridEl = $('#customer-grid');

            modal(options, customerGridEl);
            customerGridEl.modal('openModal');
        },

        /**
         * Init select customer
         */
        selectCustomer: function () {
            $('body').delegate('#customer-grid_table tbody tr', 'click', function () {
                var first_name = $(this).find('td:nth-child(3)').text().trim(),
                    last_name = $(this).find('td:nth-child(4)').text().trim();

                $("#author_customer_id").val($(this).find('input').val().trim());
                $("#author_customer").val(first_name+' '+last_name);
                $('#customer-grid').data('mageModal').closeModal();
            });
        },

        /**
         * Init customer grid
         */
        initCustomerGrid: function () {
            var self = this,
                defaultConfig = {
                    ALLOWED_TAGS: ['b', 'i', 'u', 'em', 'strong', 'br', 'div', 'span', 'p', 'label', 'button', 'table', 'thead', 'tbody', 'tr', 'th', 'td', 'select', 'option', 'input'],
                    ALLOWED_ATTR: ['class', 'style', 'id', 'title', 'type', 'name', 'value', 'href', 'colspan', 'rowspan', 'data-ui-id', 'for', 'selected', 'data-role', 'data-direction', 'data-sort'],
                    FORBID_TAGS: ['iframe', 'style', 'link', 'object', 'embed'],
                    FORBID_ATTR: ['onerror', 'onload', 'onmouseover'],
                    ADD_TAGS: ['script'],
                    ALLOW_DATA_ATTR: false,
                    SAFE_FOR_TEMPLATES: true
                };

            $("#author_customer").click(function () {
                $.ajax({
                    method: 'POST',
                    url: self.options.url,
                    data: {form_key: window.FORM_KEY},
                    showLoader: true
                }).done(function (response) {
                    const content = DOMPurify.sanitize(response, defaultConfig);
                    $('#customer-grid').html(content);
                    self.initPopup();
                });
            });
        }
    });

    return $.b8.blogAuthor;
});

