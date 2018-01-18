define([
    'underscore',
    'Magento_Checkout/js/model/payment/method-list'
], function (_, methodList) {
    'use strict';

    return function (target) {
        // Removing free check. Eligibility should be handled fine on the server side--we won't be given a
        // method that doesn't actually apply. If ZeroTotal is enabled and applicable, great.
        target.getAvailablePaymentMethods = function() {
            var methods = [];
            _.each(methodList(), function (method) {
                methods.push(method);
            });

            return methods;
        };

        return target;
    };
});
