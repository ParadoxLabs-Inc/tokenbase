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

define(
    [
        'ko',
        'jquery',
        'underscore',
        'ParadoxLabs_TokenBase/js/view/payment/method-renderer/cc',
        'mage/translate',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/quote'
    ],
    function (ko, $, _, Component, $t, placeOrderAction, additionalValidators, alert, quote) {
        'use strict';
        var config=null;
        return Component.extend({
            defaults: {
                template: 'ParadoxLabs_TokenBase/payment/ach',
                isFormShown: true,
                save: config ? config.canSaveCard && config.defaultSaveCard : false,
                selectedCard: config ? config.selectedCard : '',
                storedCards: config ? config.storedCards : {},
                achAccountTypes: config ? config.achAccountTypes : {},
                logoImage: config ? config.logoImage : false,
                achImage: config ? config.achImage : false
            },

            /**
             * @override
             */
            initObservable: function () {
                this.initVars();
                this.observe([
                        'echeckAccountName',
                        'echeckBankName',
                        'echeckRoutingNumber',
                        'echeckAccountNumber',
                        'echeckAccountType'
                    ])
                this._super();

                return this;
            },

            /**
             * Note: We're explicitly skipping AdditionalValidators.validate() here due to issues caused with
             * third-party checkouts and incidental effects those validators can cause (AJAX requests, submitting
             * forms ... ???). This means the submit button won't respect all validators, but those will still be
             * checked upon submit and stop processing--so that's okay.
             *
             * @override
             */
            checkPlaceOrderAllowed: function (value) {
                if (quote.billingAddress() === null) {
                    return false;
                }

                if (this.selectedCard()) {
                    return true;
                }

                if (this.echeckAccountName()
                    && this.echeckBankName()
                    && this.echeckRoutingNumber()
                    && this.echeckAccountNumber()
                    && this.echeckAccountType()
                    && this.validate()) {
                    return true;
                }

                return false;
            },

            /**
             * @override
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    additional_data: {
                        'card_id': this.selectedCard(),
                        'echeck_account_name': this.echeckAccountName(),
                        'echeck_bank_name': this.echeckBankName(),
                        'echeck_routing_no': this.echeckRoutingNumber(),
                        'echeck_account_no': this.echeckAccountNumber(),
                        'echeck_account_type': this.echeckAccountType(),
                        'save': this.save()
                    }
                };
            },

            getAchAccountTypes: function() {
                return _.map(this.achAccountTypes, function(value, key) {
                    return {
                        'value': key,
                        'label': value
                    }
                });
            },

            getAchImage: function() {
                return this.achImage;
            },

            getAchTypeTitleByCode: function(code) {
                var title = '';
                _.each(this.getAchAccountTypes(), function (value) {
                    if (value['value'] === code) {
                        title = value['type'];
                    }
                });
                return title;
            },

            getInfo: function() {
                return [
                    {'name': $t('Name on Account'), value: this.echeckAccountName()},
                    {'name': $t('Type'), value: $t(this.getAchTypeTitleByCode(this.echeckAccountType()))}
                ];
            },

            /**
             * @override
             */
            validate: function () {
                if (this.selectedCard()) {
                    return true;
                }

                return $.validator.validateSingleElement('#' + this.item.method + '-echeck-account-name')
                    && $.validator.validateSingleElement('#' + this.item.method + '-echeck-bank-name')
                    && $.validator.validateSingleElement('#' + this.item.method + '-echeck-routing-number')
                    && $.validator.validateSingleElement('#' + this.item.method + '-echeck-account-number')
                    && $.validator.validateSingleElement('#' + this.item.method + '-echeck-account-type');
            }
        });
    }
);
