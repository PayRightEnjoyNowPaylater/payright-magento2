<?php
/**
 * Copyright © 2015 Inchoo d.o.o.
 * created by Zoran Salamun(zoran.salamun@inchoo.net)
 */
namespace Payright\Payright\Controller\Payment;

use Payright\Payright\Helper\Data as Helper;
use \Magento\Framework\Controller\Result\JsonFactory as JsonResultFactory;
use \Magento\Framework\Json\Helper\Data as JsonHelper;

ini_set("display_errors", "1");

//namespace Magento\SamplePaymentGateway\Controller\Payment;
//
 
class Cancel extends \Magento\Framework\App\Action\Action
{
    //protected $_checkoutSession;
    // protected $_quoteManagement;

    protected $checkoutSession;
    protected $orderRepository;
    protected $payrightHelper;


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Sales\Api\OrderRepositoryInterface $orderRepository,
        \Magento\Checkout\Model\Session $checkoutSession,
        Helper $payrightHelper,
        JsonResultFactory $jsonResultFactory,
        JsonHelper $jsonHelper
    ) {
        $this->_checkoutSession = $checkoutSession;
        $this->_orderRepository = $orderRepository;
        $this->payrightHelper = $payrightHelper;
        $this->_jsonHelper = $jsonHelper;
        $this->_jsonResultFactory = $jsonResultFactory;

        parent::__construct($context);
    }
    /**
     * say hello text
     */
    
    public function execute()
    {

        $ecommerceToken = $this->getRequest()->getParam('ecommtoken');
        $FetchTransactionData = $this->payrightHelper->getPlanDataByToken($ecommerceToken,'');

        
        if (isset($FetchTransactionData['transactionData'])) {
            $postedData = $this->_jsonHelper->jsonDecode($FetchTransactionData['transactionData']);

            if (isset($FetchTransactionData['sugar_response'])) {
                $dataForPlanId = $this->_jsonHelper->jsonDecode($FetchTransactionData['sugar_response']);
                $this->payrightHelper->planStatusChange($dataForPlanId['planId']);
            }

            $planName = $FetchTransactionData['transactionGeneratePlanname'];
    
            $this->payrightHelper->setCancelProcessQueue($planName);

        }

        
       // TODO verify if this logic of order cancellation is deprecated
        // if there is an order - cancel it
        $orderId = $this->_checkoutSession->getLastOrderId();

        $order = $orderId ? $this->_orderFactory->create()->load($orderId) : false;

        if ($order && $order->getId() && $order->getQuoteId() == $this->_checkoutSession->getQuoteId()) {
            //$order->cancel()->save();
            $order->setIsActive(true)->save();
            $this->_checkoutSession
                ->unsLastQuoteId()
                ->unsLastSuccessQuoteId()
                ->unsLastOrderId()
                ->unsLastRealOrderId();
            $this->messageManager->addSuccessMessage(
                __('PayRight Checkout and Order have been canceled.')
            );
        } else {
            $this->messageManager->addSuccessMessage(
                __('PayRight Checkout has been canceled.') 
            );
        }

        $redirect = 'checkout/cart';
        $this->_redirect($redirect);

        

    }
}
