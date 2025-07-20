<?php

namespace Alcoline\Core\Contracts;

interface IAsyncMessengerSDK extends ICanPing
{
    public const string DEFAULT_CHANEL = 'telegram';

    public function send(
        string $message,
        ?string $subject = null,
        ?string $contactId = null,
        array $contactIds = [],
        array $contactsByTags = [],
        ?string $channel = null,
        bool $strict = false,
        ?string $sender = null
    ): true;
}