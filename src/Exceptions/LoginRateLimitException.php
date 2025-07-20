<?php

declare(strict_types=1);

namespace Alcoline\Core\Exceptions;

class LoginRateLimitException extends \DomainException
{
    protected $message = 'Too many login attempts. Please try again later.';
    protected $code = -32400;
}