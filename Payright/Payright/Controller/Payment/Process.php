<?php
/**
 * Copyright © 2021 Payright
 * Created by Brian Ng (brian.ng@payright.com.au)
 */

namespace Payright\Payright\Controller\Payment;

use Magento\Framework\View\Element\Template;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Checkout\Model\Cart as Cart;
use \Magento\Quote\Model\ResourceModel\Quote as QuoteRepository;
use \Magento\Framework\Json\Helper\Data as JsonHelper;

use Payright\Payright\Model\Config\Payright as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;
use \Magento\Sales\Model\Order as Order;
use \Magento\Framework\Controller\ResultFactory;

class Process extends \Magento\Framework\App\Action\Action {
    protected $_cart;
    protected $_checkoutSession;
    protected $session;
    protected $_jsonResultFactory;
    protected $_jsonHelper;
    protected $payrightHelper;
    protected $_order;
    protected $searchCriteriaBuilder;
    protected $_quoteRepository;
    protected $mathRandom;
    protected $_sandBoxApiEndpoint;

    /**
     * Process constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param Cart $cart
     * @param CheckoutSession $checkoutSession
     * @param QuoteRepository $quoteRepository
     * @param \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory
     * @param JsonHelper $jsonHelper
     * @param PayrightConfig $payrightConfig
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param Helper $payrightHelper
     * @param Order $order
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Magento\Framework\Math\Random $mathRandom
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        Cart $cart,
        CheckoutSession $checkoutSession,
        QuoteRepository $quoteRepository,
        \Magento\Framework\Controller\Result\JsonFactory $jsonResultFactory,
        JsonHelper $jsonHelper,
        PayrightConfig $payrightConfig,
        \Magento\Framework\Session\SessionManagerInterface $session,
        Helper $payrightHelper,
        Order $order,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Magento\Framework\Math\Random $mathRandom,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {

        $this->scopeConfig = $scopeConfig;
        $this->payrightConfig = $payrightConfig;

        if (($this->scopeConfig->getValue('payment/payright/sandbox')) == 0) {
            $this->_sandBoxApiEndpoint = $this->payrightConfig->getProductionLoanUrl();
        } else {
            $this->_sandBoxApiEndpoint = $this->payrightConfig->getSandboxLoanUrl();
        }

        $this->_cart = $cart;
        $this->_checkoutSession = $checkoutSession;
        $this->_jsonResultFactory = $jsonResultFactory;
        $this->payrightHelper = $payrightHelper;
        $this->_jsonHelper = $jsonHelper;
        $this->session = $session;
        $this->_order = $order;

        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;

        $this->_quoteRepository = $quoteRepository;

        $this->jsonResultFactory = $jsonResultFactory;

        $this->mathRandom = $mathRandom;

        parent::__construct($context);
    }

    /**
     *
     *
     * @return mixed
     */
    public function execute() {
        $initTransaction = $this->initTransaction();
        $result = $this->_jsonResultFactory->create();

        return $result->setData($initTransaction);
    }

    /**
     *
     *
     * @return mixed
     */
    public function initTransaction() {
        // need to load the correct quote by store
        $quote = $this->_checkoutSession->getQuote();
        $grandTotal = $quote->getGrandTotal();
        $orderId = $quote->getReservedOrderId();

        // Prepare 'sale amount' as currency format.
        $saleAmount = number_format((float)$grandTotal, 2, '.', '');

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();

        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');

        if ($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
            $customer = $customerRepository->getById($customerId);

            // customer login
            $quote->setCustomer($customer);

            $billingAddress = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();

            //check if shipping address is missing - e.g. Gift Cards
            if ((empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))) && (empty($billingAddress) || empty($billingAddress->getStreetLine(1)))) {
                $result = $this->_jsonResultFactory->create()->setData(
                    array('success' => false, 'message' => 'Please select an address')
                );

                return $result;
            } elseif (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {
                $billingAddress = $quote->getShippingAddress();
                $quote->setBillingAddress($quote->getShippingAddress());
            }
        } else {
            $post = $this->getRequest()->getPostValue();

            if (!empty($post['email'])) {
                $email = htmlspecialchars($post['email'], ENT_QUOTES);
                $email = filter_var($email, FILTER_SANITIZE_EMAIL);
                try {
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $quote->setCustomerEmail($email)
                            ->setCustomerIsGuest(true)
                            ->setCustomerGroupId(\Magento\Customer\Api\Data\GroupInterface::NOT_LOGGED_IN_ID);
                    }
                } catch (\Exception $e) {
                    $result = $this->_jsonResultFactory->create()->setData(
                        array('error' => 1, 'message' => $e->getMessage())
                    );
                    return $result;
                }
            }
        }

        // get the payment method
        $payment = $quote->getPayment();
        $payment->setMethod('payright');
        $quote->reserveOrderId();

        $quote->setPayment($payment);
        $this->_quoteRepository->save($quote);
        $this->_checkoutSession->replaceQuote($quote);

        // Generate 'expiresAt', set to expire 6 months from today's datetime.
        // Also, 'expiresAt' is currently 'optional' usage.
        $dt = new \DateTime();
        $interval = new \DateInterval('P6M');
        $dt->add($interval);
        $dt->setTimeZone(new \DateTimeZone('UTC'));
        $expiresAt = $dt->format('Y-m-d\TH-i-s.\0\0\0\Z');

        // Capture the 'orderId', for further processing.
        $capturedOrderId = $orderId;

        // Build 'merchantReference'
        $merchantReference = "MagePayright_" . $capturedOrderId;

        // Build redirect url
        $storeManager = $objectManager->get('\Magento\Store\Model\StoreManagerInterface');
        $redirectUrl = $storeManager->getStore()->getBaseUrl() . 'payrightfronttest/payment/response';

        $responseArray = $this->payrightHelper->performApiCheckout($merchantReference, $saleAmount, $redirectUrl, $expiresAt);

        // Capture Checkout Id (DO NOT REMOVE)
        $redirectEndpoint = $responseArray['data']['redirectEndpoint'];

        // Return redirect url payload to javascript
        return $redirectEndpoint;
    }
}
