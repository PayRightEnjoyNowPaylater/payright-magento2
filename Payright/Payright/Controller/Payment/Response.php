<?php
/**
 * Copyright © 2021 Payright
 * Created by Brian Ng (brian.ng@payright.com.au)
 */

namespace Payright\Payright\Controller\Payment;

use Payright\Payright\Helper\Data as Helper;
use Magento\Checkout\Model\Cart as CustomerCart;

class Response extends \Magento\Framework\App\Action\Action {
    protected $_checkoutSession;
    protected $_quoteManagement;
    protected $_orderRepository;
    private $searchCriteriaBuilder;
    protected $_invoiceService;
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

    public function execute() {
        $params = $this->getRequest()->getParams();
        $checkoutId = $params['checkoutId'];
        // $orderId = $params['orderId'];
        $status = $params['status'];

        $json = $this->_payrightHelper->getPlanDataByCheckoutId($checkoutId);

        // Retrieve specific data, and sanitize / clean with string manipulation
        $resCheckoutId = isset($json["data"]["id"]) ? $json["data"]["id"] : null;
        // TODO [A] Re-enable when 'getPlanDataByCheckoutId' bug is fixed
        // $resOrderId = isset($json["data"]["merchantReference"]) ? substr($json["data"]["merchantReference"], strlen("MagePayright_")) : null;
        $resPlanId = isset($json["data"]["planId"]) ? $json["data"]["planId"] : null;
        $resPlanNumber = isset($json["data"]["planNumber"]) ? $json["data"]["planNumber"] : null;
        $resStatus = isset($json["data"]["status"]) ? $json["data"]["status"] : null; // TODO Not using it YET, using 'status' URL param.

        $responseData = $json['data'];

        $redirect = $this->_processAuthCapture($responseData);
        $this->_redirect($redirect);
    }

    /**
     * @param $query
     * @return string
     */
    private function _processAuthCapture($query) {
        $quote = $this->_checkoutSession->getQuote();
        $payment = $quote->getPayment();
        $merchant_order_id = $quote->getReservedOrderId();
        $quote_Id = $this->cart->getQuote()->getId();
        $allItems = $quote->getAllVisibleItems();

        //print_r($quote->getBillingAddress());

        // handle the transaction status
        $prTransactionStatus = strtoupper($query['status']);

        switch ($prTransactionStatus) {
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

                    // now create the invoice
                    $this->_createInvoice($order);

                    $this->_savePayrightPlanDetails($order, $query);

                    // send the email to the user
                    try {
                        $this->_orderSender->send($order);
                    } catch (\Exception $e) {
                        $this->_helper->debug("Transaction email sending error: " . json_encode($e));
                    }

                    $this->_checkoutSession->clearQuote()->clearStorage();
                    $this->_checkoutSession->clearQuote();
                    $this->_checkoutSession->clearStorage();
                    $this->_checkoutSession->clearHelperData();
                    $this->_checkoutSession->resetCheckout();
                    $this->_checkoutSession->restoreQuote();
                    $this->_checkoutSession->setLoadInactive(false);

                    // create object manager instance
                    $this->messageManager->addSuccess("Payright transaction completed.");
                    $redirect = 'checkout/onepage/success';
                    return $redirect;
                } else {
                    $redirect = 'checkout/onepage/failure';
                    return $redirect;
                }
                break;
            case \Payright\Payright\Model\Response::RESPONSE_STATUS_REVIEW:
            case  \Payright\Payright\Model\Response::RESPONSE_STATUS_DECLINED:
                $this->messageManager->addError(__('Unfortunately we have been unable to approve this application based on the information provided. We apologise the inconvenience and thank you for choosing Payright.'));
                $redirect = 'checkout/cart';
                return $redirect;
                break;
            case  \Payright\Payright\Model\Response::RESPONSE_STATUS_CANCELLED:

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
                        __('PayRight checkout and order have been cancelled.')
                    );
                } else {
                    $this->messageManager->addSuccessMessage(
                        __('PayRight checkout has been cancelled.')
                    );
                }

                $redirect = 'checkout/cart';
                return $redirect;
                break;
            case  \Payright\Payright\Model\Response::RESPONSE_APPROVED_PENDING_ID:
                $this->messageManager->addError(__('Unfortunately, we have not been able to verify your identification. Please contact Payright on 1300 338 496 for assistance.'));
                $redirect = 'checkout/cart';
                return $redirect;
                break;
            default:
                $redirect = 'checkout/cart';
                return $redirect;
        }
    }

    /**
     *
     *
     * @param null $order
     * @param array $paymentData
     * @return mixed
     */
    private function _createTransaction($order = null, $paymentData = array()) {
        try {
            // get payment object from order object
            $payment = $order->getPayment();
            $payment->setLastTransId($paymentData->planId);
            $payment->setTransactionId($paymentData->planId);
            $formattedPrice = $order->getBaseCurrency()->formatTxt(
                $order->getGrandTotal()
            );

            $message = __('The authorized amount is %1.', $formattedPrice);

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

            return $transaction->getTransactionId();
        } catch (\Exception $e) {
            //log errors here
            return $e;
        }
    }

    /**
     *
     *
     * @param $order
     */
    public function _createInvoice($order) {
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

            // send notification code
            $order->addStatusHistoryComment(
                __('Notified customer about invoice #%1.', $invoice->getId())
            )
                ->setIsCustomerNotified(true)
                ->save();
        }
    }

    /**
     *
     *
     * @param $order
     * @param $query
     */
    private function _savePayrightPlanDetails($order, $query) {
        $order->setpayrightplanid($query['planId']); // payright plan id
        $order->setpayrightplanname($query['planNumber']); // payright plan name
        $order->setpayrightcheckoutid($query['id']); // checkout id

        $order->save();
    }

    public function getItemModel() {
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance(); // Instance of object manager
        // Quote item model to load quote item
        return $objectManager->create('Magento\Quote\Model\Quote\Item');
    }
}
