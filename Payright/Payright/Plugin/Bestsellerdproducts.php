<?php

namespace Payright\Payright\Plugin;

use Payright\Payright\Model\Config\Payright as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;

ini_set("display_errors", "1");

/**
 * Class Bestsellerdproducts
 *
 * @package Payright\Payright\Plugin
 */
class Bestsellerdproducts {
    protected $product;
    protected $payrightConfig;
    protected $payrightMain;
    protected $payrightHelper;
    protected $registry;
    protected $_catalogSession;
    protected $session;
    protected $_client;
    protected $jsonHelper;
    protected $_canCallTransactionOverview = false;

    /**
     * Bestsellerdproducts constructor.
     *
     * @param  \Payright\Payright\Model\Config\Payright  $payrightConfig
     * @param  \Payright\Payright\Helper\Data  $payrightHelper
     * @param  \Magento\Catalog\Model\Session  $catalogSession
     * @param  \Magento\Framework\Session\SessionManagerInterface  $session
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     */
    public function __construct(
        PayrightConfig $payrightConfig,
        Helper $payrightHelper,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->payrightConfig = $payrightConfig;
        $this->payrightHelper = $payrightHelper;
        $this->scopeConfig = $scopeConfig;
        $this->session = $session;
    }

    /**
     * Get product price, for HTML render.
     *
     * @param  \Magento\CatalogWidget\Block\Product\ProductsList  $productlist
     * @param $result
     * @param  \Magento\Catalog\Model\Product  $product
     * @return mixed|string
     */
    public function afterGetProductPriceHtml(\Magento\CatalogWidget\Block\Product\ProductsList $productlist, $result, \Magento\Catalog\Model\Product $product) {
        $productId = $product->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);
        $productType = $product->getTypeID();
        $finalPrice = $product->getFinalPrice();


        if ($productType != 'bundle' && $productType != 'grouped') {
            $this->session->start();
            $resultNew = $this->payrightHelper->calculateSingleProductInstallment($finalPrice);

            if ($resultNew != 'exceed_amount' && $resultNew != 'APIError' && ($this->getConfigValue('bestsellerinstalments') == "1")) {
                $resultString = "<div class='installments' style='padding: 2px;
         margin-bottom: 10px;'>or <strong> " . $resultNew['numberOfRepayments'] . " </strong>" . $resultNew['repaymentFrequency'] . " payments of $" . "<strong>" . $resultNew['loanAmountPerPayment'] . "</strong> with <span class='payright-logo'><img ></span></div>";
                return $result . $resultString;
            } else {
                if (($this->scopeConfig->getValue('payment/payright/sandbox')) == 1) {
                    $resultString = "<div class='installments' style='padding: 2px; margin-bottom: 10px;'>There are issues fetching your rates. Please review your Developer Portal.</div>";
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
