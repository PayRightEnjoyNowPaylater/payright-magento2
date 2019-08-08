/*jshint jquery:true*/
define(
    [
        'jquery',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/url-builder',
        'mage/storage',
        'Magento_Checkout/js/model/error-processor',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, quote, urlBuilder, storage, errorProcessor, customer, fullScreenLoader) {
        'use strict';
        // return Component.extend({});
        // return function (messageContainer) {

        //         var body = jQuery('body').loader();
        //         body.loader('show'); 
        //         var url = 'http://payrightmagentov1.local:8888/payrightfronttest/payment/process';
        //         console.log(quote.getCalculatedTotal());
        //         console.log(window.checkoutConfig);

              

        //         $.ajax({
        //             url: url,
        //             data:{ FinalTotal: quote.getCalculatedTotal()},
        //             method:'post',
        //             dataType: 'JSON',
        //             success: function(response) {
        //                  $.mage.redirect('https://customerpayrightportal.local/loan/'+response.ecommToken);
        //                  body.loader('hide'); 
        //             }
        //         });



        // }
    }
);