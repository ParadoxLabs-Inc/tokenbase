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
    "jquery",
    'Magento_Ui/js/modal/confirm'
], function($, confirmation) {
    $.widget('mage.tokenbaseConfirmation', {
        options: {
            deleteSelector: '.action.delete',
            confirmTitle: 'Delete payment option',
            confirmMessage: 'Are you sure you want to remove this card?'
        },

        _create: function() {
            this.element.on(
                'click',
                this.options.deleteSelector,
                this.handleDeleteClick.bind(this)
            );
        },

        /**
         * Handle delete button click event
         *
         * @param {Event} e
         */
        handleDeleteClick: function(e) {
            e.preventDefault();

            confirmation({
                title: $.mage.__(this.options.confirmTitle),
                content: $.mage.__(this.options.confirmMessage),
                actions: {
                    confirm: this.deleteCard.bind(this, e.currentTarget),
                    cancel: this.cancelDelete.bind(this)
                }
            });
        },

        /**
         * Delete the card via AJAX
         *
         * @param {HTMLElement} target
         */
        deleteCard: function(target) {
            var item = $(target).closest('fieldset');

            $.ajax({
                url: target.href,
                type: "POST",
                showLoader: true,
                async: true
            }).done(this.handleDeleteResponse.bind(this, item));
        },

        /**
         * Handle delete AJAX response
         *
         * @param {jQuery} item
         * @param {Object} data
         */
        handleDeleteResponse: function(item, data) {
            if (data.success) {
                item.remove();
            } else {
                location.assign(location.href);
            }
        },

        /**
         * Handle cancel action
         *
         * @returns {boolean}
         */
        cancelDelete: function() {
            return false;
        }
    });

    return $.mage.tokenbaseConfirmation;
});
