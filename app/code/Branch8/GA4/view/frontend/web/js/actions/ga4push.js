define([
    'jquery',
    'Magento_GoogleTagManager/js/google-tag-manager',
    'mage/cookies'
], function ($) {
    'use strict';

    let lastShippingData = null;
    let lastRemoveCartData = null;

    function clearEventSession(eventType) {
        $.ajax({
            url: '/branch8_ga4/index/clearEvent',
            type: 'POST',
            data: {
                event_type: eventType
            },
            dataType: 'json'
        }).fail(function(xhr, status, error) {
            console.error('Failed to clear event session:', error);
        });
    }

    function getEventType(eventName) {
        const eventMap = {
            'add_to_cart': 'add_to_cart',
            'remove_from_cart': 'remove_from_cart',
            'add_shipping_info': 'checkout_options',
            'add_to_wishlist': 'add_to_wishlist',
            'sign_up': 'register',
            'login': 'login',
            'add_to_compare': 'add_to_compare',
            'refund': 'refund'
        };
        return eventMap[eventName] || eventName;
    }

    function notify(dl4Objects) {
        // console.log('debug1: ', dl4Objects);
        for (var i in dl4Objects) {
            // console.log('debug2: ', dl4Objects[i]);
            const uid = dl4Objects[i]?.uid ?? '';
            if (dl4Objects[i]?.uid) {
                const eventUID = dl4Objects[i].uid;
                const cookieName = 'gtm_' + dl4Objects[i]?.event + '_uids';

                let uids = $.mage.cookies.get(cookieName);

                uids = uids ? uids.split(',') : [];

                if (uids.indexOf(eventUID) === -1) {
                    uids.push(eventUID);
                } else {
                    console.log('IGNORE!', eventUID, dl4Objects[i]);
                    return;
                }

                $.mage.cookies.set(cookieName, uids.join(','), { expires:  1/24, path: '/' });
                //unset dl4Objects[i].uid;
                delete dl4Objects[i].uid;
            }


            if (dl4Objects[i]?.event === 'add_shipping_info') {
                const currentData = JSON.stringify(dl4Objects[i]);
                if (lastShippingData === currentData) {
                    return;
                }
                lastShippingData = currentData;
            }

            if (dl4Objects[i]?.event === 'remove_from_cart') {
                const currentData = JSON.stringify(dl4Objects[i]);
                if (lastRemoveCartData === currentData) {
                    return;
                }
                lastRemoveCartData = currentData;
            }


            if (dl4Objects[i]?.event === 'add_to_cart' && dl4Objects[i]?.ecommerce && dl4Objects[i].ecommerce?.items) {
                dl4Objects[i].ecommerce.items = dl4Objects[i]?.ecommerce?.items?.filter((item, index, self) =>
                        index === self.findIndex((t) => (
                            t.item_id === item.item_id || t.item_name === item.item_name
                        ))
                );
            }


            console.log(uid);
            if (dl4Objects[i]?.event == 'select_item') {
                console.log("ga4push::notify", JSON.stringify(dl4Objects[i]));
            }
            console.log("ga4push::notify", dl4Objects[i]);

            //loop every ecommerce.items and convert item_id to int
            if (dl4Objects[i]?.ecommerce?.items) {
                dl4Objects[i].ecommerce.items = dl4Objects[i].ecommerce.items.map(item => {
                    if (item.item_id) {
                        item.item_id = parseInt(item.item_id);
                    }
                    return item;
                })
            }

            window.dataLayer.push({ecommerce: null});
            window.dataLayer.push(dl4Objects[i]);

            const ignoreEvents = ['view_item_list', 'select_item', 'select_item_cart', 'view_promotion', 'select_promotion'];

            if (dl4Objects[i]?.event && ignoreEvents.indexOf(dl4Objects[i].event) === -1 ) {
                clearEventSession(getEventType(dl4Objects[i].event));
            }
        }
    }

    $(document).ajaxComplete(function (event, xhr, settings) {
        if (settings.url.search('/customer\/section\/load') > 0) {
            var response = xhr.responseJSON;
            if (response && response.branch8_customer_data && response.branch8_customer_data.branch8_ga4) {
                var dataLayerData = $.parseJSON(response.branch8_customer_data.branch8_ga4.datalayer);
                if (dataLayerData && dataLayerData[0] && dataLayerData[0].event === "add_to_cart" && window.isBuyNow) {
                    const fullPointError = window.fullPointError;
                    window.fullPointError = false;
                    window.isBuyNow = false;
                    if (window.cartRedirectUrl && !fullPointError) {
                        window.location.href = window.cartRedirectUrl;
                        window.cartRedirectUrl = null;
                    }
                }
                pushGa4(dataLayerData);
            }
        }
    });

    window.ga4CustomPush = function (eventName, data) {
        let selectItem = [{
            'event': eventName,
            'ecommerce': data
        }];
        notify(selectItem);
    }

    window.ga4Push = pushGa4;

    $(document).on('ga4.select_item', function (event, data) {
        if (data.ecommerce) {
            let selectItem = [{
                'event': 'select_item',
                'ecommerce': data.ecommerce
            }];
            notify(selectItem);
        }
    });

    $(document).on('ga4.view_promotion', function (event, ga4ViewPromos) {
        $.each(ga4ViewPromos, function (i, e) {
            var selectItem = [{
                'event': 'view_promotion',
                'ecommerce': e.ecommerce
            }];
            notify(selectItem);
        });
    });

    function pushGa4(dl4objects) {
        if (!window.dataLayer) {
            console.log('Google Tag Manager not loaded');
            console.log(dl4objects);
        }
        window.dataLayer ?
            notify(dl4objects) :
            $(document).on('ga:inited', notify.bind(this, dl4objects));
    }

    return function (dl4objects) {
        pushGa4(dl4objects);
    };
});
