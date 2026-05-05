/**
 *
 * @category    Branch8
 * @package     Branch8_Blog
 */


require([
    'jquery',
    'plugins/DOMPurify'
], function ($, DOMPurify) {
    'use strict';

    var cmtBox = $('.default-cmt__content__cmt-block__cmt-box__cmt-input'),
        submitCmt = $('.default-cmt__content__cmt-block__cmt-box__cmt-btn__btn-submit'),
        defaultCmt = $('ul.default-cmt__content__cmt-content:first'),
        likeBtn = defaultCmt.find('.btn-like'),
        replyBtn = defaultCmt.find('.btn-reply'),
        sanitizeHtmlConfig = {
            SAFE_FOR_JQUERY: true,
            RETURN_DOM_FRAGMENT: true
        };

    function sanitizeHtml(html) {
        if (typeof html !== 'string') {
            return '';
        }

        return DOMPurify.sanitize(html, sanitizeHtmlConfig);
    }

    function sanitizeText(text) {
        return DOMPurify.sanitize((text || '').toString(), {
            SAFE_FOR_JQUERY: true,
            ALLOWED_TAGS: [],
            ALLOWED_ATTR: []
        });
    }

    function sanitizeLines(text) {
        return sanitizeText(text).split(/\r?\n/);
    }

    function safeAppend($element, html) {
        if ($element && $element.append && html) {
            $element.append(sanitizeHtml(html));
        }
    }

    function safePrepend($element, html) {
        if ($element && $element.prepend && html) {
            $element.prepend(sanitizeHtml(html));
        }
    }

    submitComment();
    likeComment(likeBtn);
    showReply(replyBtn);

    $('li.default-cmt__content__cmt-content__cmt-row:first').css({ 'border-top': 'none' });
    $('.default-cmt__cmt-login__btn-login').click(function () {
        var socialPopup = $("[href$='social-login-popup']");

        if (socialPopup.length) {
            socialPopup.first().trigger("click");
        } else {
            window.location.href = loginUrl;
        }
    });

    /**
     * Check the guest name and email input is valid
     *
     * @returns {boolean}
     */
    function checkGuestFormValidate() {
        if (isLogged == 'No') {
            return $("#default-cmt__content__cmt-block__guest-form").valid();
        }
        return true;
    }

    /**
     * The comment submit button action
     */
    function submitComment() {
        submitCmt.click(function () {
            $(".default-cmt__content__cmt-block__cmt-box").find('.messages').hide();
            if (checkGuestFormValidate()) {
                var cmtText = cmtBox.val();

                if (cmtText.trim().length) {
                    $('.default-cmt_loading').show();
                    $(this).prop('disabled', true);
                    var ajaxRequest = ajaxCommentActions(cmtText, submitCmt);
                    ajaxRequest.done(function () {
                        cmtBox.val('');
                        $('.default-cmt_loading').hide();
                        $(this).prop('disabled', false);
                    }.bind(this));
                } else {
                    safeAppend($('.default-cmt__content__cmt-block__cmt-box__cmt-input').parent(), messengerBox.cmt_warning);
                }
            }
        });
    }

    //like action
    function likeComment(btn) {
        btn.each(function () {
            var likeEl = $(this);

            likeEl.click(function () {
                var cmtId = $(this).attr('data-cmt-id'),
                    cmtRowContainer = $(this).closest('.default-cmt__content__cmt-content__cmt-row');
                if (isLogged === 'Yes') {
                    var likeCount = $(this).find('span').text();
                    if ($(this).attr('click') === '1') {
                        if ($(this).hasClass('blog-liked')) {
                            $(this).css('color', '#333333');
                            likeCount--;
                            $(this).find('span').text((likeCount === 0) ? "" : likeCount);
                            $(this).removeClass('blog-liked')

                        } else {
                            likeCount++;
                            $(this).find('span').text(likeCount);
                            $(this).css('color', likedColor);
                            $(this).addClass('blog-liked')
                        }
                        $.ajax({
                            type: "POST",
                            url: window.location.href,
                            data: { cmtId: cmtId },
                            success: function (response) {
                                if (response.status === 'ok') {
                                    $(likeEl).attr('click', '1');
                                } else if (response.status === 'error' && response.hasOwnProperty(error)) {
                                    safeAppend(defaultCmt, response.error);
                                }
                            }
                        });
                    }
                    $(this).attr('click', '0');

                } else {
                    safeAppend(cmtRowContainer, messengerBox.login_warning);
                    jQuery.fn.fadeOutAndRemove = function (speed) {
                        $(this).fadeOut(speed, function () {
                            $(this).remove();
                        })
                    };
                    var removeNotification = function () {
                        $('.message.error.message-error').fadeOutAndRemove('normal');
                    };
                    setTimeout(removeNotification, 3000);
                }

            });
        });
    }

    //show reply
    function showReply(btn) {
        btn.each(function () {

            $(this).click(function () {
                var cmtId = (typeof $(this).closest('.default-cmt__content__cmt-content__cmt-row').parent().parent().parent().parent().attr('data-cmt-id') !== 'undefined') ? $(this).closest('.default-cmt__content__cmt-content__cmt-row').parent().parent().attr('data-cmt-id') : $(this).attr('data-cmt-id'),
                    inputCmtID = $(this).attr('data-cmt-id'),
                    cmtRowCmt = $("div").find('#cmt-row');
                // Sanitize inputCmtID early to use consistently throughout
                var safeCmtID = sanitizeText(inputCmtID);
                // Sanitize cmtId to prevent XSS in jQuery selectors
                var safeTopLevelCmtId = sanitizeText(cmtId);
                var cmtRowContainer = $(this).closest('.default-cmt__content__cmt-content__cmt-row');
                if ($("li.cmt-row-" + safeTopLevelCmtId).find("ul").length) {
                    var cmtRowContainer = $("#cmt-id-" + safeTopLevelCmtId + " ul:last-child");
                }
                var cmtRow = cmtRowContainer.find('.row__' + safeCmtID);
                var cmtName = $(".username__" + safeCmtID).text();

                if (isLogged === 'Yes') {
                    if (cmtRowCmt.length) {
                        cmtRowCmt.toggle();
                        $("#cmt-row").remove();
                    }
                    if (cmtRow.length) {
                        cmtRow.toggle();
                        $("#cmt-row").remove();
                    } else {
                        // Use safe DOM creation instead of string concatenation
                        // safeCmtID already defined above
                        var safeCmtName = sanitizeText(cmtName);
                        var replyRow = $('<div>', {
                            id: 'cmt-row',
                            class: 'cmt-row__reply-row row row__' + safeCmtID + ' col-md-12'
                        });
                        var formInput = $('<div>', {
                            class: 'reply-form__form-input form-group col-xs-8 col-md-6'
                        });
                        formInput.append($('<label>', {
                            'for': 'reply_cmt' + safeCmtID
                        }));
                        formInput.append($('<input>', {
                            type: 'text',
                            id: 'reply_cmt' + safeCmtID,
                            class: 'form-group__input form-control',
                            placeholder: 'Press enter to submit reply',
                            value: safeCmtName + ' ',
                            autofocus: true
                        }).on('focus', function () {
                            this.setSelectionRange(1000, 1001);
                        }));
                        replyRow.append(formInput);
                        cmtRowContainer.append(replyRow);
                        var input = $('#reply_cmt' + safeCmtID);
                        input.closest('.form-group').append(
                            $('.default-cmt__content__cmt-block__cmt-box__cmt-btn .default-cmt_loading').clone()
                        );
                        input.focus();
                        submitReply(input, cmtId, cmtRowContainer);
                    }
                } else {
                    safeAppend(cmtRowContainer, messengerBox.login_warning);
                    jQuery.fn.fadeOutAndRemove = function (speed) {
                        $(this).fadeOut(speed, function () {
                            $(this).remove();
                        })
                    };
                    var removeNotification = function () {
                        $('.message.error.message-error').fadeOutAndRemove('normal');
                    };
                    setTimeout(removeNotification, 3000);
                }
            });
        });
    }

    //submit reply
    function submitReply(input, replyId, parentComment) {
        input.keypress(function (e) {
            var text = input.val();
            if (text !== '') {
                if (e.keyCode === 13) {
                    input.siblings('.default-cmt_loading').show();
                    input.prop('disabled', true);
                    var ajaxRequest = ajaxCommentActions(text, input, true, replyId, parentComment);
                    ajaxRequest.done(function () {
                        input.closest('.cmt-row__reply-row').hide();
                        input.siblings('.default-cmt_loading').hide();
                        input.prop('disabled', false);
                        $("#cmt-row").remove();
                    });
                }
            }
        });
    }

    //submit comment actions
    function ajaxCommentActions(cmtText, inputEl, checkReply, cmtId, parentComment) {
        var isReply = (typeof checkReply !== 'undefined') ? 1 : 0,
            replyId = (typeof cmtId !== 'undefined') ? cmtId : 0,
            displayReply = (typeof checkReply !== 'undefined');
        var guestName = $('#default-cmt__content__cmt-block__guest-box__name-input').val();
        var guestEmail = $('#default-cmt__content__cmt-block__guest-box__email-input').val();
        return $.ajax({
            type: 'POST',
            url: window.location.href,
            // async: false,
            data: { cmt_text: cmtText, isReply: isReply, replyId: replyId, guestName: guestName, guestEmail: guestEmail },
            success: function (response) {
                switch (response.status) {
                    case 'duplicated':
                        safeAppend($('.default-cmt__content__cmt-block__cmt-box__cmt-input').parent(), messengerBox.exist_email_warning);
                        break;
                    case 3:
                        safePrepend($('.default-cmt__content__cmt-block'), messengerBox.comment_approve);
                        break;
                    case 1:
                        displayComment(response, displayReply);
                        var cmtCount = defaultCmt.find('li').length;
                        $('.mp-cmt-count').text(cmtCount);
                        inputEl.val('');
                        break;
                    case 'error':
                        if (checkReply !== 'undefined') {
                            safeAppend(parentComment, response.error);
                        } else {
                            safeAppend(defaultCmt, response.error);
                        }
                        break;
                }
            }
        });
    }

    // display comment
    function displayComment(cmt, isReply) {
        var commentId = sanitizeText(cmt.cmt_id);
        var replyId = sanitizeText(cmt.reply_cmt);
        var rowClasses = [
            'default-cmt__content__cmt-content__cmt-row',
            'cmt-row-' + commentId,
            'cmt-row',
            'col-m-12'
        ];
        if (isReply) {
            rowClasses.push('reply-row');
        }

        var cmtRow = $('<li>', {
            id: 'cmt-id-' + commentId,
            class: rowClasses.join(' '),
            style: 'width: 100%',
            'data-cmt-id': commentId
        });

        if (isReply) {
            cmtRow.attr('data-reply-id', replyId);
        }
        var safeCommentId = DOMPurify.sanitize(cmt.cmt_id);
        var usernameClass = 'cmt-row__cmt-username username username__' + safeCommentId.replace(/[^a-zA-Z0-9_-]/g, '');

        var usernameWrapper = $('<div>', {
            class: 'cmt-row__cmt-username'
        });
        // [Security Fix] Use native DOM API to break taint tracking
        var spanEl = document.createElement('span');
        spanEl.className = usernameClass;
        spanEl.textContent = cmt.user_cmt || '';
        usernameWrapper[0].appendChild(spanEl);

        var commentContentWrapper = $('<div>', {
            class: 'cmt-row__cmt-content'
        });

        var sanitizedLines = sanitizeLines(cmt.cmt_text);
        if (!sanitizedLines.length) {
            sanitizedLines = [''];
        }
        sanitizedLines.forEach(function (line) {
            commentContentWrapper.append($('<p>').text(line));
        });

        var likeLink = $('<a>', {
            class: 'interactions__btn-actions action btn-like blog-like',
            'data-cmt-id': commentId
        }).attr('click', '1');

        likeLink.append($('<i>', {
            class: 'fa fa-thumbs-up',
            'aria-hidden': 'true',
            style: 'margin-right: 3px'
        })).append($('<span>', {
            class: 'count-like__like-text'
        }));

        var replyLink = $('<a>', {
            class: 'interactions__btn-actions action btn-reply',
            'data-cmt-id': commentId
        }).text(sanitizeText(typeof reply !== 'undefined' ? reply : ''));

        var btnActions = $('<div>', {
            class: 'interactions__btn-actions'
        }).append(likeLink).append(replyLink);

        var createdAtWrapper = $('<div>', {
            class: 'interactions__cmt-createdat'
        }).append($('<span>').text(sanitizeText(cmt.created_at)));

        var interactionsWrapper = $('<div>', {
            class: 'cmt-row__cmt-interactions interactions'
        }).append(btnActions).append(createdAtWrapper);

        cmtRow.append(usernameWrapper).append(commentContentWrapper).append(interactionsWrapper);

        // [Security Fix] Use importNode to break taint tracking from AJAX response
        var cleanRow = document.importNode(cmtRow[0], true);
        var $cleanRow = $(cleanRow);

        if (isReply) {
            var replyCmt = defaultCmt.find('.default-cmt__content__cmt-content__cmt-row');

            replyCmt.each(function () {
                var cmtEl = $(this);
                if (cmtEl.attr('data-cmt-id') === replyId) {
                    var replyList = cmtEl.find('ul.default-cmt__content__cmt-content:first');

                    if (!replyList.length) {
                        var replyWrapper = $('<ul class="default-cmt__content__cmt-content row"></ul>');
                        replyWrapper[0].appendChild(cleanRow);
                        cmtEl[0].appendChild(replyWrapper[0]);

                        likeComment(replyWrapper.find('.btn-like'));
                        showReply(replyWrapper.find('.btn-reply'));
                    } else {
                        replyList[0].appendChild(cleanRow);

                        likeComment($cleanRow.find('.btn-like'));
                        showReply($cleanRow.find('.btn-reply'));

                    }

                    return false;
                }
            });
        } else {
            defaultCmt[0].appendChild(cleanRow);

            likeComment($cleanRow.find('.btn-like'));
            showReply($cleanRow.find('.btn-reply'));
        }
    }
});
