/**
 * @return widget
 */

define([
    'jquery',
    'underscore'
], function ($, _) {

    $.widget('am.brandsSearch', {
        options: {
            brands: null,
            enterButtonCode: 13,
            downArrowKeyCode: 40,
            upArrowKeyCode: 38,
            escapeKeyCode: 27
        },
        selectors: {
            input: '[data-ambrands-js="input"]',
            livesearch: '[data-ambrands-js="livesearch"]',
            clearButton: '[data-ambrands-js="clear"]'
        },
        classes: {
            active: '-active'
        },
        nodes: {
            resultItem: '<a class="ambrands-item" tabindex="{tabIndex}" href="{url}">{content}</a>'
        },

        /**
         * @returns {void}
         */
        _create: function () {
            this._initNodes();
            this._initListeners();
        },

        /**
         * @returns {void}
         */
        _initNodes: function () {
            this.input = this.element.find(this.selectors.input);
            this.livesearch = this.element.find(this.selectors.livesearch);
            this.clearButton = this.element.find(this.selectors.clearButton);
        },

        /**
         * @returns {void}
         */
        _initListeners: function () {
            this.clearButton.on('click', function () {
                this.clearSearch();
            }.bind(this));

            this.input.on('keydown', function (event) {
                const keycode =  this.getKeyCode(event);

                switch (keycode) {
                    case this.options.escapeKeyCode:
                        this.clearSearch();
                        break;
                    case this.options.enterButtonCode:
                        this.redirectToBrand();
                        break;
                    case this.options.downArrowKeyCode:
                    case this.options.upArrowKeyCode:
                        event.preventDefault();
                        this.livesearch.is(':visible') && this.focusNextListItem(keycode);
                        break;
                }
            }.bind(this));

            this.input.on('keyup', function (event) {
                const keycode = this.getKeyCode(event);
                const disallowedKeyCodes = [
                    this.options.escapeKeyCode,
                    this.options.enterButtonCode,
                    this.options.downArrowKeyCode,
                    this.options.upArrowKeyCode
                ];

                !disallowedKeyCodes.includes(keycode) && this.searchBrands(event.target.value);
            }.bind(this));

            this.livesearch.on('mouseover', function (event) {
                this.livesearch.children('.ambrands-item.active').removeClass('active');
            }.bind(this));
        },

        /**
         * @param {Event} event
         * @returns {String}
         */
        getKeyCode: function (event) {
            return event.keyCode ? event.keyCode : event.which;
        },

        /**
         * @param {Number} keycode
         * @returns {void}
         */
        focusNextListItem: function (keycode) {
            const listLength = this.livesearch.children('.ambrands-item').length,
                isUpDirection = keycode === this.options.upArrowKeyCode;

            if (!this.livesearch.children('.ambrands-item.active').length) {
                this.livesearch.find(isUpDirection ? ':last-child' : ':first-child').addClass('active');
            } else {
                let activeOptIndex = this.getTabIndexOfActiveItem();
                this.livesearch.children('.ambrands-item.active').removeClass('active');
                if (activeOptIndex !== (isUpDirection ? 1 : listLength)) {
                    activeOptIndex = isUpDirection
                        ? --activeOptIndex
                        : ++activeOptIndex
                    this.livesearch.children('[tabindex=' + activeOptIndex + ']')
                        .addClass('active');
                } else {
                    this.livesearch.find(isUpDirection ? ':last-child' : ':first-child').addClass('active');
                }
            }

            this.scrollToItem();
        },

        /**
         * @returns {void}
         */
        redirectToBrand: function () {
            const redirectUrl = this.livesearch.children().length === 1
                ? this.livesearch.find(':first-child')?.attr('href')
                : this.livesearch.children('[tabindex=' + this.getTabIndexOfActiveItem() + ']')?.attr('href');
            !!redirectUrl && (window.location.href = redirectUrl);
        },

        /**
         * @returns {Number}
         */
        getTabIndexOfActiveItem: function () {
            return parseInt(this.livesearch.children('.ambrands-item.active').attr('tabindex'));
        },

        /**
         * @returns {void}
         */
        scrollToItem: function () {
            const activeItem = this.livesearch.children('.ambrands-item.active');

            $(this.livesearch).scrollTop(
                $(this.livesearch).scrollTop()
                    - $(this.livesearch).offset().top
                    + $(activeItem).offset().top
            );
        },

        /**
         * @param {Object} element
         * @param {Boolean} state
         * @returns {void}
         */
        toggleElement: function (element, state) {
            element.toggleClass(this.classes.active, state);
        },

        /**
         * @param {String} str
         */
        searchBrands: function (str) {
            var brands = this.options.brands,
                livesearch = this.livesearch,
                closeButton = this.clearButton,
                foundBrands = {},
                url,
                result;

            str = str.trim().toLowerCase();

            this.toggleElement(closeButton, str.length !== 0);

            if (str.length === 0) {
                this.toggleElement(livesearch, false);

                return;
            }

            for (url in brands) {
                if (brands[url].toLowerCase().indexOf(str) !== -1) {
                    foundBrands[url] = brands[url];
                }
            }

            if (!Object.keys(foundBrands).length) {
                this.toggleElement(livesearch, false);
            } else {
                result = '';

                $.each(_.keys(foundBrands), function (index, url) {
                    result += this.nodes.resultItem
                        .replace('{url}', url)
                        .replace('{tabIndex}', index + 1)
                        .replace('{content}', foundBrands[url]);
                }.bind(this));

                this.toggleElement(livesearch, true);
                livesearch.html(result);
            }
        },

        /**
         * @returns {void}
         */
        clearSearch: function () {
            this.toggleElement(this.livesearch, false);
            this.toggleElement(this.clearButton, false);
            this.input.val('');
            this.livesearch.html('');
        },
    });

    return $.am.brandsSearch;
});

