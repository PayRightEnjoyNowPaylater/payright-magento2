<?php
/**
 * Magento 2 extensions for PayRight Payment
 *
 * @author PayRight
 * @copyright 2016-2018 PayRight https://www.payright.com.au
 */
namespace Payright\Payright\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 * @package PayRight\PayRight\Model
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var Config\Payovertime
     */
    protected $payrightConfig;

    /**
     * ConfigProvider constructor.
     * @param Config\Payovertime $config
     */
    public function __construct(\Payright\Payright\Model\Config\EnjoynowPaylater $config)
    {
        $this->payrightConfig = $config;
    }

    /**
     * Get config set on JS global variable window.checkoutConfig
     *
     * @return array
     */
    public function getConfig()
    {
        // set default array
        $config = [];

        /**
         * adding config array
         */
        // $config = array_merge_recursive($config, [
        //     'payment' => [
        //         'afterpay' => [
        //             'afterpayJs'        => $this->afterpayConfig->getWebUrl('afterpay.js'),
        //             'afterpayReturnUrl' => 'afterpay/payment/response',
        //             'redirectMode'      => $this->afterpayConfig->getCheckoutMode(),
        //             'paymentAction'     => $this->afterpayConfig->getPaymentAction(),
        //             'termsConditionUrl' => self::TERMS_CONDITION_LINK,
        //             'currencyCode'     => $this->afterpayConfig->getCurrencyCode(),
        //         ],
        //     ],
        // ]);

        return $config;
    }
}