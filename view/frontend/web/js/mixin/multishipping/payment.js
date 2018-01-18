define([
    'jquery'
], function ($) {
    'use strict';

    return function (widget) {
        // This method normally disables and hides payment methods and injects a hidden 'free' input if $0.
        // We want to allow real payment, and Magento shows zero-subtotal method anyway, so just turning this
        // off won't cause any problems.
        $.widget('mage.payment', widget, {
            _disablePaymentMethods: function () {
                this._enablePaymentMethods();
            }
        });

        return $.mage.payment;
    };
});
