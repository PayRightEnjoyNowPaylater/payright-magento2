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

class Data extends \Magento\Framework\App\Helper\AbstractHelper {
    protected $_logger;
    protected $_payrightConfig;
    protected $_moduleList;
    protected $_client;
    protected $_registry;
    protected $jsonHelper;
    protected $_apiEndpoint;
    public $_accessToken;

    /**
     * Data constructor.
     *
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Catalog\Model\Product $product
     * @param \Magento\Quote\Model\QuoteFactory $quote
     * @param \Magento\Quote\Model\QuoteManagement $quoteManagement
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepositoryInterface
     * @param \Magento\Quote\Api\CartManagementInterface $cartManagementInterface
     * @param \Magento\Customer\Model\CustomerFactory $customerFactory
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     * @param \Magento\Sales\Model\Order $order
     * @param \Payright\Payright\Model\Config\Payright $payrightConfig
     * @param \Magento\Framework\Module\ModuleListInterface $moduleList
     * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Session\SessionManagerInterface $session
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
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
        \Payright\Payright\Model\Config\Payright $payrightConfig,
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

        // Toggle sandbox
        if (($this->scopeConfig->getValue('payment/payright/sandbox')) == 0) {
            // Specify AU or NZ region - for Production
            if (($this->scopeConfig->getValue('payment/payright/region')) == 'AU') {
                $this->_apiEndpoint = $this->_payrightConfig->getProductionAPIUrl();
            } else {
                $this->_apiEndpoint = $this->_payrightConfig->getProductionAPIUrlNz();
            }
        } else {
            // Specify AU or NZ region - for Sandbox
            if (($this->scopeConfig->getValue('payment/payright/region')) == 'AU') {
                $this->_apiEndpoint = $this->_payrightConfig->getSandboxAPIUrl();
            } else {
                $this->_apiEndpoint = $this->_payrightConfig->getSandboxAPIUrlNz();
            }
        }

        $this->_accessToken = $this->_payrightConfig->getAccessToken();

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

    /**
     * Get configuration field, by store.
     *
     * @param $field
     * @return mixed
     */
    public function getConfigValue($field) {
        return $this->scopeConfig->getValue('payment/payright/' . $field, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
    }

    /**
     * Get customer payment plan from new checkout, by 'checkoutId'.
     * Only used for 'responseAction' function in PaymentController.
     *
     * @param $merchantReference
     * @param $saleAmount
     * @param $redirectUrl
     * @param $expiresAt
     * @return array
     */
    public function performApiCheckout($merchantReference, $saleAmount, $redirectUrl, $expiresAt) {
        // Prepare json raw data payload
        $data = array(
            'merchantReference' => $merchantReference,
            'saleAmount' => $saleAmount,
            'type' => 'standard',
            'redirectUrl' => $redirectUrl,
            'expiresAt' => $expiresAt
        );

        // Define API POST call, to perform checkout
        $client = new \Zend_Http_Client($this->_apiEndpoint . "api/v1/checkouts");
        $client->setHeaders(
            array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->_accessToken,
            )
        );
        $client->setConfig(['timeout' => 15]);

        // Lastly, define POST method, with json body data sent
        $response = $client->setRawData(json_encode($data), 'application/json')->request('POST');

        return json_decode($response->getBody(), true);
    }

    /**
     * Get customer payment plan from new checkout, by 'checkoutId'.
     * Only used for 'responseAction'.
     *
     * @param $checkoutId
     * @return Exception
     */
    public function getPlanDataByCheckoutId($checkoutId) {
        $id = $checkoutId;

        try {
            // Define API GET call, to get Plan with Checkout ID
            $client = new \Zend_Http_Client($this->_apiEndpoint . "api/v1/checkouts/" . $id);
            $client->setHeaders(
                array(
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                )
            );
            $client->setConfig(['timeout' => 15]);
            $client->setMethod(\Zend_Http_Client::GET);

            return json_decode($client->request()->getBody(), true);
        } catch (\Exception $e) {
            return json_decode($client->request()->getBody(), true);
        }
    }

    /**
     * Activate Payright payment plan. Please note, this is a PUT request but we need POST for Zend_Http_Client.
     *
     * @param $checkoutId
     * @return array
     */
    public function activatePlan($checkoutId) {
        // Capture 'checkoutId' from parameter
        $cId = $checkoutId;

        // Define API PUT call, to create new checkout
        $client = new \Zend_Http_Client($this->_apiEndpoint . "api/v1/checkouts/" . $cId . "/activate");
        $client->setHeaders(
            array(
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->_accessToken,
            )
        );
        $client->setConfig(array('timeout' => 15));
        $client->setMethod(\Zend_Http_Client::PUT);

        $response = json_decode($client->request()->getBody(), true);

        // Response is data->message = 'Checkout activated'
        // else data->error and data->message
        try {
            return $response;
        } catch (\Exception $e) {
            return $response;
        }
    }

    /**
     * Retrieve data of 'rates', 'establishmentFees' and 'otherFees'.
     *
     * @return array
     */
    public function performApiGetRates() {
        $response = [];

        try {
            $client = new \Zend_Http_Client($this->_apiEndpoint . "api/v1/merchant/configuration");
            $client->setConfig(['timeout' => 15]);
            $client->setHeaders([
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $this->_accessToken,
            ]);

            $client->setMethod(\Zend_Http_Client::GET);

            $response = json_decode($client->request()->getBody(), true);

            // Define an empty array, to store breakdown of the 'data'
            $returnArray[] = null;

            if (!isset($response['error']) && isset($response['data']['rates'])) {
                $returnArray['rates'] = $response['data']['rates'];
                $returnArray['establishmentFees'] = $response['data']['establishmentFees'];
                $returnArray['otherFees'] = $response['data']['otherFees'];

                return $returnArray;
            } else {
                // Return error response, 'code' and 'message' will be received
                throw new \Exception();
            }
        } catch (\Exception $e) {
            return $response['error'];
        }
    }


    /**
     * Calculate product installments, for current product.
     * The 'Block/Catalog/Installments.php' performs 'renderInstallments()'.
     *
     * @param $saleAmount
     * @return string
     */
    public function calculateSingleProductInstallment($saleAmount) {
        // Get 'Access Token' from system configuration
        $authToken = $this->_accessToken;

        // Get 'data' = 'rates', 'establishmentFees' and 'otherFees'
        $data = $this->performApiGetRates();

        // Breakdown fields of 'data'
        $rates = $data['rates'];
        $establishmentFees = $data['establishmentFees'];

        // We need the mentioned 'otherFees' below, to calculate for 'payment frequency'
        // and 'loan amount per repayment'
        $accountKeepingFee = $data['otherFees']['monthlyAccountKeepingFee'];
        $paymentProcessingFee = $data['otherFees']['paymentProcessingFee'];

        // Check if the sale amount falls within the rate card, and determine lowest term and deposit
        $minimumDepositAndTerm = $this->getMinimumDepositAndTerm($rates, $saleAmount);

        // Breakdown fields of 'minimumDepositAndTerm'
        $depositAmount = $minimumDepositAndTerm['minimumDepositAmount']; // minimum deposit amount = deposit amount
        $term = $minimumDepositAndTerm['minimumDepositTerm']; // loan term = term
        $loanAmount = $saleAmount - $depositAmount; // 'minimum deposit amount' = 'deposit amount'.

        // Begin 'Catch Error Types'
        if (!isset($rates)) {
            return "rates_error";
        }

        if (empty($minimumDepositAndTerm)) {
            return "exceed_amount";
        }

        if (!isset($authToken)) {
            return "auth_token_error";
        }
        // End 'Catch Error Types'

        // Get your 'payment frequency', from 'monthly account keeping fee' and 'loan term'
        $getPaymentFrequency = $this->getPaymentFrequency($accountKeepingFee, $term);

        // Calculate and collect all 'number of repayments' and 'monthly account keeping fees'
        $calculatedNumberOfRepayments = $getPaymentFrequency['numberOfRepayments'];
        $calculatedAccountKeepingFees = $getPaymentFrequency['accountKeepingFees'];

        // For 'total credit required' output. Format the 'loan amount', into currency format.
        $formattedLoanAmount = number_format((float)$loanAmount, 2, '.', '');

        // Process 'establishment fees', from 'loan term' and 'establishment fees' (response)
        $resEstablishmentFees = $this->getEstablishmentFees($term, $establishmentFees);

        // Calculate repayment, to get 'loan amount' as 'loan amount per payment'.
        $calculateRepayments = $this->calculateRepayment(
            $calculatedNumberOfRepayments,
            $calculatedAccountKeepingFees,
            $resEstablishmentFees,
            $loanAmount,
            $paymentProcessingFee);

        // The entire breakdown for calculated single product 'installment'.
        $dataResponseArray['loanAmount'] = $loanAmount;
        $dataResponseArray['establishmentFee'] = $resEstablishmentFees;
        $dataResponseArray['minDeposit'] = $depositAmount;
        $dataResponseArray['totalCreditRequired'] = $this->totalCreditRequired($formattedLoanAmount, $resEstablishmentFees);
        $dataResponseArray['accountKeepingFee'] = $accountKeepingFee;
        $dataResponseArray['paymentProcessingFee'] = $paymentProcessingFee;
        $dataResponseArray['saleAmount'] = $saleAmount;
        $dataResponseArray['numberOfRepayments'] = $calculatedNumberOfRepayments;
        $dataResponseArray['repaymentFrequency'] = 'Fortnightly';
        $dataResponseArray['loanAmountPerPayment'] = $calculateRepayments;

        return $dataResponseArray;
    }

    /**
     * Calculate repayment installment
     *
     * @param int $numberOfRepayments term for sale amount
     * @param int $accountKeepingFee account keeping fees
     * @param int $establishmentFees establishment fees
     * @param int $loanAmount loan amount
     * @param int $paymentProcessingFee processing fees for loan amount
     * @return string number format amount
     */
    public function calculateRepayment(
        $numberOfRepayments,
        $accountKeepingFee,
        $establishmentFees,
        $loanAmount,
        $paymentProcessingFee) {
        $repaymentAmountInit = ((floatval($establishmentFees) + floatval($loanAmount)) / $numberOfRepayments);
        $repaymentAmount = floatval($repaymentAmountInit) + floatval($accountKeepingFee) + floatval($paymentProcessingFee);

        return number_format($repaymentAmount, 2, '.', ',');
    }

    /**
     * Calculate Minimum deposit that needs to be pay for sale amount
     *
     * @param array $getRates
     * @param int $saleAmount amount for purchased product
     * @param $loanTerm
     * @return float
     */
    public function calculateMinDeposit($getRates, $saleAmount, $loanTerm) {
        for ($i = 0; $i < count($getRates); $i++) {
            for ($l = 0; $l < count($getRates[$i]); $l++) {
                if ($getRates[$i][2] == $loanTerm) {
                    $per[] = $getRates[$i][1];
                }
            }
        }

        if (isset($per)) {
            $percentage = min($per);
            $value = $percentage / 100 * $saleAmount;
            return money_format('%.2n', $value);
        } else {
            return 0;
        }
    }

    /*
     * Get minimum deposit amount + percentage + term (loan term)
     *
     */
    function getMinimumDepositAndTerm($rates, $saleAmount) {
        // Iterate through each term, apply the minimum deposit to the sale amount and see if it fits in the rate card. If not found, move to a higher term
        foreach ($rates as $rate) {
            $minimumDepositPercentage = $rate['minimumDepositPercentage'];
            $depositAmount = $saleAmount * ($minimumDepositPercentage / 100);
            $loanAmount = $saleAmount - $depositAmount;

            // Check if loan amount is within range
            if ($loanAmount >= $rate['minimumPurchase'] && $loanAmount <= $rate['maximumPurchase']) {
                return [
                    'minimumDepositPercentage' => $minimumDepositPercentage,
                    // If above PHP 7.4 check, source: https://www.php.net/manual/en/function.money-format.php
                    'minimumDepositAmount' => function_exists('money_format') ? money_format('%.2n', $depositAmount) : sprintf('%01.2f', $depositAmount),
                    'minimumDepositTerm' => $rate['term'],
                ];
            }
        }
        // No valid term and deposit found
        return [];
    }

    /**
     * Get payment frequency for loan amount.
     *
     * @param float $accountKeepingFee account keeping fees
     * @param int $loanTerm loan term
     * @return mixed
     */
    public function getPaymentFrequency($accountKeepingFee, $loanTerm) {
        $repaymentFrequency = 'Fortnightly';

        if ($repaymentFrequency == 'Weekly') {
            $j = floor($loanTerm * (52 / 12));
            $o = $accountKeepingFee * 12 / 52;
        }

        if ($repaymentFrequency == 'Fortnightly') {
            $j = floor($loanTerm * (26 / 12));
            if ($loanTerm == 3) {
                $j = 7;
            }
            $o = $accountKeepingFee * 12 / 26;
        }

        if ($repaymentFrequency == 'Monthly') {
            $j = $loanTerm;
            $o = $accountKeepingFee;
        }

        $numberOfRepayments = $j;
        $accountKeepingFee = $o;

        $returnArray['numberOfRepayments'] = $numberOfRepayments;
        $returnArray['accountKeepingFees'] = round($accountKeepingFee, 2);

        return $returnArray;
    }

    /**
     * Get the establishment fees
     *
     * @param int $loanTerm loan term for sale amount
     * @param $establishmentFees
     * @return string $h establishment fees
     */
    public
    function getEstablishmentFees($loanTerm, $establishmentFees) {
        foreach ($establishmentFees as $key => $estFee) {
            if ($estFee['term'] == $loanTerm) {
                $initialEstFee = $estFee['initialEstFee'];
            }
        }

        if (isset($initialEstFee)) {
            return $initialEstFee;
        } else {
            return 0;
        }
    }

    /**
     * Get the total credit required.
     *
     * @param int $loanAmount lending amount
     * @param float $establishmentFees establishmentFees
     * @return float total credit allowed
     */
    public
    static function totalCreditRequired($loanAmount, $establishmentFees) {
        $totalCreditRequired = (floatval($loanAmount) + floatval($establishmentFees));

        return number_format((float)$totalCreditRequired, 2, '.', '');
    }
}
