
var config = {
    map: {
        '*': {
            tokenbaseForm: 'ParadoxLabs_TokenBase/js/form',
            tokenbaseCardFormatter: 'ParadoxLabs_TokenBase/js/cardFormatter'
        }
    },
    config: {
        mixins: {
            'Magento_Multishipping/js/payment': {
                'ParadoxLabs_TokenBase/js/mixin/multishipping/payment': true
            },
            'Magento_Checkout/js/model/payment-service': {
                'ParadoxLabs_TokenBase/js/mixin/checkout/model/payment-service': true
            }
        }
    }
};
