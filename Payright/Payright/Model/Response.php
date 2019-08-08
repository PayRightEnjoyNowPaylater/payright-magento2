<?php
/**
 * Magento 2 extensions for Afterpay Payment
 *
 * @author Afterpay
 * @copyright 2016-2018 Afterpay https://www.payright.com.au
 */
namespace Payright\Payright\Model;

/**
 * Class Response
 * @package Afterpay\Afterpay\Model
 */
class Response
{

	const RESPONSE_STATUS_SUCCESS   = 'APPROVED';
    const RESPONSE_STATUS_CANCELLED = 'CANCELLED';
    const RESPONSE_STATUS_FAILURE   = 'FAILURE';
    const RESPONSE_STATUS_REVIEW   =  'REVIEW';

    /* Order payment statuses */
    const RESPONSE_STATUS_APPROVED = 'APPROVED';
    const RESPONSE_STATUS_PENDING  = 'PENDING';
    const RESPONSE_STATUS_FAILED   = 'FAILED';
    const RESPONSE_STATUS_DECLINED = 'DECLINED';

    const RESPONSE_APPROVED_PENDINGID = 'APPROVED_PENDING_ID';





}