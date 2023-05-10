/**
 * Copyright © 2015-present ParadoxLabs, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 * Need help? Try our knowledgebase and support system:
 * @link https://support.paradoxlabs.com
 */

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
            cardTypeInput: '[name="payment[cc_type]"]',
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
                el.prop('disabled', disabled);
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

            var selectedCard = cardSelect.find(':selected');
            if (selectedCard.length > 0 && selectedCard.data('type')) {
                this.element.find(this.options.cardTypeInput).val(
                    selectedCard.data('type')
                );
            }
        }
    });

    return $.mage.tokenbaseForm;
});
