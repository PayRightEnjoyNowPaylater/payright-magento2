<?php

namespace Payright\Payright\Model;

/**
 * Class Response
 *
 * @package Payright\Payright\Model
 */
class Response {

    const RESPONSE_STATUS_SUCCESS = 'COMPLETE';
    const RESPONSE_STATUS_CANCELLED = 'CANCELLED';
    const RESPONSE_STATUS_FAILURE = 'FAILURE';
    const RESPONSE_STATUS_REVIEW = 'REVIEW';

    /* Order payment statuses */
    const RESPONSE_STATUS_APPROVED = 'APPROVED';
    const RESPONSE_STATUS_PENDING = 'PENDING';
    const RESPONSE_STATUS_FAILED = 'FAILED';
    const RESPONSE_STATUS_DECLINED = 'DECLINED';

    const RESPONSE_APPROVED_PENDING_ID = 'APPROVED_PENDING_ID';
}