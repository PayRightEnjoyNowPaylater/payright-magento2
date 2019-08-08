<?php

namespace Payright\Payright\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Payright\Payright\Helper\Data as Helper;


class ConfigObserver implements ObserverInterface
{
   
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Helper $payrightHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->payrightHelper = $payrightHelper;
        $this->scopeConfig = $scopeConfig;
        $this->_messageManager = $messageManager;
    }

    public function execute(EventObserver $observer)
    {
       $authToken = $this->payrightHelper->DoApiCallPayright();
       if ($authToken['status'] != 'Authenticated') {
       	$message = 'There is some problem with API Authentication details. Please check again!!';
       	$this->_messageManager->addError($message);
       } 
    }

    public function getConfigValue($field)
    {
        return $this->scopeConfig->getValue('payment/mypayright/'.$field, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }
}
