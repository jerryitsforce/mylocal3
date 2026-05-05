define(['jquery'], function (jQ) {

    function showig_rows_count(maxRows, pageNum, totalRows) {
        //Default rows showing
        var end_index = maxRows * pageNum;
        var start_index = ((maxRows * pageNum) - maxRows) + parseFloat(1);
        var string = 'Showing ' + start_index + ' to ' + end_index + ' of ' + totalRows + ' entries';
        $('.rows_count').html(string);
    }

    /**
     *
     */
    return function getPagination(
        table, maxRows,
        check_follow_simple_sku_price_setting,
        check_follow_simple_sku_cost_setting
    ) {

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

        if (check_follow_simple_sku_price_setting) {
            $('#follow_simple_sku_price_setting').prop('checked', true).trigger('change');
        }

        if (check_follow_simple_sku_cost_setting) {
            $('#follow_simple_sku_cost_setting').prop('checked', true).trigger('change');
        }

        //SHOWING ROWS NUMBER OUT OF TOTAL DEFAULT
        jQ('.wkv-cost_setting').on('change', function (e) {
            if (!isFollowSimpleSkuCostSettingChecked()) {
                var id = getId(this);
                setCostAndCommission(id);
            }
        });
        jQ('.wkv-commission_percent').on('change', function (e) {
            if (!isFollowSimpleSkuCostSettingChecked()) {
                var id = getId(this);
                setCostAndCommission(id);
            }
        });
        jQ('.wkv-cost').on('change', function (e) {
            if (!isFollowSimpleSkuCostSettingChecked()) {
                var id = getId(this);
                setCostAndCommission(id);
            }
        });
        jQ('.wkv-price').on('change', function (e) {
            var id = getId(this);
            setCostAndCommission(id);
        });

        jQ('.switch-is-sync').on('change', function (e) {
            var id = getId(this);
            if ($(this).is(":checked")) {
                if ($('#wkv-sku-' + id).length) {
                    if ($('#wkv-sku-' + id).val() === '') {
                        alert({'content': $t("Please enter SKU.")});
                        $(this).prop('checked', !$(this).prop('checked'));
                        e.preventDefault();
                        return false;
                    }
                    var data = {
                        sku: $('#wkv-sku-' + id).val()
                    };
                    jQ.ajax({
                        type: "GET",
                        url: syncUrl,
                        data: data,
                        cache: false,
                        beforeSend: function () {
                        },
                        success: function (response) {
                            if (response && response.hasOwnProperty('sku')) {
                                if ($('#wkv-stock-' + id).length) {
                                    $('#wkv-stock-' + id).val(response.stock);
                                }
                                if ($('#wkv-weight-' + id).length) {
                                    $('#wkv-weight-' + id).val(response.weight);
                                }
                                if ($('#wkv-sku-' + id).length) {
                                    $('#wkv-sku-' + id).val(response.sku);
                                    $('#wkv-sku-' + id).prop('disabled', true);
                                }
                                if ($('#wkv-stock-' + id).length) {
                                    $('#wkv-stock-' + id).prop('disabled', true);
                                }
                                if (response.hasOwnProperty('images') && response.images.length) {
                                    var element = '#wk-variation-row-' + id + 'image';
                                    if ($(element).length) {
                                        $(element).parent().closest('.data-grid-file-uploader').children('.wk-img-box').remove();

                                        var imageIndex = response.images.length;
                                        $.each(response.images, function (key, file) {
                                            var progressTmpl = mageTemplate('#new-uploaded-image-template'),
                                                uploadedImage;
                                            uploadedImage = progressTmpl({
                                                data: {
                                                    id: id,
                                                    response: file,
                                                    imageIndex: key
                                                }
                                            })

                                            jQ(element).parent().before(DOMPurify.sanitize(uploadedImage));
                                        });

                                        jQ(element).attr('data-image-count', imageIndex);
                                    }
                                }
                            } else {
                                alert({'content': response});
                                $(this).prop('checked', !$(this).prop('checked'));
                                return false;
                            }
                        }.bind(this),
                        error: function (response) {
                            alert({'content': response});
                            $(this).prop('checked', !$(this).prop('checked'));
                            return false;
                        }.bind(this)
                    });
                }
            } else {
                if ($('#wkv-sku-' + id).length) {
                    if ($('#wkv-is_lock_sku-' + id).length && $('#wkv-is_lock_sku-' + id).val() != 1) {
                        $('#wkv-sku-' + id).prop('disabled', false);
                    }
                }
                if ($('#wkv-stock-' + id).length) {
                    $('#wkv-stock-' + id).prop('disabled', false);
                }
            }
        })

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

})
