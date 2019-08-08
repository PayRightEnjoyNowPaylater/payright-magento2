<?php
/**
 * Magento 2 extensions for PayRight Payment
 *
 * @author PayRight
 * @copyright 2016-2018 PayRight https://www.payright.com.au
 */
namespace Payright\Payright\Helper;

use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository as MageOrderRepository;

class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    protected $_logger;
    protected $_payrightConfig;
    protected $_moduleList;
    protected $_client;
    protected $_registry;
    protected $jsonHelper;

   

    public $_payrightAcceessToken;
    public $_payrightRefreshToken;
    protected $_sandBoxApiEndpoint;
 
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Catalog\Model\Product $product,
        \Magento\Quote\Model\QuoteFactory $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface,
        \Magento\Quote\Api\CartManagementInterface $cartManagementInterface,
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Sales\Model\Order $order,
        \Payright\Payright\Model\Config\EnjoynowPaylater $payrightConfig,
        \Magento\Framework\Module\ModuleListInterface $moduleList,
        \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Session\SessionManagerInterface $session,
        \Magento\Framework\Message\ManagerInterface $messageManager


    ) {
        $this->scopeConfig = $scopeConfig;
        $this->_payrightConfig = $payrightConfig;
        if (($this->scopeConfig->getValue('payment/mypayright/sandbox')) == 0) {
            $this->_sandBoxApiEndpoint = $this->_payrightConfig->getProductionAPIUrl();
        } else {
            $this->_sandBoxApiEndpoint = $this->_payrightConfig->getSandboxAPIUrl();
        }

        $this->_storeManager = $storeManager;
        $this->_product = $product;
        $this->quote = $quote;
        $this->quoteManagement = $quoteManagement;
        $this->cartRepositoryInterface = $cartRepositoryInterface;
        $this->cartManagementInterface = $cartManagementInterface;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->order = $order;
 
        
        $this->_moduleList = $moduleList;
        $this->_client = $httpClientFactory;
        $this->_registry = $registry;
        $this->jsonHelper = $jsonHelper;

        
        $this->session = $session;
        $this->_messageManager = $messageManager;
        // $this->getSessionValue();

        parent::__construct($context);
    }


    public function DoApiCallPayright()
    {
        //API URL for authentication

        $apiURL = "oauth/token";



        $exsistingPayrightAccessToken = $this->_registry->registry('payrightAccessToken');
        $exsistingPayrightRefreshToken = $this->_registry->registry('payrightRefereshToken');
        $reponseArray = array();

        ### check if the auth token is in the session
        if (empty($this->_registry->registry('payrightAccessToken')) && empty($this->_registry->registry('payrightRefereshToken'))) {

       
           
            ####.
            $client = $this->_client->create();

            $client->setUri($this->_sandBoxApiEndpoint.$apiURL);



            $client->setConfig(['timeout' => 15]);
            $client->setHeaders(['Content-Type: application/json', 'Accept: application/json']);

            $params = array(
                "username" => $this->getConfigValue('user_name'),
                "password" => $this->getConfigValue('password'),
                "grant_type" => 'password',
                "client_id" => $this->getConfigValue('client_id'),
                "client_secret" => $this->getConfigValue('client_secret'),
            );



           
         
            $client->setParameterPost($params);
            $client->setMethod(\Zend_Http_Client::POST);



            try {
                $responseBody = $client->request()->getBody();
                $decodedData = $this->jsonHelper->jsonDecode($responseBody);

                if (array_key_exists('error', $decodedData)) {
                    return false;
                } else {
                    $this->_payrightAcceessToken = $decodedData['access_token'];
                    $this->_payrightRefreshToken = $decodedData['refresh_token'];

                    $reponseArray['payrightAccessToken'] = $this->_payrightAcceessToken;
                    $reponseArray['payrightRefreshToken'] = $this->_payrightRefreshToken;
                    $reponseArray['status'] = 'Authenticated';

                    return $reponseArray;
  

                    ## write the payright access tokenn and the refresh token to the mage registry
                }
            } catch (\Exception $e) {
                return "Error";
            }
        }
    }





    public function DoApiConfCallPayright($authToken)
    {
        $apiURL = "api/v1/configuration";

        $returnArray = array();
 

        $client = $this->_client->create();
        $client->setUri($this->_sandBoxApiEndpoint.$apiURL);
        $client->setConfig(['timeout' => 15]);
        $client->setHeaders(['Content-Type: application/json', 'Accept: application/json','Authorization:'.$authToken]);

        $params = array(
                "merchantusername" => $this->getConfigValue('merchantusername'),
                "merchantpassword" => $this->getConfigValue('merchantpassword'),
        );

        $client->setParameterPost($params);
        $client->setMethod(\Zend_Http_Client::POST);

        $responseBody = $client->request()->getBody();
            $decodedData = $this->jsonHelper->jsonDecode($responseBody);

        try {
            $responseBody = $client->request()->getBody();
            $decodedData = $this->jsonHelper->jsonDecode($responseBody);
            if (!isset($decodedData['code']) && isset($decodedData['data']['rates'])) {
                $returnArray['configToken'] = $decodedData['data']['configToken'];
                $returnArray['rates'] = $decodedData['data']['rates'];
                $returnArray['conf'] = $decodedData['data']['conf'];
                $returnArray['establishment_fee'] = $decodedData['data']['establishment_fee'];
                return $returnArray;
            } else {
                 return "Error";
            }
        } catch (\Exception $e) {
            return "Error";
        }
    }


    public function DoApiTransactionOverview($apiToken, $SugarAuthToken, $configToken, $amount)
    {
        $apiURL = "api/v1/transactionOverview";
        $returnArray = array();

        $client = $this->_client->create();
        $client->setUri($this->_sandBoxApiEndpoint.$apiURL);

    
        $client->setConfig(['timeout' => 15]);
        $client->setHeaders(['Content-Type: application/json', 'Accept: application/json','Authorization:'.$apiToken]);

        $paramsPayright = [
            'Token' => $SugarAuthToken,
            'ConfigToken' =>  $configToken,
            'saleamount' => $amount
        ];

   
        $client->setParameterPost($paramsPayright);
        $client->setMethod(\Zend_Http_Client::POST);

        try {
            $responseBody = $client->request()->getBody();
            $decodedData = $this->jsonHelper->jsonDecode($responseBody);
            $returnArray = $decodedData['data'];


            return $returnArray;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }



    public function DoApiIntializeTransaction($apiToken, $SugarAuthToken, $configToken, $transData, $ecommClientId, $merchantReference)
    {
        $apiURL = "api/v1/intialiseTransaction";
        $returnArray = array();


        $client = $this->_client->create();
        $client->setUri($this->_sandBoxApiEndpoint.$apiURL);

        $client->setConfig(['timeout' => 300]);
        $client->setHeaders(['Content-Type: application/json', 'Accept: application/json','Authorization:'.$apiToken]);
        $decodedTranscationdata = $this->jsonHelper->jsonDecode($transData);

       
        $paramsPayright = [
       'Token' => $SugarAuthToken,
       'ConfigToken' =>  $configToken,
       'transactiondata' => $transData,
       'totalAmount' => $decodedTranscationdata['transactionTotal'],
       'clientId' => $ecommClientId,
       'merchantReference' => $merchantReference
    ];



        $client->setParameterPost($paramsPayright);
        $client->setMethod(\Zend_Http_Client::POST);

        try {
            $responseBody = $client->request()->getBody();
            $decodedData = $this->jsonHelper->jsonDecode($responseBody);
            $returnArray = $decodedData;
            return $returnArray;
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    public function getConfigValue($field)
    {
        return $this->scopeConfig->getValue('payment/mypayright/'.$field, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }


    /**
    * Create Order On Your Store
    *
    * @param array $orderData
    * @return array
    *
    */

    public function createMageOrder($orderData)
    {

            //init the store id and website id @todo pass from array
        $store = $this->_storeManager->getStore();
        $websiteId = $this->_storeManager->getStore()->getWebsiteId();
                
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $customerSession = $objectManager->get('Magento\Customer\Model\Session');
        $customerRepository = $objectManager->get('Magento\Customer\Api\CustomerRepositoryInterface');


        $customerEmail = $customerSession->getCustomer()->getEmail();
        
        //init the customer
        $customer=$this->customerFactory->create();
        $customer->setWebsiteId($websiteId);
        $customer->loadByEmail($customerEmail);// load customet by email address

        //check the customer
        if (!$customer->getEntityId()) {
    
                //If not available then create this customer
            $customer->setWebsiteId($websiteId)
                    ->setStore($store)
                    ->setFirstname($orderData['shipping_address']['firstname'])
                    ->setLastname($orderData['shipping_address']['lastname'])
                    ->setEmail($orderData['email'])
                    ->setPassword($orderData['email']);
    
            $customer->save();
        }

        //init the quote
            $cartId = $this->cartManagementInterface->createEmptyCart(); //Create empty cart
            $quote = $this->cartRepositoryInterface->get($cartId); // load empty cart quote
            $quote->setStore($store);
        // if you have allready buyer id then you can load customer directly
        $customer= $this->customerRepository->getById($customer->getEntityId());
        $quote->setCurrency();
        $quote->assignCustomer($customer); //Assign quote to customer
     
        //add items in quote
        foreach ($orderData as $item) {
            $product=$this->_product->load($item['product_id']);
            $product->setPrice($item['price']);
            $quote->addProduct($product, intval($item['qty']));
        }
        
        $billingAddress  = $quote->getBillingAddress();
        $shippingAddress = (array) $quote->getShippingAddress();
            

        //Set Address to quote
        $quote->getBillingAddress()->addData($shippingAddress);
        $quote->getShippingAddress()->addData($shippingAddress);
     
        // Collect Rates and Set Shipping & Payment Method
     
        $shippingAddress=$quote->getShippingAddress();
        $shippingAddress->setCollectShippingRates(true)
                            ->collectShippingRates()
                            ->setShippingMethod('flatrate_flatrate'); //shipping method
            $quote->setPaymentMethod('mypayright'); //payment method
            $quote->setInventoryProcessed(false); //not effetc inventory
     
            // Set Sales Order Payment
        $quote->getPayment()->importData(['method' => 'mypayright']);
        $quote->save(); //Now Save quote and your quote is ready
     
        // Collect Totals
        $quote->collectTotals();
     
        // Create Order From Quote
        $quote = $this->cartRepositoryInterface->get($quote->getId());
        $orderId = $this->cartManagementInterface->placeOrder($quote->getId());
        $order = $this->order->load($orderId);
            
        $order->setEmailSent(0);
        $increment_id = $order->getRealOrderId();
            
        $orderState = \Magento\Sales\Model\Order::STATE_PROCESSING;
        $order->setState($orderState)->setStatus(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $order->save();


        if ($order->getEntityId()) {
            $result['order_id'] = $orderId;
        } else {
            $result = ['error'=>1,'msg'=>'Your custom message'];
        }

        return $result;
    }

    /**
    * Create a function for change the status of plan
    * @param integer PlanId
    * @return array return the plan array with change status
    */


    public function planStatusChange($planId)
    {
        $apiURL = "api/v1/changePlanStatus";


        $authToken = $this->DoApiCallPayright();
        $getPayRightAccessToken = $authToken['payrightAccessToken'];

        $getApiConfiguration = $this->DoApiTransactionConfCallPayright($getPayRightAccessToken);
        $sugarToken = $getApiConfiguration['auth']['auth-token'];
        $configToken = $getApiConfiguration['configToken'];
   
        
        $returnArray = array();

        $client = $this->_client->create();
        $client->setUri($this->_sandBoxApiEndpoint.$apiURL);


        $client->setConfig(['timeout' => 15]);
        $client->setHeaders(['Content-Type: application/json', 'Accept: application/json','Authorization:'.$getPayRightAccessToken]);

        $paramsPayright = [
            'Token' => $sugarToken,
            'ConfigToken' =>  $configToken,
            'id' => $planId,
            'status' => 'Cancelled'
            ];


        $client->setParameterPost($paramsPayright);
        $client->setMethod(\Zend_Http_Client::POST);

        try {
            $responseBody = $client->request()->getBody();
            $decodedData = $this->jsonHelper->jsonDecode($responseBody);
            $returnArray = $decodedData['data'];
        } catch (\Exception $e) {
            echo "Error";
            exit;
        }
    }



    public function getPlanDataByToken($ecommerceToken)
    {
        $apiURL = "api/v1/getEcomTokenData";
        $authToken = $this->DoApiCallPayright();
        $getPayRightAccessToken = $authToken['payrightAccessToken'];


        $returnArray = array();
        $client = $this->_client->create();

        $client->setUri($this->_sandBoxApiEndpoint.$apiURL);

        $client->setConfig(['timeout' => 15]);
        $client->setHeaders(['Content-Type: application/json', 'Accept: application/json','Authorization:'.$getPayRightAccessToken]);

        $paramsPayright = [
            'ecomToken' => $ecommerceToken
        ];


        $client->setParameterPost($paramsPayright);
        $client->setMethod(\Zend_Http_Client::POST);


        try {
            $responseBody = $client->request()->getBody();
            $decodedData = $this->jsonHelper->jsonDecode($responseBody);

            return $decodedData;
        } catch (\Exception $e) {
            echo "Error";
            exit;
        }
    }

   

    /**
     * Calculate the product installment
     * @param int $saleAmount amount of purchased product
     * @return calculated installment of sale amount
     */

    public function calculateSingleProductInstallment($saleAmount)
    {
        $authToken = $this->DoApiCallPayright();

        if ($authToken != 'Error') {
            $configValues = $this->DoApiConfCallPayright($authToken['payrightAccessToken']);

            $getRates = $configValues['rates'];

            if (isset($getRates)) {
                $payrightInstallmentApproval = $this->getMaximumSaleAmount($getRates, $saleAmount);

                if ($payrightInstallmentApproval == 0) {
                    $establishment_fee = $configValues['establishment_fee'];
                    $accountKeepingFees = $configValues['conf']['Monthly Account Keeping Fee'];
                    $paymentProcessingFee = $configValues['conf']['Payment Processing Fee'];
     
                    $LoanTerm = $this->fetchLoanTermForSale($getRates, $saleAmount);
                    $getMinDeposit = $this->calculateMinDeposit($getRates, $saleAmount, $LoanTerm);

                    $getFrequancy = $this->getPaymentFrequancy($accountKeepingFees, $LoanTerm);
                    $calculatedNoofRepayments = $getFrequancy['numberofRepayments'];
                    $calculatedAccountKeepingFees = $getFrequancy['accountKeepingFees'];


                    $LoanAmount = $saleAmount - $getMinDeposit;


                    $formatedLoanAmount = number_format((float)$LoanAmount, 2, '.', '');

                    $resEstablishmentFees = $this->getEstablishmentFees($LoanTerm, $establishment_fee);

                    $establishmentFeePerPayment = $resEstablishmentFees / $calculatedNoofRepayments;
                    $loanAmountPerPayment = $formatedLoanAmount / $calculatedNoofRepayments;

                    $CalculateRepayments  = $this->calculateRepayment(
                $calculatedNoofRepayments,
                $calculatedAccountKeepingFees,
                $resEstablishmentFees,
                $LoanAmount,
                $paymentProcessingFee
            );


                    $dataResponseArray['LoanAmount'] = $LoanAmount;
                    $dataResponseArray['EstablishmentFee'] = $resEstablishmentFees;
                    $dataResponseArray['minDeposit'] = $getMinDeposit;
                    $dataResponseArray['TotalCreditRequired'] = $this->TotalCreditRequired($formatedLoanAmount, $resEstablishmentFees);
                    $dataResponseArray['Accountkeepfees'] = $accountKeepingFees;
                    $dataResponseArray['processingfees'] = $paymentProcessingFee;
                    $dataResponseArray['saleAmount'] =  $saleAmount;
                    $dataResponseArray['noofrepayments'] = $calculatedNoofRepayments;
                    $dataResponseArray['repaymentfrequency'] = 'Fortnightly';
                    $dataResponseArray['LoanAmountPerPayment'] =  $CalculateRepayments;

                    return $dataResponseArray;
                } else {
                    return "exceed_amount";
                }
            } else {
                return "APIError";
            }
        } else {
               return "APIError";
        }
    }

    /**
     * Calculate Repayment installment
     * @param int $numberOfRepayments term for sale amount
     * @param int $AccountKeepingFees account keeping fees
     * @param int $establishmentFees establishment fees
     * @param int $LoanAmount loan amount
     * @param int $paymentProcessingFee processing fees for loan amount
     *
     */

    public function calculateRepayment($numberOfRepayments, $AccountKeepingFees, $establishmentFees, $LoanAmount, $paymentProcessingFee)
    {
        $RepaymentAmountInit = ((floatval($establishmentFees) + floatval($LoanAmount)) / $numberOfRepayments);
        $RepaymentAmount = floatval($RepaymentAmountInit) + floatval($AccountKeepingFees) + floatval($paymentProcessingFee);
        return number_format($RepaymentAmount, 2, '.', ',');
    }

    /**
     * Calculate Minimum deposit trhat needs to be pay for sale amount
     * @param array $getRates
     * @param int $saleAmount amount for purchased product
     * @return float mindeposit
     */


    public function calculateMinDeposit($getRates, $saleAmount, $loanTerm)
    {
        for ($i = 0; $i < count($getRates); $i++) {
            for ($l = 0; $l < count($getRates[$i]); $l++) {
                if ($getRates[$i][2] == $loanTerm) {
                    $per[] = $getRates[$i][1];
                }
            }
        }
         
        if (isset($per)) {
            $percentage = min($per);
            $value = $percentage/100*$saleAmount;
            return money_format('%.2n', $value);
        } else {
            return 0;
        }
    }

    /**
     * Payment frequancy for loan amount
     * @param float $accountKeepingFees account keeping fees
     * @param int $LoanTerm loan term
     * @param array $returnArray noofpayments and accountkeeping fees
     */

    public function getPaymentFrequancy($accountKeepingFees, $LoanTerm)
    {
        $RepaymentFrequecy = 'Fortnightly';
        
        if ($RepaymentFrequecy == 'Weekly') {
            $j = floor($LoanTerm * (52/12));
            $o = $accountKeepingFees * 12 / 52;
        }

        if ($RepaymentFrequecy == 'Fortnightly') {
            $j = floor($LoanTerm*(26/12));
            if ($LoanTerm == 3) {
                $j = 7;
            }
            $o = $accountKeepingFees * 12 / 26;
        }

        if ($RepaymentFrequecy == 'Monthly') {
            $j = parseInt(k);
            $o = $accountKeepingFees;
        }

        $numberofRepayments = $j;
        $accountKeepingFees = $o;

        $returnArray['numberofRepayments'] = $numberofRepayments;
        $returnArray['accountKeepingFees'] = round($accountKeepingFees, 2);

        return $returnArray;
    }

    /**
     * Get the loan term for sale amount
     * @param array $rates rates for merchant
     * @param float $saleAmount sale amount
     * @return float loanamount
     */

   
    public function fetchLoanTermForSale($rates, $saleAmount)
    {
        $ratesArray = array();
        //$generateLoanTerm = '';

     
        foreach ($rates as $key => $rate) {
            $ratesArray[$key]['Term'] = $rate['2'];
            $ratesArray[$key]['Min'] = $rate['3'];
            $ratesArray[$key]['Max'] = $rate['4'];
            
       

            if (($saleAmount >= $ratesArray[$key]['Min'] && $saleAmount <= $ratesArray[$key]['Max'])) {
                $generateLoanTerm[] = $ratesArray[$key]['Term'];
            }
        }

        if (isset($generateLoanTerm)) {
            return min($generateLoanTerm);
        } else {
            return 0;
        }
    }

    /**
     * Get the establishment fees
     * @param int $loanTerm loan term for sale amount
     * @return  calculated establishment fees
     */

    public function getEstablishmentFees($LoanTerm, $establishmentFees)
    {
        $fee_bandArray = array();
        $feebandCalculator = 0;

        foreach ($establishmentFees as $key => $row) {
            $fee_bandArray[$key]['term'] = $row['term'];
            $fee_bandArray[$key]['initial_est_fee'] = $row['initial_est_fee'];
            $fee_bandArray[$key]['repeat_est_fee'] = $row['repeat_est_fee'];

            if ($fee_bandArray[$key]['term'] == $LoanTerm) {
                $h = $row['initial_est_fee'];
            }

            $feebandCalculator++;
        }
        if (isset($h)) {
            return $h;
        } else {
            return 0;
        }
    }

    /**
     * Get the maximum limit for sale amount
     * @param array $getRates get the rates for merchant
     * @param float $saleAmount price of purchased amount
     * @return int allowed loanlimit in form 0 or 1, 0 means sale amount is still in limit and 1 is over limit
     */

    public function getMaximumSaleAmount($getRates, $saleAmount)
    {
        $chkLoanlimit = 0;
       
        $keys = array_keys($getRates);

        //print_r($keys);


        for ($i = 0; $i < count($getRates); $i++) {
            foreach ($getRates[$keys[$i]] as $key => $value) {
                if ($key == 4) {
                    $getVal[] = $value;
                }
            }
        }

        if (max($getVal) < $saleAmount) {
            $chkLoanlimit = 1;
        }

        return $chkLoanlimit;
    }

    /**
    * Get the total credit required
    * @param int $loanAmount lending amount
    * @param float $establishmentFees establishmentFees
    * @return float total credit allowed
    */


    public static function TotalCreditRequired($LoanAmount, $establishmentFees)
    {
        $totalCreditRequired = (floatval($LoanAmount) + floatval($establishmentFees)) ;
        return number_format((float)$totalCreditRequired, 2, '.', '');
    }

    /**
     * Insert the cancel order data in job queue
     * @param varchar $planName
     */

    public function setCancelProcessQueue($planName)
    {
        $apiURL = "api/v1/setcancelStatusQueue";



        $authToken = $this->DoApiCallPayright();
        $getPayRightAccessToken = $authToken['payrightAccessToken'];



        $getApiConfiguration = $this->DoApiTransactionConfCallPayright($getPayRightAccessToken);
        $sugarToken = $getApiConfiguration['auth']['auth-token'];
        
        $returnArray = array();
        $client = $this->_client->create();

        $client->setUri($this->_sandBoxApiEndpoint.$apiURL);

       

        $client->setConfig(['timeout' => 15]);
        $client->setHeaders(['Content-Type: application/json', 'Accept: application/json','Authorization:'.$getPayRightAccessToken]);

        $paramsPayright = [
            'authToken' => $sugarToken,
            'planName' => $planName,
            'clientId' => $this->getConfigValue('client_id')
            ];
       

        $client->setParameterPost($paramsPayright);
        $client->setMethod(\Zend_Http_Client::POST);

        try {
            $responseBody = $client->request()->getBody();
            //$decodedData = $this->jsonHelper->jsonDecode($responseBody);
        } catch (\Exception $e) {
            echo "Error";
            exit;
        }
    }


    public function DoSendInitialTranscation($userId)
    {
        $apiURL = "api/v1/setinitialTransJob";

        $authToken = $this->DoApiCallPayright();
        $getPayRightAccessToken = $authToken['payrightAccessToken'];

        $returnArray = array();
        $client = $this->_client->create();

        $client->setUri($this->_sandBoxApiEndpoint.$apiURL);


        $client->setConfig(['timeout' => 15]);
        $client->setHeaders(['Content-Type: application/json', 'Accept: application/json','Authorization:'.$getPayRightAccessToken]);

        $paramsPayright = [
            'userId' => $userId
            ];

        $client->setParameterPost($paramsPayright);
        $client->setMethod(\Zend_Http_Client::POST);

        try {
            $responseBody = $client->request()->getBody();
            //$decodedData = $this->jsonHelper->jsonDecode($responseBody);
        } catch (\Exception $e) {
            echo "Error";
            exit;
        }
    }

    public function DoApiTransactionConfCallPayright($authToken)
    {
        $apiURL = "api/v1/initialTransactionConfiguration";

        $returnArray = array();

        $client = $this->_client->create();
        $client->setUri($this->_sandBoxApiEndpoint.$apiURL);
        $client->setConfig(['timeout' => 15]);
        $client->setHeaders(['Content-Type: application/json', 'Accept: application/json','Authorization:'.$authToken]);

        $params = array(
                "merchantusername" => $this->getConfigValue('merchantusername'),
                "merchantpassword" => $this->getConfigValue('merchantpassword'),
        );

        $client->setParameterPost($params);
        $client->setMethod(\Zend_Http_Client::POST);
        

        try {
            $responseBody = $client->request()->getBody();
            $decodedData = $this->jsonHelper->jsonDecode($responseBody);
            if (!isset($decodedData['code']) && isset($decodedData['data']['auth'])) {
                $returnArray['auth'] = $decodedData['data']['auth'];
                $returnArray['configToken'] = $decodedData['data']['configToken'];
                $returnArray['rates'] = $decodedData['data']['rates'];
                $returnArray['conf'] = $decodedData['data']['conf'];
                $returnArray['establishment_fee'] = $decodedData['data']['establishment_fee'];
                return $returnArray;
            } else {
                return "Error";
            }
        } catch (\Exception $e) {
            return "Error";
        }
    }
}
