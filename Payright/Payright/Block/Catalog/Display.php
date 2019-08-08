<?php
namespace Payright\Payright\Block\Catalog;
use Magento\Framework\View\Element\Template;
use Magento\Catalog\Model\Product as Product;
use Magento\Framework\Registry as Registry;
use Magento\Directory\Model\Currency as Currency;
use Magento\Framework\Component\ComponentRegistrar as ComponentRegistrar;
use Payright\Payright\Model\Config\EnjoynowPaylater as PayrightConfig;
use Payright\Payright\Helper\Data as Helper;
// ini_set("display_errors", "0");


class Display extends \Magento\Framework\View\Element\Template
{

	protected $product;
  protected $payrightConfig;
  protected $payrightMain;
  protected $payrightHelper;
  protected $registry;
  protected $_catalogSession;
  protected $session;
  protected $_canCallTransactionOverview = false;


	public function __construct(\Magento\Framework\View\Element\Template\Context $context,
	Product $product,
    PayrightConfig $payrightConfig,
    Helper $payrightHelper,
    \Magento\Catalog\Model\Session $catalogSession,
    \Magento\Framework\Session\SessionManagerInterface $session,
    Registry $registry,
     \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
     \Magento\Catalog\Model\Product\TierPriceManagement $tierPriceManagement

	)
	{
        $this->registry = $registry;
        $this->_catalogSession = $catalogSession;
		    $this->product = $product;
        $this->payrightConfig = $payrightConfig;
        $this->payrightHelper = $payrightHelper;
        $this->session = $session;
        $this->scopeConfig = $scopeConfig;
        $this->tierPriceManagement = $tierPriceManagement;
		    parent::__construct($context);
	}


	public function getInstallmentsAmount()
    { 
      if(  $this->payrightConfig->isProductInstallmentsShow() == "1"){ 
    
       $product = $this->registry->registry('product');

       $this->session->start();

        $productId = $product->getId();
        $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $product = $objectManager->get('Magento\Catalog\Model\Product')->load($productId);
        $productType = $product->getTypeID();


     

        //$transactionOverview =  $this->payrightHelper->calculateSingleProductInstallment($product->getFinalPrice());
          //return $transactionOverview;


        if ($productType != 'bundle' && $productType != 'grouped' )  {
          $transactionOverview =  $this->payrightHelper->calculateSingleProductInstallment($product->getFinalPrice());
          return $transactionOverview;
        } else {
          return 'exceed_amount';
        }
      }
       
    }

    public function IsProductPageInstallmentTextActive()
    {
    	$isShow = $this->payrightConfig->isProductInstallmentsShow();
    	return $isShow;
    }

    public function getProductInstallmentText()
    {
    	echo "test";
    	//$this->payrightMain->ApiGetInstallmentText();
    }

    public function getConfigMerchantName()
    {
    	$merchantName = $this->payrightConfig->getMerchantName();
    	return $merchantName;
    }

    public function getmodal(){
      
      $modal = $this->payrightConfig->getoption();
      return $modal;
    }
}