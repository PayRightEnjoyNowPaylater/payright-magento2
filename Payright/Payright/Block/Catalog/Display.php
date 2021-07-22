<?php

namespace Payright\Payright\Block\Catalog;

use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product as Product;
use Magento\Framework\Registry as Registry;
use Magento\Directory\Model\Currency as Currency;
use Magento\Framework\Component\ComponentRegistrar as ComponentRegistrar;
use Payright\Payright\Model\Config\Payright as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;

// ini_set("display_errors", "0");

/**
 * Class Display
 *
 * @package Payright\Payright\Block\Catalog
 */
class Display extends \Magento\Framework\View\Element\Template {

    protected $product;
    protected $payrightConfig;
    protected $payrightMain;
    protected $payrightHelper;
    protected $registry;
    protected $_catalogSession;
    protected $session;
    protected $_canCallTransactionOverview = false;

    /**
     * Display constructor.
     *
     * @param Template\Context $context
     * @param Product $product
     * @param PayrightConfig $payrightConfig
     * @param Helper $payrightHelper
     * @param \Magento\Catalog\Model\Session $catalogSession
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param Registry $registry
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Product\TierPriceManagement $tierPriceManagement
     */
    public function __construct(\Magento\Framework\View\Element\Template\Context $context,
                                Product $product,
                                PayrightConfig $payrightConfig,
                                Helper $payrightHelper,
                                \Magento\Catalog\Model\Session $catalogSession,
                                \Magento\Framework\Session\SessionManagerInterface $session,
                                Registry $registry,
                                \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
                                \Magento\Catalog\Model\Product\TierPriceManagement $tierPriceManagement

    ) {
        $this->registry = $registry;
        $this->_catalogSession = $catalogSession;
        $this->product = $product;
        $this->payrightConfig = $payrightConfig;
        $this->payrightHelper = $payrightHelper;
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        $this->tierPriceManagement = $tierPriceManagement;
        parent::__construct($context);
    }

    /**
     * Get installment amount.
     *
     * @return string|void
     */
    public function getInstallmentsAmount() {
        if ($this->payrightConfig->isProductInstallmentsShow() == "1") {

            $product = $this->registry->registry('product');

            $this->session->start();

            $productId = $product->getId();
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);
            $productType = $product->getTypeID();

            if ($productType != 'bundle' && $productType != 'grouped') {
                // Return transaction overview
                return $this->payrightHelper->calculateSingleProductInstallment($product->getFinalPrice());
            } else {
                return 'exceed_amount';
            }
        }

    }

    /**
     * Check if product page installment text is active.
     *
     * @return mixed
     */
    public function IsProductPageInstallmentTextActive() {
        return $this->payrightConfig->isProductInstallmentsShow();
    }

    /**
     * Get product page installment text.
     *
     * @return mixed
     */
    public function getProductInstallmentText() {
        return "test";
    }

    /**
     * Get merchant name configuration field.
     *
     * @return mixed
     */
    public function getConfigMerchantName() {
        return $this->payrightConfig->getMerchantName();
    }
}