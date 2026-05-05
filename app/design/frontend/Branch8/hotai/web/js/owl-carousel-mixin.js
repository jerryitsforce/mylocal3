define([
    'jquery',
    'plugins/DOMPurify'
], function ($, DOMPurify) {
    'use strict';

    return function (target) {
        var Owl = $.fn.owlCarousel && $.fn.owlCarousel.Constructor;

        if (!Owl) {
            return target;
        }

        // 1. Patch Workers: Secure the Cloning Logic
        // The original worker creates clones using outerHTML strings which is an XSS sink.
        if (Owl.Workers) {
            Owl.Workers.forEach(function (worker) {
                if (worker.filter && worker.filter.indexOf('items') > -1 && worker.filter.indexOf('settings') > -1 && worker.run.toString().indexOf('outerHTML') > -1) {
                    var originalRun = worker.run;
                    worker.run = function () {
                        // We cannot easily change the internal logic of the run function because variables like 'h' and 'i' are local strings.
                        // However, we can intercept the jQuery append/prepend calls if we could, but we can't easily.
                        // A better approach for the worker might be to let it run, but since it uses $stage.append(), it might be caught by our other patches if we had global jQuery patches.
                        // But since we don't, we have to try to sanitize the _items before they are used, or override the method.

                        // Overriding the specific cloning worker entirely is safer.
                        // Original Logic Reference (approximate):
                        // var b = [], c = this._items, ... h = "", i = ""; ... h += c[...].outerHTML ... this._clones = b, a(h).addClass("cloned").appendTo(this.$stage), a(i).addClass("cloned").prependTo(this.$stage)

                        // Implementation of safe run:
                        var b = [],
                            c = this._items,
                            d = this.settings,
                            e = Math.max(2 * d.items, 4),
                            f = 2 * Math.ceil(c.length / 2),
                            g = d.loop && c.length ? d.rewind ? e : Math.max(e, f) : 0,
                            h = "",
                            i = "";

                        for (g /= 2; g > 0;) {
                            b.push(this.normalize(b.length / 2, true));
                            h += c[b[b.length - 1]][0].outerHTML;
                            b.push(this.normalize(c.length - 1 - (b.length - 1) / 2, true));
                            i = c[b[b.length - 1]][0].outerHTML + i;
                            g -= 1;
                        }
                        this._clones = b;

                        // SANITIZATION HERE
                        $(DOMPurify.sanitize(h, { RETURN_DOM_FRAGMENT: true })).addClass("cloned").appendTo(this.$stage);
                        $(DOMPurify.sanitize(i, { RETURN_DOM_FRAGMENT: true })).addClass("cloned").prependTo(this.$stage);
                    };
                }
            });
        }

        // 2. Patch Navigation Plugin: Secure Dots Rendering
        if (Owl.Plugins && Owl.Plugins.Navigation && Owl.Plugins.Navigation.prototype) {
            var NavProto = Owl.Plugins.Navigation.prototype;
            var originalDraw = NavProto.draw;

            NavProto.draw = function () {
                var settings = this._core.settings;

                // If dotsData is enabled, templates are built from data-dot attributes which could be unsafe.
                // The original code: this._controls.$absolute.html(this._templates.join(""))

                // We create a proxy for $absolute.html to sanitize input
                if (this._controls && this._controls.$absolute) {
                    var originalHtml = this._controls.$absolute.html;
                    var $absolute = this._controls.$absolute;

                    // Override html method for this specific element instance temporarily or strictly context-bound
                    // Actually, simpler: before listing join, sanitize the templates?
                    // But templates are HTML strings (buttons).

                    // Best bet: Sanitize the final string before it goes to .html() inside the original draw?
                    // No, we can't inject inside originalDraw. We must replace it or prep the data.

                    if (settings.dotsData && this._templates && this._templates.length) {
                        // Sanitize each template part
                        this._templates = this._templates.map(function (tpl) {
                            return DOMPurify.sanitize(tpl);
                        });
                    }
                }

                // Monkey-patch jQuery .html() just for the execution of draw? A bit risky.
                // Let's reimplement the relevant part of draw if dotsData is used.

                if (settings.dotsData) {
                    var b = this._pages.length - this._controls.$absolute.children().length;
                    if (settings.dots && settings.dotsData && b !== 0) {
                        // SAFE IMPLEMENTATION
                        var content = this._templates.join("");
                        this._controls.$absolute.empty().append(DOMPurify.sanitize(content, { RETURN_DOM_FRAGMENT: true }));

                        // Handle the rest of draw logic for 'active' class
                        this._controls.$absolute.find(".active").removeClass("active");
                        this._controls.$absolute.children().eq($.inArray(this.current(), this._pages)).addClass("active");

                        // We must prevent originalDraw from destroying our work or double-appending if we call it.
                        // If we reimplement, we don't call original.
                        // But draw also handles Nav buttons ($relative).

                        // Let's just let originalDraw handle nav buttons, but we handle dots?
                        // Original draw is monolithic.

                        // Better: Replace .html() on the jQuery object of $absolute
                        var realHtml = this._controls.$absolute.html;
                        var self = this;
                        this._controls.$absolute.html = function (content) {
                            return realHtml.call(self._controls.$absolute, DOMPurify.sanitize(content));
                        };

                        originalDraw.apply(this, arguments);

                        // Restore
                        this._controls.$absolute.html = realHtml;
                        return;
                    }
                }

                return originalDraw.apply(this, arguments);
            };
        }

        return target;
    };
});
