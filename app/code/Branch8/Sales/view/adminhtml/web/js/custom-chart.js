/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_CustomChart
 * @author    Webkul
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
define([
    'jquery',
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function ($, alert, $t) {
    'use strict';

    $.widget('mage.customchart', {
        /**
         * Options common to all instances of this widget.
         * @type {Object}
         */
        options: {
            chartDivId: "chart_div"
        },

        _create: function () {
            this._super();
            var self = this;

            /**
             * js code to draw chart
             * Reference: https://developers.google.com/chart/interactive/docs/gallery/columnchart
             */
            google.charts.load('current', {packages: ['corechart', 'bar']});
            google.charts.setOnLoadCallback(drawColColors);

            function drawColColors() {
                var chartDiv = document.getElementById(self.options.chartDivId);

                var options = {
                    title: '',
                    legend: {position: 'top'},
                    colors: ['#ef0a2c', '#32A8B4', '#FF9900', '#14d01d'] // Add a new color
                };

                function drawDefaultChart() {
                    var defaultData = new google.visualization.arrayToDataTable(
                        self.options.chartInfo.chart
                    );

                    var defaultChart = new google.visualization.ColumnChart(chartDiv);
                    defaultChart.draw(defaultData, options);
                }

                drawDefaultChart();
            }
        }
    });

    return $.mage.customchart;
});
