/**
 * Paradox Labs, Inc.
 * http://www.paradoxlabs.com
 * 717-431-3330
 *
 * Need help? Open a ticket in our support system:
 *  http://support.paradoxlabs.com
 *
 * @category    ParadoxLabs
 * @package     TokenBase
 * @author      Ryan Hoerr <support@paradoxlabs.com>
 * @license     http://store.paradoxlabs.com/license.html
 */

define(
    [
        'ko',
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/action/place-order',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/modal/alert',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader',
        'mage/validation'
    ],
    function (ko, $, Component, placeOrderAction, cardNumberValidator, additionalValidators, alert, quote, fullScreenLoader) {
        'use strict';
        var config=null;
        return Component.extend({
            defaults: {
                template: 'ParadoxLabs_TokenBase/payment/cc',
                isFormShown: true,
                isCcvShown: true,
                save: config ? config.canSaveCard && config.defaultSaveCard : false,
                selectedCard: config ? config.selectedCard : '',
                storedCards: config ? config.storedCards : {},
                creditCardExpMonth: config ? config.creditCardExpMonth : null,
                creditCardExpYear: config ? config.creditCardExpYear : null,
                logoImage: config ? config.logoImage : false
            },

            isShowLegend: function() {
                return true;
            },

            isActive: function() {
                return true;
            },

            initVars: function() {
                this.canSaveCard     = config ? config.canSaveCard : false;
                this.forceSaveCard   = config ? config.forceSaveCard : false;
                this.defaultSaveCard = config ? config.defaultSaveCard : false;
                this.requireCcv      = config ? config.requireCcv : false;
            },

            /**
             * @override
             */
            initObservable: function () {
                this.initVars();
                this._super()
                    .observe([
                        'selectedCard',
                        'save',
                        'storedCards',
                        'requireCcv',
                        'creditCardNumberFormatted'
                    ]);

                this.placeOrderFailure = ko.observable(false);

                this.isTokenizing = ko.observable(false);
                this.isTokenizing.subscribe(this.spinner.bind(this));

                this.selectedCard.subscribe(this.handleSelectedCardType.bind(this));

                this.isFormShown = ko.computed(function () {
                    return !this.useVault()
                        || this.selectedCard() === undefined
                        || this.selectedCard() === '';
                }, this);

                this.isCcvShown = ko.computed(function () {
                    return this.hasVerification()
                        && (this.requireCcv()
                            || !this.useVault()
                            || this.selectedCard() === undefined
                            || this.selectedCard() === '');
                }, this);

                this.isPlaceOrderActionAllowed = ko.computed({
                    read: this.checkPlaceOrderAllowed,
                    write: function() {},
                    owner: this
                });

                this.creditCardNumberFormatted.subscribe(function(value) {
                    this.creditCardNumber(value.replace(/\D/g,''));
                }.bind(this));

                // Trigger form validation periodically for clean UX.
                setInterval(
                    function() {
                        this.selectedCard.notifySubscribers();
                    }.bind(this),
                    100
                );

                return this;
            },

            /**
             * Note: We're explicitly skipping AdditionalValidators.validate() here due to issues caused with
             * third-party checkouts and incidental effects those validators can cause (AJAX requests, submitting
             * forms ... ???). This means the submit button won't respect all validators, but those will still be
             * checked upon submit and stop processing--so that's okay.
             */
            checkPlaceOrderAllowed: function () {
                if (quote.billingAddress() === null || this.isTokenizing() === true) {
                    return false;
                }

                if (this.selectedCard()
                    && (this.isCcvShown() === false || this.creditCardVerificationNumber())) {
                    return true;
                }

                if (this.creditCardNumber()
                    && this.creditCardExpYear()
                    && this.creditCardExpMonth()
                    && (this.isCcvShown() === false || this.creditCardVerificationNumber())
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
                        'save': this.save(),
                        'cc_type': this.selectedCardType() ? this.selectedCardType() : this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'cc_cid': this.creditCardVerificationNumber(),
                        'card_id': this.selectedCard()
                    }
                };
            },

            getCode: function () {
                return this.item.method;
            },

            useVault: function() {
                return this.getStoredCards().length > 0;
            },

            forceSaveCard: function() {
                return this.forceSaveCard;
            },

            isCcDetectionEnabled: function() {
                return true;
            },

            getStoredCards: function() {
                return this.storedCards();
            },

            getLogoImage: function() {
                return this.logoImage;
            },

            /**
             * @override
             */
            validate: function () {
                if (this.selectedCard()) {
                    return true;
                }

                return $.validator.validateSingleElement('#' + this.item.method + '-cc-number')
                    && $.validator.validateSingleElement('#' + this.item.method + '-cc-exp-month')
                    && $.validator.validateSingleElement('#' + this.item.method + '-cc-cid');
            },

            /**
             * @override
             */
            getPlaceOrderDeferredObject: function () {
                this.placeOrderFailure(false);

                return this._super()
                           .fail(this.handleFailedOrder.bind(this));
            },

            handleFailedOrder: function (response) {
                this.placeOrderFailure(true);

                var error = JSON.parse(response.responseText);
                if (error && typeof error.message !== 'undefined') {
                    alert({
                        title: $.mage.__('Unable to place order'),
                        content: error.message
                    });
                }
            },

            handleSelectedCardType: function () {
                var cardId = this.selectedCard();
                if (cardId === null || cardId === undefined) {
                    return;
                }

                for (var card of this.storedCards()) {
                    if (card.id === cardId) {
                        this.creditCardType(card.type);
                    }
                }
            },

            spinner: function(isTokenizing) {
                if (isTokenizing === true) {
                    fullScreenLoader.startLoader();
                } else {
                    fullScreenLoader.stopLoader();
                }
            }
        });
    }
);
