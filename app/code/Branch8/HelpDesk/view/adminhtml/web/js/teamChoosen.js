define([
    'jquery',
    'underscore',
    'jquery/ui',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function ($, _) {
    'use strict';
    /**
     *
     */
    $.widget('mage.teamChoosen', {
        options: {
            defaultTeamId: '',
            defaultUserId: '',
            teamJsonOptions: {},
            teamSelect: "#ticket_team_id",
            userSelect: "#ticket_user_id",
        },

        /**
         * @private
         */
        _create: function () {
            this._bind();
        },

        /**
         * @private
         */
        _bind: function () {
            var self = this;
            $(document).on('change',
                this.options.teamSelect,
                $.proxy(this.teamChange, this)
            );
            setTimeout(function () {
                $(self.options.teamSelect).val(
                    self.options.defaultTeamId
                ).trigger('change');
            }, 3000)

        },
        /**
         *
         */
        teamChange: function (element) {
            const value = $(element.target).val() || '',
                optionsList = this.options.teamJsonOptions[value] || this.options.teamJsonOptions[0];
            console.log({
                teamjsonOptions:this.options.teamJsonOptions
            })
            this.buildOptionsForSelect(
                $(this.options.userSelect),
                optionsList,
                this.options.defaultUserId
            );
        },
        /**
         *
         * @param element
         * @param optionsList
         */
        buildOptionsForSelect: function (element, optionsList, selectedValue) {
            element.find('option')
                .remove()
                .end();
            $.each(optionsList, function (i, item) {
                /// let selected = item.value == selectedValue ? 'selected=true' : '';
                let optionHtml = '<option value="' + item.value + '"';
                if (item.value == selectedValue) {
                    optionHtml +=' selected '
                }
                optionHtml += ' >'
                optionHtml += item.label + '</option>'
                element.append(optionHtml)
            });
        }
    });

    return $.mage.teamChoosen;

});
