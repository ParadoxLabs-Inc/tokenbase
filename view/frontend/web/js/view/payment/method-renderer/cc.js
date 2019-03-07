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
        'mage/validation'
    ],
    function (ko, $, Component, placeOrderAction, cardNumberValidator, additionalValidators, alert, quote) {
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
                availableCardTypes: config ? config.availableCardTypes : {},
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
                        'requireCcv'
                    ]);

                this.placeOrderFailure = ko.observable(false);

                this.isFormShown = ko.computed(function () {
                    return !this.useVault()
                        || this.selectedCard() === undefined
                        || this.selectedCard() === '';
                }, this);

                this.isCcvShown = ko.computed(function () {
                    return this.requireCcv()
                        || !this.useVault()
                        || this.selectedCard() === undefined
                        || this.selectedCard() === '';
                }, this);

                this.readyToPlaceOrder = ko.computed(this.checkPlaceOrderAllowed, this);
                this.readyToPlaceOrder.subscribe(function (readyFlag) {
                    this.isPlaceOrderActionAllowed(readyFlag);
                }.bind(this));

                return this;
            },

            checkPlaceOrderAllowed: function (value) {
                if (quote.billingAddress() === null) {
                    return false;
                }

                if (this.selectedCard() && (this.isCcvShown() === false || this.creditCardVerificationNumber())) {
                    return true;
                }

                if (this.creditCardNumber()
                    && this.creditCardExpYear()
                    && this.creditCardExpMonth()
                    && this.creditCardType()
                    && (this.isCcvShown() === false || this.creditCardVerificationNumber())
                    && this.validate()
                    && additionalValidators.validate()) {
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
                this.isPlaceOrderActionAllowed(this.checkPlaceOrderAllowed());

                var error = JSON.parse(response.responseText);
                if (error && typeof error.message !== 'undefined') {
                    alert({
                        title: $.mage.__('Unable to place order'),
                        content: error.message
                    });
                }
            }
        });
    }
);
