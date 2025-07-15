define([
    "jquery",
    "ko",
    "uiComponent",
    "Magento_Checkout/js/model/quote",
    "Magento_Checkout/js/model/new-customer-address",
    "Magento_Checkout/js/model/shipping-rate-registry",
    "Magento_Checkout/js/model/shipping-service",
    "Magento_Checkout/js/checkout-data",
    "mage/translate",
    "Magento_Catalog/js/price-utils",
    "mage/url",
], function (
    $,
    ko,
    Component,
    quote,
    newCustomerAddress,
    rateRegistry,
    shippingService,
    checkoutData,
    $t,
    priceUtils,
    urlBuilder
) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Brimar_Shipping/cart/shipping/additional-options",
        },

        isVisible: ko.observable(false),
        isResidential: ko.observable(false),
        isScheduled: ko.observable(false),
        residentialSurcharge:
            window.checkoutConfig.brimarShipping?.residentialSurcharge || 2.0,
        scheduledSurcharge:
            window.checkoutConfig.brimarShipping?.scheduledSurcharge || 3.0,

        initialize: function () {
            this._super();

            if (
                !quote.shippingAddress() ||
                typeof quote.shippingAddress().getType !== "function"
            ) {
                const addressData = {
                    countryId: "US",
                    regionId: "12", //  required
                    regionCode: "CA", //  required
                    postcode: "90034", //  required
                    city: "Los Angeles", // recommended
                    street: ["Street Address"], //recommended
                    firstname: "Guest", // required
                    lastname: "User", //  required
                    telephone: "1234567890", //  required
                    saveInAddressBook: false,
                    customAttributes: [
                        {
                            attributeCode: "brimar_residential",
                            value: "0",
                        },
                        {
                            attributeCode: "brimar_scheduled",
                            value: "0",
                        },
                    ],
                };

                const address = newCustomerAddress(addressData);
                quote.shippingAddress(address);
            }

            // Initialize states
            this.updateVisibility(quote.shippingMethod());
            this.updateExtensionAttributes(quote.shippingAddress());

            // Subscribe to changes
            quote.shippingMethod.subscribe(this.updateVisibility, this);
            quote.shippingAddress.subscribe(
                this.updateExtensionAttributes,
                this
            );
        },

        updateVisibility: function (method) {
            this.isVisible(method && method.carrier_code === "brimar");
        },

        updateExtensionAttributes: function (address) {
            if (!address) {
                address = {
                    countryId: "US",
                    extensionAttributes: {
                        brimar_residential: false,
                        brimar_scheduled: false,
                    },
                };
                quote.shippingAddress(address);
            } else {
                address.extensionAttributes = address.extensionAttributes || {
                    brimar_residential: false,
                    brimar_scheduled: false,
                };
            }

            this.isResidential(
                !!address.extensionAttributes.brimar_residential
            );
            this.isScheduled(!!address.extensionAttributes.brimar_scheduled);
        },

        getResidentialLabel: function () {
            const currencyConfig =
                window.checkoutConfig.quoteData &&
                window.checkoutConfig.quoteData.base_currency_code
                    ? window.checkoutConfig.priceFormat
                    : customerData.get("price-format").priceFormat;

            // get price format from window.priceConfig
            const priceFormat =
                window.priceConfig && window.priceConfig.priceFormat
                    ? window.priceConfig.priceFormat
                    : currencyConfig;
            return (
                $t("Residential Delivery") +
                " (+" +
                priceUtils.formatPrice(this.residentialSurcharge, priceFormat) +
                ")"
            );
        },

        getScheduledLabel: function () {
            return (
                $t("Scheduled Delivery") +
                " (+" +
                priceUtils.formatPrice(this.scheduledSurcharge) +
                ")"
            );
        },

        handleOptionChange: function () {
            var shippingAddress = quote.shippingAddress();
            if (!shippingAddress) {
                shippingAddress = {
                    countryId: "US", //  required
                    postcode: "90034", //  required
                    regionId: "12", // recommended
                    regionCode: "CA", // optional
                    street: ["Street Address"], //optional
                    city: "Los Angeles", // optional
                    extension_attributes: {
                        brimar_residential: this.isResidential(),
                        brimar_scheduled: this.isScheduled(),
                    },
                };
            } else {
                shippingAddress.extensionAttributes =
                    shippingAddress.extensionAttributes || {};
                shippingAddress.extensionAttributes.brimar_residential =
                    this.isResidential();
                shippingAddress.extensionAttributes.brimar_scheduled =
                    this.isScheduled();
            }

            quote.shippingAddress(shippingAddress);
            checkoutData.setShippingAddressFromData(shippingAddress);
            //console.log(shippingAddress);
            // Trigger rates reload
            // forceShippingRatesUpdate();
            rateRegistry.set(shippingAddress.getKey(), null);
            shippingService.getShippingRates(shippingAddress);
            console.log(window.checkoutConfig.quoteData.entity_id);
            // ajax sumbit the data to backend
            $.ajax({
                url: urlBuilder.build("brimarshipping/address/save"),
                type: "POST",
                data: {
                    quote_id: window.checkoutConfig.quoteData.entity_id,
                    is_residential: this.isResidential() ? 1 : 0,
                    is_scheduled: this.isScheduled() ? 1 : 0,
                },
                dataType: "json",
            }).done(function (response) {
                console.log("Address saved:", response);
            });
            //this.forceShippingRatesUpdate();
        },

        forceShippingRatesUpdate: function () {
            let shippingAddress = quote.shippingAddress();

            if (!shippingAddress) {
                shippingAddress = {
                    countryId: "US",
                    postcode: "",
                    extensionAttributes: {
                        brimar_residential: this.isResidential(),
                        brimar_scheduled: this.isScheduled(),
                    },
                };
                quote.shippingAddress(shippingAddress);
            }
            // Clear cached rates
            rateRegistry.set(shippingAddress.getKey(), null);
            rateRegistry.set(shippingAddress.getCacheKey(), null);

            console.log(shippingAddress);
            // Trigger rates reload
            shippingService.getShippingRates(shippingAddress);
        },
    });
});
