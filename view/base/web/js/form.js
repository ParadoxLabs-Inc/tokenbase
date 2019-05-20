/*jshint jquery:true*/
define([
    "jquery"
], function($) {
    "use strict";

    $.widget('mage.tokenbaseForm', {
        options: {
            code: '',
            toggleFieldsSelector: '.hide-if-card-selected',
            toggleFieldInputs: 'input, select',
            cardSelectInput: '[name="payment[card_id]"]',
            pollInterval: 1000
        },

        toggleFields: function(disabled) {
            var fields = this.element.find(this.options.toggleFieldsSelector);
            if (fields.first().is(':hidden') !== disabled) {
                fields.toggle(!disabled);
            }

            fields.find(this.options.toggleFieldInputs).each(function(index, el) {
                el = jQuery(el);
                if (el.is(':hidden') !== disabled) {
                    el.toggle(!disabled);
                }
                if (el.prop('disabled') !== disabled) {
                    if (disabled) {
                        el.prop('disabled', disabled);
                    } else {
                        el.removeProp('disabled');
                    }
                }
            });
        },

        _create: function() {
            var cardSelect = this.element.find(this.options.cardSelectInput);

            if (cardSelect.length > 0) {
                if (cardSelect.val()) {
                    this.toggleFields(true);
                }

                cardSelect.bind('change', this.handleCardSelectChange.bind(this));

                // Interval to enforce field disablement after switching methods on admin order
                setInterval(
                    this.handleCardSelectChange.bind(this),
                    this.options.pollInterval
                );
            }

            // Disable server-side validation
            if (typeof window.order != 'undefined' && typeof window.order.addExcludedPaymentMethod == 'function') {
                window.order.addExcludedPaymentMethod(this.options.code);
            }
        },

        handleCardSelectChange: function () {
            var cardSelect = this.element.find(this.options.cardSelectInput);
            if (cardSelect.is(':hidden')) {
                return;
            }

            if (cardSelect.val()) {
                this.toggleFields(true);
            } else {
                this.toggleFields(false);
            }
        }
    });

    return $.mage.tokenbaseForm;
});
