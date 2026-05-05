require(['jquery', 'plugins/DOMPurify', 'mage/translate', 'domReady!'], function($, DOMPurify, $t) {
    $(document).ready(function(){
        $('.condition').trigger('change');
        $('#event_user_limit_type').trigger('change');
        $('#event_event_limit_type').trigger('change');

        $('#event_reward_type').on('change', function() {
            toggleRewardType($(this).val())
        }).trigger('change');

        $('#event_reward_pool_id').on('change', function() {
            loadBatchCode($(this).val())
        }).trigger('change');
        updatePreviewTitle();
        updatePreviewContent();
    });
    


    function toggleRewardType(val){
        if (val == '1') {/**COUPON */
            $('.field-reward_rule_id').show();

            $('.field-reward_pool_id').hide();
            $('#event_reward_pool_id').removeClass('required-entry _required');
            $('.field-reward_pool_batch_code').hide();
        } else if(val == '2'){
            $('.field-reward_pool_id').show();
            $('.field-reward_pool_batch_code').show();
            $('.field-reward_rule_id').hide();
        }else {
            $('.field-reward_pool_id').hide();
            $('.field-reward_pool_batch_code').hide();
            $('.field-reward_rule_id').hide();
        }
    }

    function loadBatchCode(ruleId){
        if(ruleId == ''){
            return;
        }
        $.ajax({
            url: loadBatchCodeUrl + '?id='+ruleId+'&eid='+$('#event_entity_id').val(),
            method: 'get',
            dataType: 'json',
            showLoader:true,
            beforeSend: function() {
                $('body').trigger('processStart');
            },
            success: function(data){
                var batchCodes = data.data;
                $('#event_reward_pool_batch_code').html(DOMPurify.sanitize('<option value="">'+$t('Please Select')+'</option>'));
                $.each(batchCodes, function(k, v){
                    var batchCodeSelected = '';
                    if(v.selected == 1){
                        batchCodeSelected = 'selected';
                    }
                    var batchCodeDisabled = '';
                    if(v.disabled == 1){
                        batchCodeDisabled = 'disabled';
                    }
                    var batchOption = '<option '+batchCodeSelected+' '+batchCodeDisabled+' value="'+v.code+'">'+v.label+'</option>';
                    $('#event_reward_pool_batch_code').append(DOMPurify.sanitize(batchOption));
                });
                $('body').trigger('processStop');
            }
        });
    }

    $('#event_notification_title').on('change', function(){
        updatePreviewTitle();
    });
    $('#event_notification_title').on('keyup', function(){
        updatePreviewTitle();
    });
    $('#event_title').on('change', function(){
        updatePreviewTitle();
        updatePreviewContent();
    });
    $('#event_end_date').on('change', function(){
        updatePreviewTitle();
        updatePreviewContent();
    });
    function updatePreviewTitle(){
        let rewardTitle = $('#event_title').val();
        let rewardEnddate = $('#event_end_date').val();
        let notificationTitle = $('#event_notification_title').val();
        notificationTitle = notificationTitle.replace('{reward_title}', rewardTitle);
        notificationTitle = notificationTitle.replace('{expiry_date}', rewardEnddate);
        $('#preview_notification_title').html(DOMPurify.sanitize(notificationTitle));

        /** Update count */
        var notificationTitleForCnt = notificationTitle;
        notificationTitleForCnt = notificationTitleForCnt.replace('{customer_name}', '');
        notificationTitleForCnt = notificationTitleForCnt.replace('{reward_code}', '');
        var notiTitleLength = notificationTitleForCnt.length+'';
        $('#cnt_title').html(DOMPurify.sanitize(notiTitleLength));
    }

    $('#event_notification_content').on('change', function(){
        updatePreviewContent();
    });
    $('#event_notification_content').on('keyup', function(){
        updatePreviewContent();
    });

    function updatePreviewContent(){
        let rewardTitle = $('#event_title').val();
        let rewardEnddate = $('#event_end_date').val();
        let notificationContent = $('#event_notification_content').val();
        notificationContent = notificationContent.replace('{reward_title}', rewardTitle);
        notificationContent = notificationContent.replace('{expiry_date}', rewardEnddate);
        notificationContent = notificationContent.replaceAll('\n', '<br />');
        $('#preview_notification_content').html(DOMPurify.sanitize(notificationContent));

        /** Update count */
        var notificationContentForCnt = notificationContent;
        notificationContentForCnt = notificationContentForCnt.replace('{customer_name}', '');
        notificationContentForCnt = notificationContentForCnt.replace('{reward_code}', '');
        var notiContentLength = notificationContentForCnt.length+'';
        $('#cnt_content').html(DOMPurify.sanitize(notiContentLength));
    }
    /** Trigger tab */
    $(document).on('change', '.condition', function(){
        toggleCondition(this);
    });
    function toggleCondition(elm){
        var selectedConditionVal = parseInt($(elm).val());
        $('#event_user_limit_daily').removeClass('ars-readonly');
        $('#event_user_limit_total').removeClass('ars-readonly');
        if(selectedConditionVal == 1){/** API */
            showHideConditionElm(elm, ['field-api_url', 'field-api_key']);
        }else if(selectedConditionVal == 2){/** Visit URL */
            showHideConditionElm(elm, ['field-url']);
        }else if(selectedConditionVal == 3){/** First purchase */
            showHideConditionElm(elm, ['field-first_order_status']);
        }else if(selectedConditionVal == 4){/** Level change */
            showHideConditionElm(elm, ['field-level']);
        }else{
            showHideConditionElm(elm, []);
        }
        /**
         * Set User limit = Event, qty = 1 for event if only one First Purchase trigger found
         */
        $('#event_user_limit_daily').removeClass('ars-readonly');
        $('#event_user_limit_total').removeClass('ars-readonly');
        $.each($('#reward_event_tabs_triggers_content .condition'), function(k, _condition){
            if(parseInt($(_condition).val()) == 3){
                $('#event_user_limit_daily').val(1);
                $('#event_user_limit_daily').addClass('ars-readonly');
                $('#event_user_limit_total').val(1);
                $('#event_user_limit_total').addClass('ars-readonly');
            }
        });
    }

    function showHideConditionElm(elm, elmToShows){
        var elems = ['field-api_url', 'field-api_key', 'field-url', 'field-first_order_status', 'field-level'];
        var parentConditionItems = $(elm).parents('.condition_item');
        
        if(parentConditionItems.length){
            var parentConditionItem = parentConditionItems[0];
        }else{
            var parentConditionItem = null;
        }
        if(!parentConditionItem){
            return;
        }

        if(elmToShows.length == 0){
            $.each(elems, function(k2, elm2){
                var wrapTag = $(parentConditionItem).find('.'+elm2);
                wrapTag.hide();
                wrapTag.find('input').removeClass('required-entry');
                wrapTag.find('select').removeClass('required-entry');
                
            });
        }
        $.each(elmToShows, function(k, elm){
            var wrapTag = $(parentConditionItem).find('.'+elm);
            wrapTag.show();
            wrapTag.find('input').addClass('required-entry');
            wrapTag.find('select').addClass('required-entry');
            
            $.each(elems, function(k2, elm2){
                if(elmToShows.indexOf(elm2) == -1){
                    var wrapTag = $(parentConditionItem).find('.'+elm2);
                    wrapTag.hide();
                    wrapTag.find('input').removeClass('required-entry');
                    wrapTag.find('select').removeClass('required-entry');
                }
            });
        });
    }
    $(document).on('click', '.close_condition', function(){
        $(this).parents('.condition_item').remove();
    });
    $('#add_condition').click(function(){
        $('#condition_details').append(DOMPurify.sanitize(newConditionBlock));
        $('.condition').trigger('change');
    });


    /** End Trigger Tab */
    $('#event_user_limit_type').change(function(){
        toggleUserLimit($(this).val());
    });
    $('#event_event_limit_type').change(function(){
        toggleEventLimit($(this).val());
    });
    function toggleUserLimit(val){
        if(val == '0'){
            $('.field-user_limit').hide();
            $('#event_user_limit').attr('disabled', 'disabled');
        }else{
            $('.field-user_limit').show();
            $('#event_user_limit').removeAttr('disabled');
            $('#event_user_limit').removeAttr('readonly');
        }
    }

    function toggleEventLimit(val){
        if(val == '0'){
            $('.field-event_limit').hide();
            $('#event_event_limit').attr('disabled', 'disabled');
        }else{
            $('.field-event_limit').show();
            $('#event_event_limit').removeAttr('disabled');
        }
    }
});