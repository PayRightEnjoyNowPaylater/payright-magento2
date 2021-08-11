<?php

namespace Payright\Payright\Plugin;

use Payright\Payright\Helper\Data as Helper;
use Payright\Payright\Model\Config\Payright as PayrightConfig;

/**
 * Class Minicartrepayment
 *
 * @package Payright\Payright\Plugin
 */
class Minicartrepayment {

    protected $payrightHelper;
    protected $scopeConfig;

    /**
     * Minicartrepayment constructor.
     *
     * @param  \Payright\Payright\Helper\Data  $payrightHelper
     * @param  \Payright\Payright\Model\Config\Payright  $payrightConfig
     * @param  \Magento\Framework\App\Config\ScopeConfigInterface  $scopeConfig
     */
    public function __construct(
        Helper $payrightHelper,
        PayrightConfig $payrightConfig,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->payrightHelper = $payrightHelper;
        $this->payrightConfig = $payrightConfig;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get section data.
     *
     * @param  \Magento\Checkout\CustomerData\Cart  $subject
     * @param  array  $result
     * @return array
     */
    public function afterGetSectionData(\Magento\Checkout\CustomerData\Cart $subject, array $result) {

        // $cart = $this->getConfigValue('minicart');
        //echo $cartAmount."--";
        // 
        //$result['extra_data'] = $result['subtotalAmount'];
        if ($result['subtotalAmount'] > 0 && isset($result['subtotalAmount'])) {
            $cartAmount = number_format((float)$result['subtotalAmount'], 2, '.', '');
            $result['extra_data'] = $result['subtotalAmount'];
            $transactionOverview = $this->payrightHelper->calculateSingleProductInstallment($cartAmount);

            if (($transactionOverview != 'exceed_amount' && $transactionOverview != 'APIError') && ($this->getConfigValue('minicart') == "1")) {
                $InstallTxtBuild = $transactionOverview['numberOfRepayments'] . " " . $transactionOverview['repaymentFrequency'] . " payments of $ " . $transactionOverview['loanAmountPerPayment'];
                $result['extra_data'] = $InstallTxtBuild . '<span class="payright-logo" ><img ></span>';
            } else {
                $result['extra_data'] = '';
                return $result;
            }

        } else {
            $result['extra_data'] = '';
        }

        return $result;
    }

    /**
     * Get configuration field, by global use.
     *
     * @param $field
     * @return mixed
     */
    public function getConfigValue($field) {
        return $this->scopeConfig->getValue('payment/payright/' . $field);
    }
}

?>