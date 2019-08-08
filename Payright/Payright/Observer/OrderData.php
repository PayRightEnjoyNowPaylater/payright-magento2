<?php

namespace Payright\Payright\Observer;

use \Magento\Framework\Json\Helper\Data as JsonHelper;
use Payright\Payright\Model\Config\EnjoynowPaylater as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;

class OrderData implements \Magento\Framework\Event\ObserverInterface
{
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
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     ) {
        $this->payrightHelper = $payrightHelper;
        $this->_jsonHelper = $jsonHelper;
        $this->session = $session;
        $this->_client = $httpClientFactory;
        $this->jsonHelper = $jsonHelper;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Function to activate the plan if order status is complete
     * @param  \Magento\Framework\Event\Observer $observer Pass the event that is mentioned in event.xml
     * @return Array Return the response of an API
     */
    
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
   
        
        $PayRightPlanId = $observer->getEvent()->getOrder()->getpayrightplanid(); 

        $statuscode = $observer->getEvent()->getOrder()->getStatus();
   

        if ($statuscode == \Magento\Sales\Model\Order::STATE_COMPLETE) {
            $authToken = $this->payrightHelper->DoApiCallPayright();
           
            $getPayRightAccessToken = $authToken['payrightAccessToken'];
            
            // Get all the configuration for API call from ecommerce 
            $getApiConfiguration = $this->payrightHelper->DoApiConfCallPayright($getPayRightAccessToken);
            $sugarToken = $getApiConfiguration['auth']['auth-token'];
            $configToken = $getApiConfiguration['configToken'];
           


            $apiURL = "api/v1/changePlanStatus";
            $returnArray = array();

            $client = $this->_client->create();
            $client->setUri($this->_sandBoxApiEndpoint.$apiURL);

            $client->setConfig(['timeout' => 300]);
            $client->setHeaders(['Content-Type: application/json', 'Accept: application/json','Authorization:'.$getPayRightAccessToken]);


            //plan_id = "C006218_021";
            $PayRightPlanId = $observer->getEvent()->getOrder()->getpayrightplanid(); 
            $PayRightPlanName = $observer->getEvent()->getOrder()->getpayrightplanname(); 



            $paramsPayright = [
                'Token' => $sugarToken,
                'ConfigToken' =>  $configToken,
                'id' => $PayRightPlanId,
                'status'=> 'Active'
            ];

        

            $client->setParameterPost($paramsPayright);
            $client->setMethod(\Zend_Http_Client::POST);

            try {
                $responseBody = $client->request()->getBody();
                $decodedData = $this->jsonHelper->jsonDecode($responseBody);
                // var_dump($decodedData);
                // die();

                $returnArray = $decodedData['data'];
                if ($decodedData['success'] == 1) {
                    $message = "<b>Your Plan ".$returnArray['name']. " has been activated.</b><br/><br/><br/><br/>";
                    return $message; 
                    // print $message;
                } else {
                    $message = "<b>There is some problem in activation of your plan. Please contact at support@payright.com.au.</b><br/><br/><br/>";
                    return $message;
                    // print $message;
                }
            } catch (\Exception $e) {
                 $message = "<b>There is some problem in activation of your plan. Please contact at support@payright.com.au.</b><br/><br/><br/>";
                 return $message;
            }
        }
    }
}
