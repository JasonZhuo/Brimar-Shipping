define([
    "jquery",
    "ko",
    "uiComponent",
    "Magento_Checkout/js/model/quote",
    "Magento_Checkout/js/model/shipping-service",
    "Magento_Checkout/js/model/shipping-rate-registry",
    "mage/translate",
    "Magento_Catalog/js/price-utils",
    "Magento_Checkout/js/action/select-shipping-method",
    "mage/url",
], function (
    $,
    ko,
    Component,
    quote,
    shippingService,
    rateRegistry,
    $t,
    priceUtils,
    selectShippingMethodAction,
    urlBuilder
) {
    "use strict";

    return Component.extend({
        defaults: {
            template: "Brimar_Shipping/checkout/shipping/additional-options",
            listens: {
                "${ $.provider }:shippingAddress": "updateAddress",
            },
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
            this.updateVisibility(quote.shippingMethod());

            // 监听配送方法变化
            quote.shippingMethod.subscribe(this.updateVisibility, this);
        },

        updateVisibility: function (method) {
            this.isVisible(method && method.carrier_code === "brimar");
        },

        updateAddress: function (address) {
            if (address && address.extension_attributes) {
                this.isResidential(
                    !!address.extension_attributes.brimar_residential
                );
                this.isScheduled(
                    !!address.extension_attributes.brimar_scheduled
                );
            }
        },

        getResidentialLabel: function () {
            return (
                $t("Residential Delivery") +
                " (+" +
                priceUtils.formatPrice(
                    this.residentialSurcharge,
                    quote.getPriceFormat()
                ) +
                ")"
            );
        },

        getScheduledLabel: function () {
            return (
                $t("Scheduled Delivery") +
                " (+" +
                priceUtils.formatPrice(
                    this.scheduledSurcharge,
                    quote.getPriceFormat()
                ) +
                ")"
            );
        },

        handleOptionChange: function () {
            var shippingAddress = quote.shippingAddress();

            if (!shippingAddress) {
                console.error("Shipping address is not available");
                return;
            }

            // 确保扩展属性是直接对象（非observable）
            shippingAddress.extension_attributes = _.extend(
                shippingAddress.extension_attributes || {},
                {
                    brimar_residential: this.isResidential(),
                    brimar_scheduled: this.isScheduled(),
                }
            );

            // 确保必要字段存在
            const requiredFields = {
                countryId: shippingAddress.countryId || "US",
                postcode: shippingAddress.postcode || "90034",
                regionId: shippingAddress.regionId || "12",
                regionCode: shippingAddress.regionCode || "CA",
            };

            _.extend(shippingAddress, requiredFields);

            // 触发地址更新
            quote.shippingAddress(shippingAddress);

            // 强制刷新运费
            rateRegistry.set(shippingAddress.getCacheKey(), null);
            shippingService.getShippingRates();

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

        //
        validateBeforeSubmit: function () {
            if (!this.isVisible()) return true;

            const address = quote.shippingAddress();
            if (!address || !address.postcode) {
                console.error("Invalid shipping address");
                return false;
            }

            return true;
        },
    });
});
