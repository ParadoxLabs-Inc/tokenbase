define(
    [
        'ko',
        'jquery',
        'Magento_Payment/js/view/payment/cc-form',
        'Magento_Checkout/js/action/place-order',
        'Magento_Payment/js/model/credit-card-validation/credit-card-number-validator',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/modal/alert'
    ],
    function (ko, $, Component, placeOrderAction, cardNumberValidator, additionalValidators, alert) {
        'use strict';
        var config=null;
        return Component.extend({
            isShowLegend: function() {
                return true;
            },
            isActive: function() {
                return true;
            },
            placeOrderFailure: ko.observable(false),
            defaults: {
                template: 'ParadoxLabs_TokenBase/payment/cc',
                isCcFormShown: true,
                isCcvShown: true,
                save: config ? config.canSaveCard && config.defaultSaveCard : false,
                selectedCard: config ? config.selectedCard : '',
                storedCards: config ? config.storedCards : {},
                availableCardTypes: config ? config.availableCardTypes : {},
                creditCardExpMonth: config ? config.creditCardExpMonth : null,
                creditCardExpYear: config ? config.creditCardExpYear : null,
                logoImage: config ? config.logoImage : false
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

                this.isCcFormShown = ko.computed(function () {
                    return !this.useVault()
                        || this.selectedCard() === undefined
                        || this.selectedCard() == '';
                }, this);

                this.isCcvShown = ko.computed(function () {
                    return this.requireCcv()
                        || !this.useVault()
                        || this.selectedCard() === undefined
                        || this.selectedCard() == '';
                }, this);

                return this;
            },
            /**
             * @override
             */
            getData: function () {
                return {
                    'method': this.item.method,
                    additional_data: {
                        'save': this.save(),
                        'cc_type': this.selectedCardType() != '' ? this.selectedCardType() : this.creditCardType(),
                        'cc_exp_year': this.creditCardExpYear(),
                        'cc_exp_month': this.creditCardExpMonth(),
                        'cc_number': this.creditCardNumber(),
                        'cc_cid': this.creditCardVerificationNumber(),
                        'card_id': this.selectedCard()
                    }
                };
            },
            getCode: function () {
                return '';
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
            placeOrder: function (data, event) {
                var self = this;
                self.placeOrderFailure(false);
                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    // This mess for CE 2.0 compatibility, following CE 2.1 interface change. If 2.1+...
                    if( typeof this.getPlaceOrderDeferredObject === 'function' ) {
                        this.getPlaceOrderDeferredObject()
                            .fail(function(response){
                                self.handleFailedOrder(response).bind(this);
                            }).done(
                                function () {
                                    self.afterPlaceOrder();

                                    if (self.redirectAfterPlaceOrder) {
                                        // This dependency doesn't exist prior to 2.1. Can't require it up-front.
                                        require(['Magento_Checkout/js/action/redirect-on-success'],
                                            function(redirectOnSuccessAction) {
                                                redirectOnSuccessAction.execute();
                                            }
                                        );
                                    }
                                }
                            );
                    }
                    else {
                        // If 2.0...
                        $.when(
                            placeOrderAction(this.getData(), this.redirectAfterPlaceOrder, this.messageContainer)
                        ).fail(function(response){
                            self.handleFailedOrder(response).bind(this);
                        }).done(this.afterPlaceOrder.bind(this));
                    }

                    return true;
                }

                return false;
            },
            /**
             * @override
             */
            validate: function () {
                return true;
            },
            handleFailedOrder: function (response) {
                this.placeOrderFailure(true);
                this.isPlaceOrderActionAllowed(true);

                var error = JSON.parse(response.responseText);
                if (error && typeof error.message != 'undefined') {
                    alert({
                        content: error.message
                    });
                }
            }
        });
    }
);
