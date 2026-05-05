define(['jquery', 'mage/translate'], function ($) {
    return {
        ONLINE: 1,
        OFFLINE: 0,
        BUSY: 2,
        ONLINE_LABEL: $.mage.__('online'),
        OFFLINE_LABEL: $.mage.__('offline'),
        BUSY_LABEL: $.mage.__('busy'),
        /**
         *
         * @param value
         * @returns {*}
         */
        getChatStatusLabel: function (value) {
            let label = '';
            switch (parseInt(value)) {
                case this.ONLINE:
                    label = this.ONLINE_LABEL;
                    break;
                case this.OFFLINE:
                    label = this.OFFLINE_LABEL;
                    break;
                case this.BUSY:
                    label = this.BUSY_LABEL;
                    break;
                default:
                    label = this.OFFLINE_LABEL;
            }
            return label;
        },
        /**
         *
         * @param value
         */
        getChatStatusClasses: function (value) {
            let classes = '';
            switch (parseInt(value)) {
                case this.ONLINE:
                    classes = 'online';
                    break;
                case this.OFFLINE:
                    classes = 'offline';
                    break;
                case this.BUSY:
                    classes = 'busy';
                    break;
                default:
                    classes = 'online';
            }
            return classes;
        }
    }
})
