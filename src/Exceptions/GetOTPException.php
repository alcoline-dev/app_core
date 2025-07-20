<?php

declare(strict_types=1);

namespace Alcoline\Core\Exceptions;

class GetOTPException extends \DomainException
{
    protected $message = 'Get OTP code error';
    protected $code = -32401;
}