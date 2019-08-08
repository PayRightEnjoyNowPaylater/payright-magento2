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
                template: 'Payright_Payright/payment/mypayright'

            },
            /** Returns send check to info */
            getMailingAddress: function() {
                return window.checkoutConfig.payment.checkmo.mailingAddress;
            },
            afterPlaceOrder: function () {
                setPaymentMethodAction(this.messageContainer);
                return false;
            },
            payrightPlaceOrder: function () {
                var body = jQuery('body').loader();
                var data = $("#co-shipping-form").serialize();
                var email = quote.guestEmail;

                body.loader('show'); 
                // Making sure it using current flow
                var url = mageUrl.build("payrightfronttest/payment/process"); 
                data = data + '&email=' + email;
                
                $.ajax({
                    url: url,
                    method:'post',
                    data: data,
                    dataType: 'json',
                    success: function(response) {

                    $.mage.redirect(response.redirectUrl+response.ecommerceToken);
                    //$.mage.redirect('https://betaonlineapi.payright.com.au/loan/new/'+response.ecommerceToken);
                    body.loader('hide'); 
                    }
                });




            },
            getPayrightInstallmentText: function() {

                var url = mageUrl.build("payrightfronttest/payment/Installments"); 
                /// start  the loader
                var body = jQuery('body').loader();
                body.loader('show'); 
                
                $.ajax({
                    url: url,
                    method:'post',
                    dataType: 'json',
                    success: function(response) {
                        //console.log(response+'This is resposne');

                       if (response != 'exceed_amount' && response != 'APIError') {
                        $('#ppInstallmentText').html(response.noofrepayments);
                        $('#ppInstallmentAmount').html("$ "+response.LoanAmountPerPayment); 
                        $('#payrightmargin').html(response.noofrepayments+" "+response.repaymentfrequency+" Instalments of $"+response.LoanAmountPerPayment);
                        $('#payrightBlock').show();
                    } else {
                        $('#payrightBlock').hide();
                       }

                        
                        /// hide the preloader 
                        body.loader('hide');
                    }
                });

            },
            adjustTitle:function() 
            {
                /// this is to adjust the title
                var bodywidth = document.getElementById("bodybox").offsetWidth;
                var width = document.getElementById("circleArrow").offsetWidth;
                var title = document.getElementById("installmentTitle");
                //      alert(bodywidth)
                if (bodywidth > 1280) {
                    title.setAttribute( 'style', 'margin-top: -'+width*8/13 +'px' );
                }
                else if (bodywidth < 770) {
                    title.setAttribute( 'style', 'margin-top: -155px' );
                }
                else {
                    title.setAttribute( 'style', 'margin-top: -'+width*2/3 +'px' );
                }

            }
           
        });
    }
);
