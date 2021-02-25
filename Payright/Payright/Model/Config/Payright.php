<?php
/**
 * Magento 2 extensions for PayRight Payment
 *
 * @author PayRight
 * @copyright 2016-2018 PayRight https://www.payright.com.au
 * Updated on 27th March 2018
 * Added function getSiteConfig() to calculate Api and Web Url based on the selected currency.
 * Added Multi site support to get correct URL's
 */

namespace Payright\Payright\Model\Config;

use Magento\Framework\App\Config\ScopeConfigInterface as ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface as StoreManagerInterface;
use Magento\Framework\App\Request\Http as Request;
use Magento\Framework\App\State as State;

/**
 * Class Payright
 *
 * @package PayRight\PayRight\Model\Config
 */
class Payright {
    const ACTIVE = 'active';
    const TITLE = 'api_mode';
    const ORDER_STATUS = 'api_url';
    const PAYRIGHT_SHOW_PRICE_PRODUCT = 'payrightshowpriceproduct';
    const METHOD_CODE = 'payright';

    const SANDBOX_URL_ECOMM_API = 'https://sandbox.payright.com/au/checkout/';
    const PRODUCTION_URL_ECOMM_API = 'https://api.payright.com/au/checkout/';

    const SANDBOX_URL_ECOMM_API_NZ = 'https://sandbox.payright.com/nz/checkout/';
    const PRODUCTION_URL_ECOMM_API_NZ = 'https://api.payright.com/nz/checkout/';

    const SANDBOX_URL_LOAN_APPLICATION = 'https://checkout-dev.payright.com.au/';
    const PRODUCTION_URL_LOAN_APPLICATION = 'https://checkout.payright.com.au/';

    const SANDBOX_URL_LOAN_APPLICATION_NZ = 'https://checkout-dev.payright.co.nz';
    const PRODUCTION_URL_LOAN_APPLICATION_NZ = 'https://checkout.payright.co.nz';

    protected $scopeConfig;
    protected $storeManager;
    protected $request;
    protected $state;
    // protected $storeId = null;

    /**
     * Payright constructor.
     *
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Request $request
     * @param State $state
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Request $request,
        State $state
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->request = $request;
        $this->state = $state;
    }

    /**
     *
     *
     * @return mixed
     */
    public function isProductInstallmentsShow() {
        return $this->scopeConfig->getValue("payment/payright/payrightshowpriceproduct");
    }

    /**
     *
     *
     * @return mixed
     */
    public function getMerchantName() {
        return $this->scopeConfig->getValue("payment/payright/merchantname");
    }

    /**
     * Toggle sandbox mode
     *
     * @return mixed
     */
    public function getPayrightMode() {
        return $this->scopeConfig->getValue('payment/payright/sandbox');
    }

    /**
     * Get Access Token
     *
     * @return mixed
     */
    public function getAccessToken() {
        return $this->scopeConfig->getValue("payment/payright/accesstoken");
    }

    /**
     *
     *
     * @return string
     */
    public function getSandboxLoanUrl() {
        return self::SANDBOX_URL_LOAN_APPLICATION;
    }

    /**
     *
     *
     * @return string
     */
    public function getSandboxAPIUrl() {
        return self::SANDBOX_URL_ECOMM_API;
    }

    /**
     * NZ - Sandbox Loan Application
     *
     * @return string
     */
    public function getSandboxLoanUrlNz() {
        return self::SANDBOX_URL_LOAN_APPLICATION_NZ;
    }

    /**
     * NZ - Sandbox Ecommerce API
     *
     * @return string
     */
    public function getSandboxAPIUrlNz() {
        return self::SANDBOX_URL_ECOMM_API_NZ;
    }

    /**
     *
     *
     * @return string
     */
    public function getProductionLoanUrl() {
        return self::PRODUCTION_URL_LOAN_APPLICATION;
    }

    /**
     *
     *
     * @return string
     */
    public function getProductionAPIUrl() {
        return self::PRODUCTION_URL_ECOMM_API;
    }

    /**
     * NZ - Prod Loan Application
     *
     * @return string
     */
    public function getProductionLoanUrlNz() {
        return self::PRODUCTION_URL_LOAN_APPLICATION_NZ;
    }

    /**
     * NZ - Prod Ecommerce API
     *
     * @return string
     */
    public function getProductionAPIUrlNz() {
        return self::PRODUCTION_URL_ECOMM_API_NZ;
    }

}