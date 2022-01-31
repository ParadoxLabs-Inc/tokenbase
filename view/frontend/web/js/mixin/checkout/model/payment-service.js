define([
    'underscore',
    'Magento_Checkout/js/model/payment/method-list',
    'mage/utils/wrapper'
], function (_, methodList, wrapper) {
    'use strict';

    return function (target) {
        // Removing free check. Eligibility should be handled fine on the server side--we won't be given a
        // method that doesn't actually apply. If ZeroTotal is enabled and applicable, great.
        target.getAvailablePaymentMethods = function() {
            return methodList().slice();
        };

        target.setPaymentMethods = wrapper.wrapSuper(target.setPaymentMethods, function(methods) {
            var filteredMethods = methods;

            if (methods && methods.length > 0) {
                var freeMethod = _.find(methods, function (paymentMethod) {
                    return paymentMethod.method === 'free';
                });
                var filteredMethods = _.without(methods, freeMethod);
            }

            this._super(filteredMethods);
        });

        return target;
    };
});
