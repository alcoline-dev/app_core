<?php

declare(strict_types = 1);

namespace Alcoline\Core\Contracts;

interface IUserSdk extends ICanPing
{

    public function getOTP(string $phone, string $appName): string;

    public function login(string $phone, string $otp, string $appName): object;

    public function refresh(string $refreshToken): object;

    public function me(string $accessToken): object;
}