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
 * Class Payovertime
 * @package PayRight\PayRight\Model\Config
 */
class EnjoynowPaylater
{


	const ACTIVE  = 'active';
	const TITLE   = 'api_mode';
	const ORDER_STATUS  = 'api_url';
	const PAYRIGHTSHOWPRICEPRODUCT  = 'payrightshowpriceproduct';
    const METHOD_CODE = 'mypayright';

    //const SANDBOX_URL_LOAN_APPLICATION = 'http://customerpayrightportal.local/loan/new/';
    //const SANDBOX_URL_ECOMM_API = 'http://ecommerceapi.payright.local/';

    const SANDBOX_URL_LOAN_APPLICATION = 'https://betaonline.payright.com.au/loan/new/';
    const SANDBOX_URL_ECOMM_API = 'https://api.payright.com.au/';

    //const SANDBOX_URL_ECOMM_API = 'https://betaonlineapi.payright.com.au/';
    
    const PRODUCTION_URL_ECOMM_API = 'https://liveapi.payright.com.au/';
    const PRODUCTION_URL_LOAN_APPLICATION = 'https://online.payright.com.au/loan/new/';



    protected $scopeConfig;
    protected $storeManager;
    protected $request;
    protected $state;


	// protected $storeId = null;

    /**
     * Payovertime constructor.
     * @param ApiMode $apiMode
     * @param ScopeConfigInterface $scopeConfig
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
    * @return bool
    */
    public function isProductInstallmentsShow()
    {
    	$showPriceConfig = $this->scopeConfig->getValue("payment/mypayright/payrightshowpriceproduct");
    	return $showPriceConfig;
    }

    /**
    * @return string
    */
    public function getMerchantName()
    {
        $merchantName = $this->scopeConfig->getValue("payment/mypayright/merchantname");
      
        return $merchantName;
    }

    public function getPayrightMode()
    {
        $PayrightMode = $this->scopeConfig->getValue('payment/mypayright/sandbox');
        return $PayrightMode;
    }
    public function getoption()
    {
         $showPriceConfig = $this->scopeConfig->getValue("payment/mypayright/modaloption");
        return $showPriceConfig;
    }

    public function getSandboxLoanUrl()
    {
        return self::SANDBOX_URL_LOAN_APPLICATION;
    }

    public function getSandboxAPIUrl()
    {
        return self::SANDBOX_URL_ECOMM_API;
    }

    public function getProductionLoanUrl()
    {
        return self::PRODUCTION_URL_LOAN_APPLICATION;
    }

    public function getProductionAPIUrl()
    {
        return self::PRODUCTION_URL_ECOMM_API;
    }





}