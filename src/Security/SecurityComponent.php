<?php

declare(strict_types = 1);

namespace Alcoline\Core\Security;

use InvalidArgumentException;
use Alcoline\Core\Contracts\ISecuritySDK;

class SecurityComponent
{
    public function __construct(
        protected ISecuritySDK $securitySDK,
    ) {}

    /**
     * @throws InvalidArgumentException
     */
    public function can(string $agent, array $context = [], ?string $permission = null): bool
    {
        $permission = $permission ?? $this->getPermission();
        $this->validatePermission($permission);

        return $this->securitySDK->can(
            $permission,
            $agent,
            $context
        );
    }

    protected function getPermission(): string
    {
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);
        $caller = $backtrace[2] ?? [];

        $fullClass = $caller['class'] ?? 'unknown_domain';
        $method = $caller['function'] ?? 'unknown_action';

        $class = defined("$fullClass::ALIAS") ? constant("$fullClass::ALIAS") : $this->getDomain($fullClass);

        return $class . '.' . $method;
    }

    protected function getDomain(string $fullClass): string
    {
        $pos = strrchr($fullClass, '\\');
        return $pos ? ltrim($pos, '\\') : $fullClass;
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function validatePermission(string $permission): true
    {
        if (!preg_match('/^[a-zA-Z0-9_]+\\.[a-zA-Z0-9_]+$/', $permission)) {
            throw new InvalidArgumentException(sprintf(
                'Permission "%s" must follow the pattern "domain.action" (only one dot, no empty parts).',
                $permission
            ));
        }

        return true;
    }
}