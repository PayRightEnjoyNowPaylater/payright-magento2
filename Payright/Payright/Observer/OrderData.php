<?php

namespace Payright\Payright\Observer;

use \Magento\Framework\Json\Helper\Data as JsonHelper;
use Payright\Payright\Model\Config\Payright as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class OrderData implements \Magento\Framework\Event\ObserverInterface {
    protected $session;
    protected $_jsonHelper;
    protected $payrightConfig;
    protected $payrightHelper;
    protected $_sandBoxApiEndpoint = 'https://api.payright.com.au/';
    protected $_client;
    protected $_scopeConfig;


    public function __construct(
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        PayrightConfig $payrightConfig,
        \Magento\Framework\Session\SessionManagerInterface $session,
        Helper $payrightHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Payright\Payright\Model\Config\Payright $payrightConfig
    ) {
        $this->payrightHelper = $payrightHelper;
        $this->_jsonHelper = $jsonHelper;
        $this->session = $session;
        $this->_client = $httpClientFactory;
        $this->jsonHelper = $jsonHelper;
        $this->_scopeConfig = $scopeConfig;
        $this->_payrightConfig = $payrightConfig;
        $this->_accessToken = $this->_payrightConfig->getAccessToken();
    }

    /**
     * Function to activate the plan if order status is complete
     *
     * @param \Magento\Framework\Event\Observer $observer Pass the event that is mentioned in event.xml
     * @return Array Return the response of an API
     */

    public function execute(\Magento\Framework\Event\Observer $observer) {

        $payrightPlanId = $observer->getEvent()->getOrder()->getpayrightplanid();
        $payrightPlanName = $observer->getEvent()->getOrder()->getpayrightplanname();
        $payrightPlanCheckoutId = $observer->getEvent()->getOrder()->getpayrightcheckoutid();

        $status = $observer->getEvent()->getOrder()->getStatus();

        if ($status == \Magento\Sales\Model\Order::STATE_COMPLETE) {

            $this->payrightHelper->activatePlan($payrightPlanCheckoutId);

            $message = "<b>Your Plan " . $payrightPlanName . " has been activated.</b><br/><br/><br/><br/>";

//            if ($decodedData['success'] == 1) {
//                $message = "<b>Your Plan " . $payrightPlanName . " has been activated.</b><br/><br/><br/><br/>";
//            } else {
//                $message = "<b>There is some problem in activation of your plan. Please contact at support@payright.com.au.</b><br/><br/><br/>";
//            }

            return $message;
        }
    }
}
