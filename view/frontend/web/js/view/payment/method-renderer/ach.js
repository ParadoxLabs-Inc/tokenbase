/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
define(
    [
        'ko',
        'underscore',
        'Magento_Checkout/js/view/payment/default',
        'mage/translate'
    ],
    function (ko, _, Component, $t) {
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
                save: config ? config.canSaveCard : false,
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
                this.canSaveCard    = config ? config.canSaveCard : false;
                this.forceSaveCard  = config ? config.forceSaveCard : false;
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
            }
        });
    }
);
