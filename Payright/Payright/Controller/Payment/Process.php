<?php
/**
 * Copyright © 2015 Inchoo d.o.o.
 * created by Zoran Salamun(zoran.salamun@inchoo.net)
 */
namespace Payright\Payright\Controller\Payment;

use Magento\Framework\View\Element\Template;

use \Magento\Checkout\Model\Session as CheckoutSession;
use \Magento\Checkout\Model\Cart as Cart;
use \Magento\Store\Model\StoreResolver as StoreResolver;
use \Magento\Quote\Model\ResourceModel\Quote as QuoteRepository;
use \Magento\Framework\Json\Helper\Data as JsonHelper;

use Payright\Payright\Model\Config\EnjoynowPaylater as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;
use \Magento\Sales\Model\Order as Order;
use \Magento\Sales\Api\OrderRepositoryInterface as OrderRepositoryInterface;
use \Magento\Framework\Controller\ResultFactory;

class Process extends \Magento\Framework\App\Action\Action
{
    /**
     * say hello text
     */

    protected $_cart;
    protected $_checkoutSession;
    protected $session;
    protected $_jsonResultFactory;
    protected $_jsonHelper;
    protected $payrightHelper;
    protected $_order;

    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    protected $orderRepository;
    protected $searchCriteriaBuilder;
    protected $_quoteRepository;
    protected $mathRandom;
    protected $_sandBoxApiEndpoint;


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
        if (($this->scopeConfig->getValue('payment/mypayright/sandbox')) == 0) {
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
        $this->order = $order;

        $this->_orderRepository = $orderRepository;
        $this->_searchCriteriaBuilder = $searchCriteriaBuilder;

        $this->_quoteRepository = $quoteRepository;

        $this->jsonResultFactory = $jsonResultFactory;

        $this->mathRandom = $mathRandom;

        parent::__construct($context);
    }


    public function execute()
    {
        $IntialiseTransaction = $this->intialiseTransactionNew();
        $result = $this->_jsonResultFactory->create();
        return $result->setData($IntialiseTransaction);
    }



    public function intialiseTransactionNew()
    {
        //need to load the correct quote by store
        $data = $this->_checkoutSession->getData();
        $quote = $this->_checkoutSession->getQuote();
        $grandTotal = $quote->getGrandTotal();


        $data['platform_type'] = 'magento';
        $data['transactionTotal'] =  $grandTotal;
        $CurrentPrApiToken =  $this->session->getPrApiToken();

        // #### do the PayRight API Call's to config
        if (empty($CurrentPrApiToken)) {
            $ApiAuthCall = $this->payrightHelper->DoApiCallPayright();
            $this->session->setPrApiToken($ApiAuthCall['payrightAccessToken']);
            $CurrentPrApiToken =  $this->session->getPrApiToken();
        }

        ### if the tokens are empty then
        $DoConfigurationcall = $this->payrightHelper->DoApiTransactionConfCallPayright($CurrentPrApiToken);

        $this->session->setPrSugarToken($DoConfigurationcall['auth']['auth-token']);
        $this->session->setPrConfigToken($DoConfigurationcall['configToken']);

        $CurrentPrSugarToken = $this->session->getPrSugarToken();
        $CurrentPrConfigToken = $this->session->getPrConfigToken();

        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');

        if ($customerSession->isLoggedIn()) {
            $customerId = $customerSession->getCustomer()->getId();
            $customer = $customerRepository->getById($customerId);

            // customer login
            $quote->setCustomer($customer);

            $billingAddress  = $quote->getBillingAddress();
            $shippingAddress = $quote->getShippingAddress();

            //check if shipping address is missing - e.g. Gift Cards
            if ((empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))) && (empty($billingAddress) || empty($billingAddress->getStreetLine(1)))) {
                $result = $this->_jsonResultFactory->create()->setData(
                            array('success' => false, 'message' => 'Please select an Address')
                        );

                return $result;
            }
            // else if( empty($shippingAddress) || empty($shippingAddress->getStreetLine(1))  || empty($shippingAddress->getFirstname()) ) {
            //     $shippingAddress = $quote->getBillingAddress();
            //     $quote->setShippingAddress($quote->getBillingAddress());
            // }
            elseif (empty($billingAddress) || empty($billingAddress->getStreetLine(1)) || empty($billingAddress->getFirstname())) {
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


        ##### get the payment method
        $payment = $quote->getPayment();
        $payment->setMethod('mypayright');
        $quote->reserveOrderId();

        $quote->setPayment($payment);
        $this->_quoteRepository->save($quote);
        $this->_checkoutSession->replaceQuote($quote);


        $encodeCheckoutSessionData = json_encode($data);


        if (!empty($CurrentPrApiToken) && !empty($CurrentPrSugarToken) && !empty($CurrentPrConfigToken)) {
            $merchantReference = 'Magento_'.$this->mathRandom->getRandomNumber(5);

            
            $intialiseTransactionToken =  $this->payrightHelper->DoApiIntializeTransaction($CurrentPrApiToken, $CurrentPrSugarToken, $CurrentPrConfigToken, $encodeCheckoutSessionData, $this->payrightHelper->getConfigValue('client_id'), $merchantReference);

            if ($intialiseTransactionToken['configToken'] != '') {
                $this->payrightHelper->DoSendInitialTranscation($this->payrightHelper->getConfigValue('client_id'));
            }
        }

        $responseArray['ecommerceToken'] = $intialiseTransactionToken['ecommToken'];
        $responseArray['redirectUrl'] = $this->_sandBoxApiEndpoint;

        return $responseArray;
    }
}
