<?php

declare(strict_types = 1);

namespace Alcoline\Core\Contracts;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag(self::CAN_PING_TAG)]
interface ICanPing
{
    public const string CAN_PING_TAG = 'i.can.ping';
    public function ping(): string;
}