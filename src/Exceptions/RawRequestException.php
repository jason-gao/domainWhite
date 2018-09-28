<?php
/**
 * Desc:
 * Created by PhpStorm.
 * User: jasong
 * Date: 2018/09/28 14:10.
 */

namespace DomainWhiteSdk\Exceptions;

use \Exception;

class RawRequestException extends Exception
{
    const MSG_API_URL  = 'must set base api url first';
    const CODE_API_URL = -1000;
}
