/*jshint jquery:true*/
define([
    "jquery"
], function($) {
    "use strict";

    return {
        options: {
            code : ""
        },

        enableDisableFields: function(disabled) {
            var toggleFields = $('#payment_form_' + this.options.code + ' .hide_if_card_selected');

            if (disabled) {
                toggleFields.hide();
            }
            else {
                toggleFields.show();
            }

            toggleFields.find('input, select').prop('disabled', disabled);
        },

        _create: function() {
            var cardSelect = $('#' + this.options.code + '_card_id');

            if (cardSelect.length > 0) {
                var self = this;

                if (cardSelect.val()) {
                    this.enableDisableFields(true);
                }

                cardSelect.bind('change', function (e) {
                    if ($(this).val()) {
                        self.enableDisableFields(true);
                    } else {
                        self.enableDisableFields(false);
                    }
                });
            }
        }
    };
});
