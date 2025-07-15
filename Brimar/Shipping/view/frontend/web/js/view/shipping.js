define([
    "uiComponent",
    "Magento_Checkout/js/model/quote",
    "Magento_Checkout/js/checkout-data",
], function (Component, quote, checkoutData) {
    "use strict";

    return Component.extend({
        initialize: function () {
            this._super();
            quote.shippingAddress.subscribe(function (address) {
                if (address) {
                    window.checkoutConfig.shippingFormTemplate = {
                        template: "Brimar_Shipping/checkout/shipping",
                    };
                }
            });
        },
    });
});
