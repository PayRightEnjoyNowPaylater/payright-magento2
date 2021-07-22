<?php

namespace Payright\Payright\Plugin;

use Payright\Payright\Helper\Data as Helper;
use Payright\Payright\Model\Config\Payright as PayrightConfig;

// ini_set("display_errors", "0");

/**
 * Class RelatedProducts
 *
 * @package Payright\Payright\Plugin
 */
class RelatedProducts {
    protected $product;
    protected $payrightConfig;
    protected $payrightMain;
    protected $payrightHelper;
    protected $session;
    protected $_catalogSession;

    /**
     * RelatedProducts constructor.
     *
     * @param  \Payright\Payright\Model\Config\Payright  $payrightConfig
     * @param  \Payright\Payright\Helper\Data  $payrightHelper
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     * @param  \Magento\Catalog\Model\Session  $catalogSession
     * @param  \Magento\Framework\Session\SessionManagerInterface  $session
     */
    public function __construct(
        PayrightConfig $payrightConfig,
        Helper $payrightHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\Session\SessionManagerInterface $session
    ) {
        $this->payrightConfig = $payrightConfig;
        $this->payrightHelper = $payrightHelper;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
    }

    /**
     * Get product price.
     *
     * @param  \Magento\Catalog\Block\Product\ProductList\Related  $subject
     * @param $result
     * @param  \Magento\Catalog\Model\Product  $product
     * @return mixed|string
     */
    public function afterGetProductPrice(\Magento\Catalog\Block\Product\ProductList\Related $subject, $result, \Magento\Catalog\Model\Product $product) {
        $finalPrice = $product->getFinalPrice();
        // $instalments = $this->payrightHelper->calculateSingleProductInstallment($finalPrice);
        $productType = $product->getTypeID();

        if ($productType != 'bundle' && $productType != 'grouped' && $this->getConfigValue('relatedinstalments') == "1") {
            $this->session->start();
            $instalments = $this->payrightHelper->calculateSingleProductInstallment($finalPrice);

            if ($instalments != 'exceed_amount' && $instalments != 'APIError') {
                $prinstalments = "<div class='installments' style='padding: 10px;
         margin-bottom: 10px;'>or <strong>" . $instalments['numberOfRepayments'] . "</strong>" . " Fortnightly " . "payments of $" . "<strong>" . $instalments['loanAmountPerPayment'] . "</strong> with<span class='payright-logo'><img ></span> </div>";

                return $result . $prinstalments;
            } else {
                if (($this->scopeConfig->getValue('payment/payright/sandbox')) == 1) {
                    $resultString = "<div class='installments' style='padding: 2px; margin-bottom: 10px;'>There is some problem with API!!</div>";
                    return $result . $resultString;
                } else {
                    return $result;
                }
            }
        } else {
            return $result;
        }
    }

    /**
     * Get configuration field, by global use.
     *
     * @param $field
     * @return mixed
     */
    public function getConfigValue($field) {
        return $this->scopeConfig->getValue('payment/payright/' . $field);
    }
}
