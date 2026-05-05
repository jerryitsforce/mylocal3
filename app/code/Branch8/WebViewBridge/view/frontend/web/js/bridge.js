require([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mage/cookies',
    'domReady!'
], function ($, customerData) {
    'use strict';

    const headerMenu = $('header.page-header .nav-toggle')
    const navigation = $(".page-wrapper > .navigation");
    const isDebuggerEnabled = $('body').hasClass('hotai-app-debugger');
    const headerSearch = $('header.page-header .block-search .block-title')

    const setSearchRecent = (keyword) => {
        if (!keyword) return;
        var storageKey = 'ds-view-recent-live-search';
        var recents = [];
        try {
            recents = JSON.parse(localStorage.getItem(storageKey)) || [];
        } catch (e) {
            recents = [];
        }
        recents.push(keyword);
        var uniqueRecents = recents.filter(function (val, idx, arr) {
            return arr.indexOf(val) === idx;
        });
        localStorage.setItem(storageKey, JSON.stringify(uniqueRecents.slice(-5)));
    };
    window.AppBridge = {
        handleMessage(data) {
            let validType = false;
            const $searchInput = $('#search');
            const $headerBackButton = $('#hide-nav-button');
            const $headerSearchCloseButton = $('.block-search .close-btn');
            const $searchAutocomplete = $('#search_autocomplete')
            const searchForm = $('#search_mini_form');
            const $modal = $('.delete-all-confirm-popup');

            const $html = $('html');

            switch (data.type) {
                case 'HEADER_MENU_PRESS':
                    validType = true;
                    if (headerMenu.length > 0) {
                        headerMenu.trigger('click');
                    }
                    break;
                case 'HEADER_BACK_PRESS':
                    validType = true;
                    if ($headerBackButton.length > 0) {
                        $headerBackButton.trigger('click');
                    }

                    if ($headerSearchCloseButton.length > 0) {
                        $headerSearchCloseButton.trigger('click');
                    }

                    break;
                case 'HEADER_SEARCH_PRESS':
                    validType = true;
                    if (headerSearch.length > 0) {
                        headerSearch.trigger('click');
                    }
                    break;
                case 'HEADER_SEARCH_INPUT_FOCUS':
                    $searchInput.trigger('click');
                    $searchInput.triggerHandler('focus');
                    // $html.addClass('nav-search-typing');
                    $html.addClass('--with-search-suggestion');

                    $searchAutocomplete.css('display', 'flex');
                    $searchAutocomplete.css('right', '0px');

                    validType = true;
                    break;

                case 'HEADER_SEARCH_INPUT_FOCUS_OUT':
                    $html.removeClass('nav-search-typing');
                    $html.removeClass('--with-search-suggestion');
                    validType = true;
                    break;
                case 'HEADER_SEARCH_INPUT_CHANGE':
                    const input = document.getElementById('search');
                    if (!input) break;

                    const value = data.data;
                    //ReactJS input value setter
                    const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
                        window.HTMLInputElement.prototype,
                        'value'
                    ).set;
                    nativeInputValueSetter.call(input, value);

                    input.dispatchEvent(new Event('input', { bubbles: true }));

                    validType = true;
                    break;

                case 'GO_HOME':
                    validType = true;
                    window.location.href = encodeURI(window.location.origin + '/');
                    break;
                case 'WISH_LIST_REMOVE_ALL':
                    validType = true;
                    if (!$modal.length || !$modal.hasClass('_show')) {
                        $('.wishlist-remove-all').trigger('click');
                    }
                    break;
                case 'BROWSING_HISTORY_REMOVE_ALL':
                    validType = true;
                    if (!$modal.length || !$modal.hasClass('_show')) {
                        $('.browsing-history-remove-all').trigger('click');
                    }
                    break;

                case 'SEARCH_SUBMIT':
                    validType = true;
                    $searchAutocomplete.hide()
                    if (searchForm.length > 0) {
                        searchForm.submit();
                    }
                    break;

                case 'SET_SEARCH_RECENT':
                    validType = true;
                    setSearchRecent(data.data);
                    break;

                default:
                    break;
            }

            if (validType && isDebuggerEnabled) {
                customerData.set('messages', {
                    messages: [{
                        type: 'success',
                        text: '[AppBridge] ' + data.type + ': ' + data.data
                    }]
                });
            }

        }
    };
    if (!window._appBridgeDocumentListenerRegistered) {
        window._appBridgeDocumentListenerRegistered = true;
        document.addEventListener('message',function(event){
            console.log('Handle Message By Document Listener ', JSON.parse(event.data).type)
            try {
                const data = JSON.parse(event.data);
                window.AppBridge.handleMessage(data);
            } catch (e) {
                console.warn('[AppBridge] Error when parse message:', event.data);
            }
        }, false)
    } else {
        console.log('window._appBridgeDocumentListenerRegistered true')
    }

    if (!window._appBridgeWindowListenerRegistered) {
        window._appBridgeWindowListenerRegistered = true;
        window.addEventListener('message', function(event) {
            console.log('Handle Message By Window Listener ', JSON.parse(event.data).type)

            try {
                const data = JSON.parse(event.data);
                window.AppBridge.handleMessage(data);
            } catch (e) {
                console.warn('[AppBridge] Error when parse message:', event.data);
            }
        }, false);
    } else {
        console.log('window._appBridgeWindowListenerRegistered true')
    }

    const notifyAppReady = () => {
        try {
            reactNativeWebViewPostMessage('WEB_HEADER_READY', 'Header Ready');
            return true;
        } catch (e) {
            return false;
        }
    };

    const sendLoginSuccessEvent = (accessToken) => {
        console.log('[AppBridge] Login Success Event:', accessToken);
        const loginReferrer = $.mage.cookies.get('hotai_redirect_url') || '';
        if (accessToken) {
            reactNativeWebViewPostMessage('LOGIN_SUCCESS', JSON.stringify({
                magentoToken: accessToken,
                redirect_url: loginReferrer,
            }));
            removeCookie('hotai_app_tk');
            removeCookie('hotai_app_access_token');
        }
    };

    const notifyLoginFailed = (isFailed) => {
        if (parseInt(isFailed) === 1) {
            reactNativeWebViewPostMessage('LOGIN_FAILED', '1');
            removeCookie('hotai_app_login_failed');
        }
    };

    const reactNativeWebViewPostMessage = (type, data) => {
        if (window.ReactNativeWebView) {
            window.ReactNativeWebView.postMessage(JSON.stringify({ type, data }));
        } else {
            const intervalId = setInterval(() => {
                if (window.ReactNativeWebView) {
                    window.ReactNativeWebView.postMessage(JSON.stringify({ type, data }));
                    clearInterval(intervalId);
                }
            }, 500);
        }
    }

    const removeCookie = (key) => {
        $.mage.cookies.set(key, '', { domain: '', path: '/', expires: new Date('Jan 01 1970 00:00:01 GMT') });
    }

    if (window.ReactNativeWebView) {
        notifyAppReady();
    } else {
        const intervalId = setInterval(() => {
            if (window.ReactNativeWebView && headerMenu.length > 0 && headerSearch.length) {
                const success = notifyAppReady();
                if (success) {
                    clearInterval(intervalId);
                }
            }
        }, 500);
    }

    var branch8Data = customerData.get('branch8_customer_data');
    branch8Data.subscribe(function (data) {
        if (data && data.webview_bridge) {
            if (data.webview_bridge.access_token) {
                sendLoginSuccessEvent(data.webview_bridge.access_token);
            }
            if (data.webview_bridge.login_failed) {
                notifyLoginFailed(data.webview_bridge.login_failed);
            }
        }
    });

    // Initial check
    var currentData = branch8Data();
    if (currentData && currentData.webview_bridge) {
        if (currentData.webview_bridge.access_token) {
            sendLoginSuccessEvent(currentData.webview_bridge.access_token);
        }
        if (currentData.webview_bridge.login_failed) {
            notifyLoginFailed(currentData.webview_bridge.login_failed);
        }
    }

    let lastScrollTop = 0;
    let lastSent = null;
    window.addEventListener('scroll', function () {
        let st = window.pageYOffset || document.documentElement.scrollTop;
        if (Math.abs(st - lastScrollTop) < 5) return;

        if (st > 50 && st > lastScrollTop && lastSent !== 'down') {

            reactNativeWebViewPostMessage('SCROLL_DOWN', st);
            lastSent = 'down';
        } else if (st < lastScrollTop && lastSent !== 'up') {

            reactNativeWebViewPostMessage('SCROLL_UP', st);
            lastSent = 'up';
        }
        lastScrollTop = st <= 0 ? 0 : st;
    }, false);

    function postCartUpdatedMessage() {
        console.log('postUpdateCartMessage');
        reactNativeWebViewPostMessage('CART_UPDATED', '');
    }

    $(document).on('ajax:addToCart', function (event, data) {
        postCartUpdatedMessage()
    });

    $(document).ajaxComplete(function (event, xhr, settings) {
        const url = settings.url;

        if (url.indexOf('/checkout/cart/updatePost') !== -1) {
            postCartUpdatedMessage()
        }

        if (url.indexOf('checkout/cart/delete') !== -1) {
            postCartUpdatedMessage()
        }
        if (url.indexOf('customer/section/load') > 0 && settings.url.indexOf('cart') > 0) {
            var customerSection = xhr.responseJSON;

            if (customerSection.cart?.summary_count !== undefined) {
                reactNativeWebViewPostMessage('CART_UPDATED_QTY', customerSection.cart?.summary_count);

            }
            if (customerSection.customer) {
                const isGuest = !customerSection.customer.email;
                reactNativeWebViewPostMessage('CART_UPDATED_TYPE', isGuest ? 'guest' : 'customer');
            }
        }
    });

    //document ready move div.point-floating-button and div#back-to-top outside page-footer and still in page-wrapper
    $(document).ready(function () {
        const floatingButton = $('.point-floating-button');
        const backToTop = $('#back-to-top');
        const pageWrapper = $('.page-wrapper');

        if (floatingButton.length && pageWrapper.length) {
            floatingButton.appendTo(pageWrapper);
        }
        if (backToTop.length && pageWrapper.length) {
            backToTop.appendTo(pageWrapper);
        }
    });


    function logElementInfo(el) {
        if (!el) return;

        console.group('📱 TOUCH EVENT');

        console.log('Tag:', el.tagName);
        console.log('ID:', el.id);
        console.log('Class:', el.className);
        console.log('Name:', el.getAttribute('name'));
        console.log('Href:', el.getAttribute('href'));
        console.log('Src:', el.getAttribute('src'));
        console.log('Text:', el.innerText?.trim());

        console.groupCollapsed('OuterHTML');
        console.log(el);
        console.groupEnd();

        console.groupEnd();
    }

    document.addEventListener(
        'touchstart',
        function (e) {
            let target = e.target;

            while (target && !target.tagName) {
                target = target.parentNode;
            }

            logElementInfo(target);
        },
        true
    );

});
