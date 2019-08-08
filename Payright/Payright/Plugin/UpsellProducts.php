<?php
namespace Payright\Payright\Plugin;

use Payright\Payright\Helper\Data as Helper;
use Payright\Payright\Model\Config\EnjoynowPaylater as PayrightConfig;

// ini_set("display_errors", "0");
class UpsellProducts
{
    protected $product;
    protected $payrightConfig;
    protected $payrightMain;
    protected $payrightHelper;
    protected $session;

    public function __construct(
        PayrightConfig $payrightConfig,
        Helper $payrightHelper,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Session\SessionManagerInterface $session
    ) {
        $this->payrightConfig = $payrightConfig;
        $this->payrightHelper = $payrightHelper;
        $this->scopeConfig    = $scopeConfig;
        $this->session        = $session;
    }

    public function aftergetProductPrice(
        \Magento\Catalog\Block\Product\ProductList\Upsell $subject,
        $result,
        \Magento\Catalog\Model\Product $product
    ) {
        $finalPrice  = $product->getFinalPrice();
        $productType = $product->getTypeID();

        if ($productType != 'bundle' && $productType != 'grouped' && $this->getConfigValue('upsellinstalments') == "1") {
            $this->session->start();
            $instalments = $this->payrightHelper->calculateSingleProductInstallment($finalPrice);

            if ($instalments != 'exceed_amount' && $instalments != 'APIError') {
                $prinstalments = "<div class='installments' style='padding: 10px;
         margin-bottom: 10px;'>or <strong>" . $instalments['noofrepayments'] . "</strong>" . " Fortnightly " . "payments of $" . "<strong>" . $instalments['LoanAmountPerPayment'] . "</strong> with<span class='payright-logo'><img ></span></div>";

                return $result . $prinstalments;
            } else {
                if (($this->scopeConfig->getValue('payment/mypayright/sandbox')) == 1) {
                    $resultString = "<div class='installments' style='padding: 2px; margin-bottom: 10px;'>There is some problem with API!!</div>";
                    return $result.$resultString;
                } else {
                    return $result;
                }
            }
        } else {
            return $result;
        }
    }

    public function getConfigValue($field)
    {
        return $this->scopeConfig->getValue('payment/mypayright/' . $field);
    }
}
