/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/resource-url-manager',
        'mage/storage',
        'mage/url',
        'Magento_Checkout/js/model/payment/additional-validators',
        'Magento_Ui/js/model/messageList',
        'Magento_Customer/js/customer-data',
        'Magento_Customer/js/section-config'
    ],
    function ($, Component, quote, resourceUrlManager, storage, mageUrl, additionalValidators, globalMessageList, customerData, sectionConfig) {
        'use strict';

        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Payright_Payright/payment/payright'

            },
            getMailingAddress: function () {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            afterPlaceOrder: function () {
                setPaymentMethodAction(this.messageContainer);
                return false;
            },
            payrightPlaceOrder: function () {
                let body = $('body').loader();
                let data = $("#co-shipping-form").serialize();
                let email = quote.guestEmail;

                body.loader('show');

                // Trigger checkout process
                let url = mageUrl.build("payrightfronttest/payment/process");

                $.ajax({
                    url: url,
                    method: 'post',
                    data: data + '&email=' + email,
                    dataType: 'json',
                    success: function (response) {
                        // Retrieve direct redirect endpoint to use, for location.href
                        location.href = response;
                        body.loader('hide');
                    }
                });
            },
            getPayrightInstallmentText: function () {
                let body = $('body').loader();

                body.loader('show');

                let url = mageUrl.build("payrightfronttest/payment/installments");

                $.ajax({
                    url: url,
                    method: 'post',
                    dataType: 'json',
                    success: function (response) {

                        if (response != 'exceed_amount' && response != 'APIError') {
                            $('#payright-installment-text').html(response.numberOfRepayments);
                            $('#payright-installment-amount').html("$ " + response.loanAmountPerPayment);
                            $('.payright-margin').html(response.numberOfRepayments + " " + response.repaymentFrequency + " Instalments of $" + response.loanAmountPerPayment);
                            $('#payright-block').show();
                        } else {
                            $('#payright-block').hide();
                        }

                        /// hide the pre-loader
                        body.loader('hide');
                    }
                });

            },
            adjustTitle: function () {
                // this is to adjust the title
                let bodyWidth = document.getElementById("body-container").offsetWidth;
                let width = document.getElementById("circleArrow").offsetWidth;
                let title = document.getElementById("installmentTitle");

                if (bodyWidth > 1280) {
                    title.setAttribute('style', 'margin-top: -' + width * 8 / 13 + 'px');
                } else if (bodyWidth < 770) {
                    title.setAttribute('style', 'margin-top: -155px');
                } else {
                    title.setAttribute('style', 'margin-top: -' + width * 2 / 3 + 'px');
                }
            }

        });
    }
);
