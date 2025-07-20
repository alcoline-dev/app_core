<?php

namespace Alcoline\Core\Contracts;

interface IMessengerSDK extends ICanPing
{
    public const string DEFAULT_CHANEL = 'telegram';

    /**
     * @return object[]
     */
    public function send(
        string $message,
        ?string $subject = null,
        ?string $contactId = null,
        array $contactIds = [],
        array $contactsByTags = [],
        ?string $channel = null,
        bool $strict = false,
        ?string $sender = null
    ): array;
}