<?php
/**
 * Copyright © 2015 Inchoo d.o.o.
 * created by Zoran Salamun(zoran.salamun@inchoo.net)
 */
namespace Payright\Payright\Controller\Payment;

use Payright\Payright\Helper\Data as Helper;
use Magento\Checkout\Model\Cart as CustomerCart;

class Response extends \Magento\Framework\App\Action\Action
{
    protected $_checkoutSession;
    protected $_quoteManagement;
    protected $_orderRepository;
    private $searchCriteriaBuilder;

    /**
    * @var \Magento\Sales\Model\Service\InvoiceService
    */
    protected $_invoiceService;

    /**
     * @var \Magento\Framework\DB\Transaction
     */
    protected $_transaction;
    protected $_quoteRepository;
    protected $_orderSender;
    protected $_transactionBuilder;
    protected $_transactionRepository;


    protected $_payrightHelper;



    public function __construct(
    \Magento\Framework\App\Action\Context $context,
    \Magento\Sales\Model\Service\InvoiceService $invoiceService,
    \Magento\Framework\DB\Transaction $transaction,
    \Magento\Checkout\Model\Session $checkoutSession,
    \Magento\Quote\Model\QuoteManagement $quoteManagement,
     \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
     \Magento\Quote\Model\QuoteRepository $quoteRepository,
     \Magento\Sales\Model\Order\Email\Sender\OrderSender $orderSender,
     \Magento\Sales\Model\Order\Payment\Transaction\BuilderInterface $transactionBuilder,
      \Magento\Sales\Model\OrderRepository $orderRepository,
      \Magento\Sales\Model\Order\Payment\Repository $paymentRepository,
      \Magento\Sales\Model\Order\Payment\Transaction\Repository $transactionRepository,
      Helper $payrightHelper,
      CustomerCart $cart
   ) {
        $this->_orderRepository = $orderRepository;
        $this->_checkoutSession = $checkoutSession;
        $this->_quoteManagement = $quoteManagement;
        $this->_invoiceService = $invoiceService;
        $this->_transaction = $transaction;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->_quoteRepository = $quoteRepository;
        $this->_orderSender = $orderSender;
        $this->_transactionBuilder = $transactionBuilder;
        $this->_orderRepository = $orderRepository;
        $this->_paymentRepository = $paymentRepository;
        $this->_transactionRepository = $transactionRepository;
        $this->_payrightHelper = $payrightHelper;
        $this->cart = $cart;

        parent::__construct($context);
    }
    /**
     * say hello text
     *///
    public function execute()
    {
        $ecommerceToken = $this->getRequest()->getParam('ecommtoken');
        $FetchTransactionData = $this->_payrightHelper->getPlanDataByToken($ecommerceToken);
  

        $query = $this->getRequest()->getParams();
        $query['prtransactiondata'] = $FetchTransactionData['transactionResult'];

      

        $ResponseData = json_decode($query['prtransactiondata']);

        $redirect =  $this->_processAuthCapture($ResponseData);
        $this->_redirect($redirect);
    }

    private function _processAuthCapture($query)
    {
        $quote = $this->_checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $merchant_order_id = $quote->getReservedOrderId();

        $quote_Id = $this->cart->getQuote()->getId();
        $allItems = $quote->getAllVisibleItems();

        //print_r($quote->getBillingAddress());

     

        #### handle the transaciton status
        $PayRightTransactionStatus = strtoupper($query->prtransactionStatus);
      

        switch ($PayRightTransactionStatus) {
            case  \Payright\Payright\Model\Response::RESPONSE_STATUS_SUCCESS:
            $this->_checkoutSession
                ->setLastQuoteId($quote->getId())
                ->setLastSuccessQuoteId($quote->getId())
                ->clearHelperData();

            // Create Order From Quote
            $quote->collectTotals();

            $order = $this->_quoteManagement->submit($quote);
            if ($order) {
                $this->_checkoutSession->setLastOrderId($order->getId())
                                   ->setLastRealOrderId($order->getIncrementId())
                                   ->setLastOrderStatus($order->getStatus());

                $response = $query;

                $this->_createTransaction($order, $response);
                #### now create the invoice
                $this->_createInvoice($order);

            
                $this->_savePayRightPlanId($order, $query);


                #### send the email to the user ! z
                try {
                    $this->_orderSender->send($order);
                } catch (\Exception $e) {
                    $this->_helper->debug("Transaction Email Sending Error: " . json_encode($e));
                }

                /*foreach ($allItems as $item) {
                    $itemId = $item->getItemId();//item id of particular item
                    $quoteItem=$this->getItemModel()->load($itemId);//load particular item which you want to delete by his item id
                    $quoteItem->delete();
                }


                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $cartObject = $objectManager->create('Magento\Checkout\Model\Cart')->truncate();
                $cartObject->saveQuote();

                if (!empty($quote_Id)) {
                    $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                    $resource = $objectManager->get('Magento\Framework\App\ResourceConnection');
                    $connection = $resource->getConnection();
                    $tableName = $resource->getTableName('quote');
                    $sql = "DELETE  FROM " . $tableName." WHERE entity_id = ".$quote_Id;
                    $connection->query($sql);
                }*/

     

                $this->_checkoutSession->clearQuote()->clearStorage();
                $this->_checkoutSession->clearQuote();
                $this->_checkoutSession->clearStorage();
                $this->_checkoutSession->clearHelperData();
                $this->_checkoutSession->resetCheckout();
                $this->_checkoutSession->restoreQuote();
                $this->_checkoutSession->setLoadInactive(false);

                // create objectmanager instance



                $this->messageManager->addSuccess("Payright Transaction Completed");
                $redirect = 'checkout/onepage/success';
            }
        break;
        case  \Payright\Payright\Model\Response::RESPONSE_STATUS_DECLINED:
             $this->messageManager->addError(__('Unfortunately we have been unable to approve this application based on the information provided. We apologies for the inconvenience and thank you for using Payright.'));
             $redirect = 'checkout/cart';
        break;
        case  \Payright\Payright\Model\Response::RESPONSE_STATUS_CANCELLED:
             $this->messageManager->addError(__('Payright Transaction failed. Please contact Payright on 1300 338 496 for Help.'));
             $redirect = 'checkout/cart';
        break;
        case  \Payright\Payright\Model\Response::RESPONSE_STATUS_REVIEW:
             $this->messageManager->addError(__('Unfortunately we have been unable to approve this application based on the information provided. We apologies for the inconvenience and thank you for using Payright.'));
             $redirect = 'checkout/cart';
        break;
        case  \Payright\Payright\Model\Response::RESPONSE_APPROVED_PENDINGID:
             $this->messageManager->addError(__('Unfortunately, we have not been able to verify your ID digitally. Please contact Payright on 1300 338 496 for Help.'));
             $redirect = 'checkout/cart';
        break;
        default:
              $redirect = 'checkout/cart';
 
   
        }

        return $redirect;
    }


    private function _createTransaction($order = null, $paymentData = array())
    {
        try {
            //get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData->planId);
            $payment->setTransactionId($paymentData->planId);
            $formatedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );
 
            $message = __('The authorized amount is %1.', $formatedPrice);
            //get the object of builder class
            $trans = $this->_transactionBuilder;
            $transaction = $trans->setPayment($payment)
            ->setOrder($order)
            ->setTransactionId($paymentData->planId)
            ->setFailSafe(true)
            ->build(\Magento\Sales\Model\Order\Payment\Transaction::TYPE_CAPTURE);
 
            $payment->addTransactionCommentsToOrder(
                $transaction,
                $message
            );
            $payment->setParentTransactionId(null);
            $this->_paymentRepository->save($payment);
            $this->_orderRepository->save($order);
            $transaction = $this->_transactionRepository->save($transaction);
 
            return  $transaction->getTransactionId();
        } catch (Exception $e) {
            //log errors here
        }
    }

    public function _createInvoice($order)
    {
        if ($order->canInvoice()) {
            $invoice = $this->_invoiceService->prepareInvoice($order);
            $invoice->register();
            $invoice->save();
            $transactionSave = $this->_transaction->addObject(
                $invoice
            )->addObject(
                $invoice->getOrder()
            );
            $transactionSave->save();
           
            //send notification code
            $order->addStatusHistoryComment(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )
            ->setIsCustomerNotified(true)
            ->save();
        }
    }

    private function _savePayRightPlanId($order, $query)
    {
        $order->setpayrightplanid($query->planId);
        $order->setpayrightplanname($query->planData->name);

        $order->save();
    }

    public function getItemModel()
    {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();//instance of object manager
        $itemModel = $objectManager->create('Magento\Quote\Model\Quote\Item');//Quote item model to load quote item
        return $itemModel;
    }
}
