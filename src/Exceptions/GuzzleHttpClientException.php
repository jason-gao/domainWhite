<?php
/**
 * Desc: HttpClientException
 * Created by PhpStorm.
 * User: jasong
 * Date: 2018/09/28 14:10.
 */

namespace DomainWhiteSdk\Exceptions;

use \Exception;

class GuzzleHttpClientException extends Exception
{
    const MSG_BODY  = 'guzzle body must be string';
    const CODE_BODY = -1000;
}
