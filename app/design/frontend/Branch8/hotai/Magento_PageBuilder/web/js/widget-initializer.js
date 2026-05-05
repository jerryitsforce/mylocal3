/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * @api
 */
define([
    'underscore',
    'jquery',
    'mage/apply/main',
    'Magento_Ui/js/lib/view/utils/dom-observer'
], function (_, $, mage, domObserver) {
    'use strict';

    function generateUID(length) {
        let result = '';
        const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        const charactersLength = characters.length;
        let counter = 0;
        while (counter < length) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
            counter += 1;
        }
        return result;
    }

    /**
     * Initializes components assigned to HTML elements.
     *
     *
     * @param {HTMLElement} el
     * @param {Array} data
     * @param {Object} breakpoints
     * @param {Object} currentViewport
     */
    function initializeWidget(el, data, breakpoints, currentViewport) {
        _.each(data, function (config, component) {
            config = config || {};
            config.breakpoints = breakpoints;
            config.currentViewport = currentViewport;
            $(el).attr('data-ga4-event-uid', generateUID(20));
            // console.log(el[0]);
            mage.applyFor(el, config, component);
        });
    }

    return function (data, contextElement) {
        _.each(data.config, function (componentConfiguration, elementPath) {
            domObserver.get(
                elementPath,
                function (element) {
                    var $element = $(element);
                    if (contextElement) {
                        $element = $(contextElement).find(element);
                    }

                    if ($element.length) {
                        initializeWidget($element, componentConfiguration, data.breakpoints, data.currentViewport);
                    }
                }
            );
        });
    };
});
