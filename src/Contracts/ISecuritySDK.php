<?php

namespace Alcoline\Core\Contracts;

interface ISecuritySDK extends ICanPing
{
    public function can(string $permission, string $agent, array $context = []): bool;
}