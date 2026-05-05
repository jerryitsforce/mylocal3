define([
    'mageUtils',
    'underscore',
    './MessageType',
    './direction'
], function (mageUtils, _, MessageType, DIRECTION) {
    'use strict';

    return {
        /**
         *
         * @param utcDateTimeString
         * @returns {string}
         */
        toLocaleDateTime: function (utcDateTimeString,) {
            const utcDate = new Date(utcDateTimeString + ' UTC'),
                localOffset = utcDate.getTimezoneOffset(),
                localDate = new Date(utcDate.getTime() - (localOffset * 60 * 1000));
            return localDate.toISOString().slice(0, 19).replace('T', ' ');
        },
        /**
         *
         */
        toServerDate: function (date) {
            const currentDatetime = date ? new Date(date) : new Date(),
                year = currentDatetime.getUTCFullYear(),
                month = String(currentDatetime.getUTCMonth() + 1).padStart(2, '0'),
                day = String(currentDatetime.getUTCDate()).padStart(2, '0'),
                hours = String(currentDatetime.getUTCHours()).padStart(2, '0'),
                minutes = String(currentDatetime.getUTCMinutes()).padStart(2, '0'),
                seconds = String(currentDatetime.getUTCSeconds()).padStart(2, '0');
            return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
        },
        /**
         *
         * @param date
         * @returns {string}
         */
        formatLocalDate: function (date) {
            let dateObject = new Date(
                date.replace(' ', 'T') + 'Z');
            return dateObject.toLocaleDateString(undefined, {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
        },
        /**
         *
         * @param content
         */
        containHtmlTag: function (content) {
            const htmlTagPattern = /<[^>]+>/;
            /* content = content.replace(/(\r\n|\n|\r)/g, '<br>')*/
            //const regex = /<(?!\/?(a|br)\b)[^>]+>/i;
            return htmlTagPattern.test(content);
        },
        /**
         *
         * @returns {boolean}
         */
        checkTagInclude: function (content) {
            if (/<(\/)?[a-zA-z]\s*([a-zA-Z]*\s*)*(src=|href=)?/g.test(content)) {
                return true;
            }
            return false;
        },
        /**
         *
         * @param message
         * @returns {*}
         */
        filter: function (message) {
            if (!message) {
                message = '';
            }
            return message.replace(/<script[^>]*>(?:(?!<\/script>)[^])*<\/script>/g, "");
        },
        /**
         *
         * @param message
         * @returns {*}
         */
        messageTextToHtml: function (message) {
            if (!message) {
                message = '';
            }
            let html = this.filter((message));
            // auto convert single url to <a>tag
            html = this.convertUrlsToLinks(html);

            return this.replaceByEmoji(html);
        },
        /**
         *
         * @param htmlString
         * @returns {string}
         */
        convertUrlsToLinks: function (htmlString) {
            let tempDiv = document.createElement('div');
            tempDiv.innerHTML = htmlString;

            // Function to replace URLs with <a> tags
            function processNode(node) {

                if (node.nodeType === Node.TEXT_NODE) {
                    const urlPattern = /(\b(https?|ftp|file):\/\/[-A-Z0-9+&@#\/%?=~_|$!:,.;]*[-A-Z0-9+&@#\/%=~_|$])/gi;
                    let match;
                    let lastIndex = 0;
                    const fragment = document.createDocumentFragment();

                    while ((match = urlPattern.exec(node.textContent)) !== null) {
                        const url = match[0];
                        const before = node.textContent.substring(lastIndex, match.index);

                        // Append text before the URL
                        if (before) {
                            fragment.appendChild(document.createTextNode(before));
                        }

                        // Create a link element for the URL
                        const link = document.createElement('a');
                        link.href = url;
                        link.textContent = url;
                        fragment.appendChild(link);

                        lastIndex = urlPattern.lastIndex;
                    }

                    // Append any remaining text after the last URL
                    if (lastIndex < node.textContent.length) {
                        fragment.appendChild(document.createTextNode(node.textContent.substring(lastIndex)));
                    }

                    // Replace the original text node with the fragment
                    node.parentNode.replaceChild(fragment, node);
                } else if (node.nodeType === Node.ELEMENT_NODE) {
                    // Skip specific tags
                    const tagName = node.tagName.toUpperCase();
                    const isExcludedTag = ['IMG', 'IFRAME', 'SOURCE', 'LINK'].includes(tagName) ||
                        (tagName === 'LINK' && node.getAttribute('rel') === 'stylesheet');
                    if (isExcludedTag) {
                        return; // Skip processing this node
                    }
                    // Recursively process child nodes
                    node.childNodes.forEach(child => processNode(child));
                }
            }
            // Process the root node
            tempDiv.childNodes.forEach(child => processNode(child));
            const processedHtml = tempDiv.innerHTML;
            if (tempDiv.parentNode) {
                tempDiv.parentNode.removeChild(tempDiv);
            } else {
                // If tempDiv is not in the DOM, simply nullify it
                tempDiv = null;
            }
            return processedHtml;
        },
        /**
         *
         * @param message
         */
        replaceByEmoji: function (message) {
            if (!message) {
                message = '';
            }
            // const config = {
            //     tag_type: 'img',
            //     img_dir: window.chatboxCoreConfig.emojiImagePath,
            //     ignored_tags: {
            //         'SCRIPT': 1,
            //         'TEXTAREA': 1,
            //         'A': 1,
            //         'PRE': 1,
            //         'CODE': 1
            //     }
            // };
            // emoji.setConfig(config);
            // return emoji.replace(message);
            return message;
        },
        /**
         *
         */
        convertMessageFromSocket: function (rawResponse) {
            return {
                date: rawResponse.dateTime,
                id: rawResponse.id || _.uniqueId('mgid_'),
                message: rawResponse.message,
                messageType: rawResponse.messageType || MessageType.text,
                receiverName: rawResponse.receiverName || '',
                receiverUniqueId: rawResponse.receiverUniqueId,
                senderUniqueId: rawResponse.senderUniqueId,
                senderName: rawResponse.customerName,
            }
        },
        /**
         * convertRawMediaToMetaMessage
         * @param mediaInformation
         * @param keys
         * @returns {*[]}
         */
        convertRawMediaToMetaMessage: function (mediaInformation, keys = []) {
            let info = [];
            for (const [key, value] of Object.entries(mediaInformation)) {
                if (keys.length && keys.includes(key)) {
                    info.push({
                        key: key,
                        value: value
                    })
                }
            }
            return info;
        },

        /**
         * getMetaValue
         * @param key
         * @param meta
         */
        getMetaValue: function (key, meta) {
            const filters = meta.filter((item) => item.key === key);
            return filters.length > 0 ? filters[0].value : '';
        },

        /**
         *
         * parseResponse
         * @param text
         * @returns {any}
         */
        parseResponse: function (text) {
            let data = JSON.parse(text);
            if (data.hasOwnProperty("meta")) {
                data.meta = JSON.parse(data.meta);
            }
            return data;
        },
        /**
         *
         * @param messageList
         * @param newMessages
         * @param where
         * @returns {exports}
         */
        appendMessages: function (messageList, newMessages, where) {
            const to = where === DIRECTION.NEXT ? where : DIRECTION.PREVIOUS;
            let messages = messageList();
            switch (to) {
                case DIRECTION.PREVIOUS:
                    messages.splice(0, 0, ...newMessages);
                    break;
                default:
                    messages.push(...newMessages)
                    break
            }
            messageList(messages);
            return this;
        },
        /**
         * messageUniqueIdGenerate
         */
        messageUniqueIdGenerate: function () {
            return mageUtils.uniqueid(32)
        },
        /**
         *
         */
        nlToBr: function (text) {
            if (!text) return '';
            return text.replace(/\r\n|\r|\n/g, '<br>');
        },
        /**
         *
         * @param text
         */
        preWrap: function (text) {
            if (!text)
                return '';
            if (text.endsWith("\n") || text.endsWith("\r\n")) {
                text += "\u200B";
            }
            return text;
        }
    }
});
