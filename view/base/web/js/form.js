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
            cardSelectInput: '[name="payment[card_id]"]'
        },

        toggleFields: function(disabled) {
            var fields = this.element.find(this.options.toggleFieldsSelector);

            if (disabled) {
                fields.hide();
            }
            else {
                fields.show();
            }

            fields.find(this.options.toggleFieldInputs).prop('disabled', disabled);
        },

        _create: function() {
            var cardSelect  = this.element.find(this.options.cardSelectInput);
            var self        = this;

            if (cardSelect.length > 0) {
                if (cardSelect.val()) {
                    this.toggleFields(true);
                }

                cardSelect.bind('change', function () {
                    if ($(this).val()) {
                        self.toggleFields(true);
                    } else {
                        self.toggleFields(false);
                    }
                });
            }
        }
    });

    return $.mage.tokenbaseForm;
});
