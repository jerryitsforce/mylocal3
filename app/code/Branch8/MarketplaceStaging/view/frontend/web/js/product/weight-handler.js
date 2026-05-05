define([
    'jquery',
    'mage/translate',
    'uiRegistry',
    'plugins/DOMPurify',
    "mage/calendar",
    "mage/adminhtml/wysiwyg/tiny_mce/setup"
], function ($, $t, registry, DOMPurify) {
    'use strict';

    return {

        $weightSwitcher: '[data-role=staging-weight-switcher]',
        $weight: '#staging-weight',
        flagInit: false,
        wysiwygUrl: '',
        wysiwygList: [],

        /**
         * Hide weight switcher
         */
        hideWeightSwitcher: function () {
            $('body').find(this.$weightSwitcher).hide();
        },

        /**
         * Is locked
         * @returns {*}
         */
        isLocked: function () {
            return $('body').find(this.$weight).is('[data-locked]');
        },

        /**
         * Disabled
         */
        disabled: function () {
            $('body').find(this.$weight).removeClass('required-entry');
            $('body').find(this.$weight).removeClass('mage-error');
            $('body').find(this.$weightSwitcher + ' #weight-error').remove();
            if (!$('#staging_weight_type').length) $('body').find(this.$weight).addClass('ignore-validate').prop('disabled', true);
        },

        /**
         * Enabled
         */
        enabled: function () {
            $('body').find(this.$weight).addClass('required-entry');
            $('body').find(this.$weight).removeClass('ignore-validate').prop('disabled', false);
        },

        /**
         * Switch Weight
         * @returns {*}
         */
        switchWeight: function () {
            return this.productHasWeight() ? this.enabled() : this.disabled();
        },

        /**
         * Product has weight
         * @returns {Bool}
         */
        productHasWeight: function () {
            return $('body').find(this.$weightSwitcher + ' input:checked').val() === '1';
        },

        /**
         * Notify product weight is changed
         * @returns {*|jQuery}
         */
        notifyProductWeightIsChanged: function () {
            return $('body').find(this.$weightSwitcher + ' input:checked').trigger('change');
        },

        /**
         * Change
         * @param {String} data
         */
        change: function (data) {
            var value = data !== undefined ? +data : !this.productHasWeight();

            $('body').find(this.$weightSwitcher + ' input[value=' + value + ']').prop('checked', true);
        },

        initWysiwyg: function () {
            var self = this;
            $('body').find('.init-mce').each(function () {
                self.wysiwygList['wysiwygObj'+$(this).attr('id')] = new wysiwygSetup($(this).attr('id'),self.wysiwygSettings );
                self.wysiwygList['wysiwygObj'+$(this).attr('id')].setup("exact");
            });

            $('body').on('click', '.stg_toggle_editor', function(){
                var objId = $(this).attr('wysiwyg-obj');
                self.wysiwygList[objId].toggle();
            });
        },

        /**
         * Constructor component
         */
        'Branch8_MarketplaceStaging/js/product/weight-handler': function (config) {
            if (!this.flagInit) {
                this.flagInit = true;
                this.bindAll();
            }
            this.wysiwygUrl = config.wysiwygUrl;
            this.wysiwygSettings = config.wysiwygSettings;
            this.switchWeight();
            this.initWysiwyg();
            let currentDate = new Date();
            var jQ = $.noConflict();
            $.extend(true, $, {
                calendarConfig: {
                    serverTimezoneSeconds  : config.serverTimezoneSeconds,
                    serverTimezoneOffset  : config.serverTimezoneOffset,
                }
            });
            jQ('body').find('.datepicker').each(function () {
                let idField = DOMPurify.sanitize($(this).attr('id'));
                jQ('#'+idField).calendar({
                    dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    changeYear: true,
                    changeMonth: true,
                    showsTime: false,
                    showOtherMonths: true,
                    buttonText: $t('Select Date'),
                    currentText: $t('Go Today'),
                    closeText: $t('Close'),
                    showOn: 'button'
                });
            });
            jQ('body').find('.datetimepicker').each(function () {
                let idField = DOMPurify.sanitize($(this).attr('id'));
                jQ('#'+idField).calendar({
                    dayNamesMin: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                    changeYear: true,
                    changeMonth: true,
                    showsTime: true,
                    showOtherMonths: true,
                    timeFormat: 'h:mm TT',
                    buttonText: $t('Select Date'),
                    currentText: $t('Go Today'),
                    closeText: $t('Close'),
                    showOn: 'button'
                });
            });
            jQ('body').on('change', 'input[name="staging[mode]"]', function () {
                $('.stage-mode').hide();
                jQ('#mode-'+ DOMPurify.sanitize($(this).val())).show();
            });
        },

        /**
         * Bind all
         */
        bindAll: function () {
            $('body').on('change', this.$weightSwitcher + ' input', this.switchWeight.bind(this));
        }
    };
});
