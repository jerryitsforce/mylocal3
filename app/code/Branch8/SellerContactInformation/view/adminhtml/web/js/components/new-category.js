define([
    'underscore',
    'Magento_Catalog/js/components/new-category',
    'jquery'
], function (_, Category, $) {
    'use strict';

    function flattenCollection(array, separator, created) {
        var i = 0,
            length,
            childCollection;

        array = _.compact(array);
        length = array.length;
        created = created || [];

        for (i; i < length; i++) {
            created.push(array[i]);

            if (array[i].hasOwnProperty(separator)) {
                childCollection = array[i][separator];
                delete array[i][separator];
                flattenCollection.call(this, childCollection, separator, created);
            }
        }

        return created;
    }

    return Category.extend({
        /**
         * Set option to options array.
         *
         * @param {Object} option
         * @param {Array} options
         */
        setOption: function (option, options) {
            var parent = parseInt(option.parent);
            if (_.contains([0, 1], parent)) {
                options = options || this.cacheOptions.tree;
                options.push(option);

                var copyOptionsTree = JSON.parse(JSON.stringify(this.cacheOptions.tree));
                this.cacheOptions.plain = flattenCollection(copyOptionsTree, this.separator);
                this.options(this.cacheOptions.tree);
            } else {
                this._super(option, options);
            }
        },
        toggleOptionSelected: function (data) {
            var self = this;
            var isSelected = this.isSelected(data.value);

            if (this.lastSelectable && data.hasOwnProperty(this.separator)) {
                return this;
            }

            if (!this.multiple) {
                if (!isSelected) {
                    this.value(data.value);
                }
                this.listVisible(false);
            } else {
                if (!isSelected) { /*eslint no-lonely-if: 0*/
                    this.value.push(data.value);
                    /* to add current category and callled recursive function for selecting parent category*/
                    $.each(data.optgroup, function (key, values) {
                        self.value.push(values.value);
                        if (typeof values.optgroup !== 'undefined') {
                            self.childToParentIterate(values, 'add');
                        }
                    });
                    if (typeof data.optgroup === 'undefined') {
                        self.childToParentIterate(data, 'add');
                    }

                } else {
                    this.value(_.without(this.value(), data.value));
                    $.each(data.optgroup, function (key, optval) {
                        self.value.pop(optval.value);
                        var optgroup = optval.optgroup;
                        self.childRemove(optgroup);
                    });
                }
            }
        },
        childRemove: function(optgroup){
            var self = this;
            $.each(optgroup, function( key,val ) {
                self.value.pop(val.value);
                self.childRemove(val.optgroup);
            });
        },
        childToParentIterate: function(data, op){
            var self = this;
            var path = data.path;
            var level = data.level;
            $.each(data.optgroup, function( key, value ) {
                var optgroup = value.optgroup;
                self.value.push(value.value);
                $.each(optgroup, function( optkey,optval ) {
                    var label = optval.label;
                    if(op == "add")
                    {
                        self.value.push(optval.value);
                    }
                    self.childIteration(optval,op,level,path);
                });
            });
        },
        childIteration: function(data, op,level,path){
            var self = this;
            if(data.level < level){
                var optgroup = data.optgroup;
                $.each(optgroup, function( optkey,optval ) {
                    var label = optval.label;
                    if(op == "add")
                    {
                        self.value.push(optval.value);
                    }
                    self.childIteration(optval,op,level,path);
                });
            }
        }
    });
});
