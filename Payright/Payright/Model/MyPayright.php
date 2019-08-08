<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Payright\Payright\Model;



/**
 * Pay In Store payment method model
 */
class MyPayright extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'mypayright';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = true;

    const METHOD_CODE = 'payrightpaypayovertime';


  

}
