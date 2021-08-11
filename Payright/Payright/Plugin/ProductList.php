<?php

namespace Payright\Payright\Plugin;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product as Product;
use Magento\Framework\Registry as Registry;
use Magento\Directory\Model\Currency as Currency;
use Magento\Framework\Component\ComponentRegistrar as ComponentRegistrar;
use Payright\Payright\Model\Config\Payright as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;

ini_set("display_errors", "0");

/**
 * Class ProductList
 *
 * @package Payright\Payright\Plugin
 */
class ProductList {
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
     * ProductList constructor.
     *
     * @param  \Magento\Framework\View\Element\Template\Context  $context
     * @param  \Magento\Catalog\Model\Product  $product
     * @param  \Payright\Payright\Model\Config\Payright  $payrightConfig
     * @param  \Payright\Payright\Helper\Data  $payrightHelper
     * @param  \Magento\Catalog\Model\Session  $catalogSession
     * @param  \Magento\Framework\Session\SessionManagerInterface  $session
     * @param  \Magento\Framework\Registry  $registry
     * @param  \Magento\Framework\Json\Helper\Data  $jsonHelper
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        Product $product,
        PayrightConfig $payrightConfig,
        Helper $payrightHelper,
        \Magento\Catalog\Model\Session $catalogSession,
        \Magento\Framework\Session\SessionManagerInterface $session,
        Registry $registry,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->registry = $registry;
        $this->_catalogSession = $catalogSession;

        $this->payrightConfig = $payrightConfig;
        $this->payrightHelper = $payrightHelper;
        $this->session = $session;
        $this->jsonHelper = $jsonHelper;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get product details, for HTML render.
     *
     * @param  \Magento\Catalog\Block\Product\ListProduct  $subject
     * @param  \Closure  $proceed
     * @param  \Magento\Catalog\Model\Product  $product
     */
    public function aroundGetProductDetailsHtml(
        \Magento\Catalog\Block\Product\ListProduct $subject,
        \Closure $proceed,

        \Magento\Catalog\Model\Product $product
    ) {
        $this->product = $product;


        $productId = $product->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);
        $productType = $product->getTypeID();

        if (($productType != 'bundle' && $productType != 'grouped') && ($this->getConfigValue('payrightshowpricecategory') == "1")) {
            echo $this->getPrice($product->getFinalPrice());
        }
    }

    /**
     * Get price.
     *
     * @param $finalPrice
     * @return string
     */
    public function getPrice($finalPrice) {
        $this->session->start();
        $result = $this->payrightHelper->calculateSingleProductInstallment($finalPrice);

        if ($result != 'exceed_amount' && $result != 'APIError') {
            $resultString = "<div class='installments' style='padding: 10px;
         margin-bottom: 10px;'>or <strong>" . $result['numberOfRepayments'] . "</strong>" . " Fortnightly " . "payments of $" . "<strong>" . $result['loanAmountPerPayment'] . "</strong> with <span class='payright-logo'></span> 

         <img id='payright-logo' >";

            return $resultString;
        } else {
            if (($this->scopeConfig->getValue('payment/payright/sandbox')) == 1) {
                $resultString = "<div class='installments' style='padding: 2px; margin-bottom: 10px;'>There is some problem with API!!</div>";
                return $resultString;
            }
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
