<?php

namespace Payright\Payright\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Payright\Payright\Helper\Data as Helper;

/**
 * Class ConfigObserver
 *
 * @package Payright\Payright\Observer
 */
class ConfigObserver implements ObserverInterface {

    protected $_payrightConfig;
    public $_accessToken;

    /**
     * ConfigObserver constructor.
     *
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     * @param  \Payright\Payright\Helper\Data  $payrightHelper
     * @param  \Magento\Framework\Message\ManagerInterface  $messageManager
     * @param  \Payright\Payright\Model\Config\Payright  $payrightConfig
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Helper $payrightHelper,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Payright\Payright\Model\Config\Payright $payrightConfig
    ) {
        $this->payrightHelper = $payrightHelper;
        $this->scopeConfig = $scopeConfig;
        $this->_messageManager = $messageManager;
        $this->_payrightConfig = $payrightConfig;
        $this->_accessToken = $this->_payrightConfig->getAccessToken();
    }

    /**
     * To execute Observer operations.
     *
     * @param  \Magento\Framework\Event\Observer  $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer) {
        $data = $this->payrightHelper->performApiGetRates();
        $isInvalidAccessToken = isset($data['status']) && isset($data['message']);

        $authToken = $this->_accessToken;

        $emptyAuthToken = is_string($authToken) && strlen(trim($authToken)) === 0;

        if ($emptyAuthToken) {
            $message = 'We require your \'Access Token\', it can be obtained from your merchant store at the developer portal.';
            $this->_messageManager->addError($message);
        } else if ($isInvalidAccessToken) {
            $message = 'Your \'Access Token\' is invalid, please specify the correct \'access token\' and store \'region\'.';
            $this->_messageManager->addError($message);
        } else {
            $message = 'Your access token is saved. Please back up your access token ' . $authToken . ' for safe-keeping.';
            $this->_messageManager->addSuccess($message);
        }
    }
}
