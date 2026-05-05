/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_OptionsWithStockAndImages
 * @author    Webkul Software Private Limited
 * @copyright Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */
define([
    "jquery",
    "mage/translate",
    "Magento_Ui/js/modal/modal",
    'mage/url',
    'ko',
    'Magento_Ui/js/modal/alert',
    'mage/template',
    'underscore',
    'plugins/DOMPurify',
    './Variant/VariantProcessHandler'
], function (
    $,
    $t,
    modal,
    url,
    ko,
    alert,
    mageTemplate,
    _,
    DOMPurify,
    VariantProcessHandler
) {
    'use strict';
    /**
     *
     */
    return function (config) {
        const option = $.isEmptyObject(config) === false ? config : window.variantionConfig;
        var jQ = $.noConflict(),
            wkVariantionTrigger = option.wkVariantionTrigger, body = jQ('body');
        VariantProcessHandler.init(option);
        body.on('click', wkVariantionTrigger, async function () {
            await VariantProcessHandler.click();
            getPagination('#variation-tbl', 10);
        })
        body.on('change', '.maxRows', function () {
            var tblId = DOMPurify.sanitize('#' + jQ(this).closest('form').find('table').attr('id') + '');
            var maxRows = parseInt(jQ(this).val());
            getPagination(tblId, maxRows);
        });
        jQ('.maxRows').trigger('change');
        function getPagination(table, maxRows) {
            var currenpageAttr = jQ(table).closest('form').find('.currenpage');
            jQ('.pagination').html('');
            var trnum = 0;
            var totalRows = jQ(table + ' tbody tr').length;
            jQ(table + ' tr:gt(0)').each(function () {
                trnum++;
                if (trnum > maxRows) {
                    jQ(this).hide();
                }
                if (trnum <= maxRows) {
                    jQ(this).show();
                }
            });
            jQ(currenpageAttr).val(1);
            jQ('.nxt-pag-btn').prop("disabled", true);
            if (totalRows > maxRows) {
                var pagenum = Math.ceil(totalRows / maxRows);
                //	numbers of pages
                if (pagenum > 1) {
                    jQ('.nxt-pag-btn').prop("disabled", false);
                }
            } else {
                pagenum = 1;
            }
            jQ('.pagenumlabel').html('of ' + pagenum);
            showig_rows_count(maxRows, 1, totalRows);
            $('.pre-pag-btn, .nxt-pag-btn').unbind('click').click(function () {
                var currenpage = $(currenpageAttr).val();
                if ($(this).attr("class") == 'action-next nxt-pag-btn') {
                    $('.pre-pag-btn').prop("disabled", false);
                    currenpage++;
                }
                if ($(this).attr("class") == 'action-previous pre-pag-btn') {
                    $('.nxt-pag-btn').prop("disabled", false);
                    currenpage--;
                }
                if (pagenum == currenpage) {
                    $('.nxt-pag-btn').prop("disabled", true);
                }
                if (currenpage == 1) {
                    $('.pre-pag-btn').prop("disabled", true);
                }
                nextandpreviousPage(currenpage);
                $('.currenpage').val(currenpage);
            })

            function nextandpreviousPage(pageNum) {
                var trIndex = 0;

                //SHOWING ROWS NUMBER OUT OF TOTAL
                showig_rows_count(maxRows, pageNum, totalRows);
                //SHOWING ROWS NUMBER OUT OF TOTAL

                jQ(table + ' tr:gt(0)').each(function () {
                    trIndex++;
                    // if tr index gt maxRows*pageNum or lt maxRows*pageNum-maxRows fade if out
                    if (trIndex > (maxRows * pageNum) || trIndex <= ((maxRows * pageNum) - maxRows)) {
                        $(this).hide();
                    } else {
                        $(this).show();
                    }
                });
            }

            jQ('.wkv-cost_setting').trigger('change');
        }
        //ROWS SHOWING FUNCTION
        function showig_rows_count(maxRows, pageNum, totalRows) {
            //Default rows showing
            var end_index = maxRows * pageNum;
            var start_index = ((maxRows * pageNum) - maxRows) + parseFloat(1);
            var string = 'Showing ' + start_index + ' to ' + end_index + ' of ' + totalRows + ' entries';
            $('.rows_count').html(string);
        }
        jQ(document).ready(function () {
            jQ("body").on("keyup", "#variation-search, #swatch-search", function () {
                var tblId = DOMPurify.sanitize('#' + jQ(this).closest('form').find('table').attr('id') + '');
                var value = DOMPurify.sanitize(jQ(this).val().toLowerCase());
                jQ(tblId + " tbody tr").filter(function () {
                    jQ(this).toggle(jQ(this).text().toLowerCase().indexOf(value) > -1)
                });
            });
        });
        //delete image
        $("body").on("click", ".data-grid-file-uploader .data-grid-file-uploader-inner .wkosi-image-delete", function () {
            $(this).parent('.wk-img-box').remove();
        });
        $("body").on('change', '#check', function () {
            $("input:checkbox").prop('checked', $(this).prop("checked"));
        });
    }
});
