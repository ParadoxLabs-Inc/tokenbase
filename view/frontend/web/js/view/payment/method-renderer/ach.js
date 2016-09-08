define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'mage/translate',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/modal/alert'
    ],
    function (ko, $, Component, $t, placeOrderAction, additionalValidators, alert) {
        'use strict';
        var config=null;
        return Component.extend({
            isShowLegend: function() {
                return true;
            },
            isActive: function() {
                return true;
            },
            defaults: {
                template: 'ParadoxLabs_TokenBase/payment/ach',
                isAchFormShown: true,
                save: config ? config.canSaveCard && config.defaultSaveCard : false,
                selectedCard: config ? config.selectedCard : '',
                storedCards: config ? config.storedCards : {},
                achAccountTypes: config ? config.achAccountTypes : {},
                logoImage: config ? config.logoImage : false,
                achImage: config ? config.achImage : false,
                echeckAccountName: '',
                echeckBankName: '',
                echeckRoutingNumber: '',
                echeckAccountNumber: '',
                echeckAccountType: ''
            },
            initVars: function() {
                this.canSaveCard     = config ? config.canSaveCard : false;
                this.forceSaveCard   = config ? config.forceSaveCard : false;
                this.defaultSaveCard = config ? config.defaultSaveCard : false;
            },
            /**
             * @override
             */
            initObservable: function () {
                this.initVars();
                this._super()
                    .observe([
                        'echeckAccountName',
                        'echeckBankName',
                        'echeckRoutingNumber',
                        'echeckAccountNumber',
                        'echeckAccountType',
                        'selectedCard',
                        'save',
                        'storedCards'
                    ]);

                this.isAchFormShown = ko.computed(function () {
                    return !this.useVault()
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
            getCode: function () {
                return '';
            },
            useVault: function() {
                return this.getStoredCards().length > 0;
            },
            forceSaveCard: function() {
                return this.forceSaveCard;
            },
            getStoredCards: function() {
                return this.storedCards();
            },
            getLogoImage: function() {
                return this.logoImage;
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
                    if (value['value'] == code) {
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
            placeOrder: function (data, event) {
                var self = this,
                    placeOrder;

                if (event) {
                    event.preventDefault();
                }

                if (this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);

                    // This mess for CE 2.0 compatibility, following CE 2.1 interface change. If 2.1+...
                    if( typeof this.getPlaceOrderDeferredObject === 'function' ) {
                        this.getPlaceOrderDeferredObject()
                            .fail(
                                function (response) {
                                    self.isPlaceOrderActionAllowed(true);

                                    var error = JSON.parse(response.responseText);
                                    if (error && typeof error.message != 'undefined') {
                                        alert({
                                            content: error.message
                                        });
                                    }
                                }
                            ).done(
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
                        ).fail(function (response) {
                            self.isPlaceOrderActionAllowed(true);

                            var error = JSON.parse(response.responseText);
                            if (error && typeof error.message != 'undefined') {
                                alert({
                                    content: error.message
                                });
                            }
                        })
                        .done(this.afterPlaceOrder.bind(this));
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
            }
        });
    }
);
