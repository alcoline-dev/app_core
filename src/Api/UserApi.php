<?php

declare(strict_types=1);

namespace Alcoline\Core\Api;

use Alcoline\Core\Api\DTO\UserMeInfoView;
use Alcoline\Core\Contracts\IAsyncMessengerSDK;
use Alcoline\Core\Contracts\IMessengerSDK;
use Alcoline\Core\Contracts\IUserSdk;
use Alcoline\Core\Exceptions\GetOTPException;
use Alcoline\Core\Security\Service\LoginLimiter;
use Alcoline\Core\Security\Service\UserContext;
use Symfony\Component\Validator\Constraints as Assert;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Ufo\DTO\DTOTransformer;
use Ufo\RpcError\RpcInvalidTokenException;
use Ufo\RpcObject\RPC;

class UserApi
{
    public const string OTP_SUBJECT = 'ОТР пароль';
    public const string TWIG_TEMPLATE = 'otp_message.html.twig';
    public const string SYSTEM_SENDER = 'system';

    public function __construct(
        protected IMessengerSDK $messengerSdkService,
        protected IAsyncMessengerSdk $messengerAsyncSdkService,
        protected IUserSdk $userSdkService,
        protected UserContext $userContext,
        protected Environment $twig,
        protected LoginLimiter $loginLimiter,
        protected string $otpTemplate = self::TWIG_TEMPLATE,
        protected string $otpSubject = self::OTP_SUBJECT,
        protected string $sender = self::SYSTEM_SENDER,
    ) {}

    /**
     * Отримання otp кода, та відправка його через мессенджер
     *
     * @param string $phone Номер телефону користувача
     * @return bool
     *
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function getOTP(
        #[RPC\Assertions([
            new Assert\NotBlank,
            new Assert\Regex(
                pattern: '/^\+380\d{9}$/',
                message: 'The phone number is not a valid UA mobile number'
            ),
        ])] string $phone,
        #[RPC\Assertions([new Assert\NotBlank])]
        string $appName,
    ): bool
    {

        $this->loginLimiter->checkIp();

        try {
            $otp = $this->userSdkService->getOTP($phone, $appName);
        } catch (\Exception) {
            throw new GetOTPException();
        }

        $this->sendOtp($otp, $phone);

        return true;
    }

    protected function sendOtp(string $otp, string $phone): void
    {
        $message = $this->twig->render($this->otpTemplate, ['otp' => $otp]);

        $this->messengerSdkService->send(
            message: $message,
            subject: $this->otpSubject,
            contactId: $phone,
            channel: IMessengerSDK::DEFAULT_CHANEL,
            sender: $this->sender,
        );
    }

    /**
     * Вхід, отримання доступу
     *
     * @param string $phone Номер телефону користувача
     * @param string $otp OTP Код
     * @return object повертає обʼєкт доступу з токенами
     *
     */
    public function login(
        #[RPC\Assertions([
            new Assert\NotBlank,
            new Assert\Regex(
                pattern: '/^\+380\d{9}$/',
                message: 'The phone number is not a valid UA mobile number'
            ),
        ])]
        string $phone,
        #[RPC\Assertions([new Assert\NotBlank])]
        string $otp,
        #[RPC\Assertions([new Assert\NotBlank])]
        string $appName
    ): object
    {
        return $this->userSdkService->login($phone, $otp, $appName);
    }

    /**
     * Отримання інформації по користовачу
     *
     * @param string $accessToken Токен входу
     * @return UserMeInfoView повертає обʼєкт інфо
     * @throws RpcInvalidTokenException
     */
    public function me(
        #[RPC\Assertions([new Assert\NotBlank, new Assert\Uuid])]
        string $accessToken
    ): UserMeInfoView
    {
        try {
            $user = $this->userContext->getUser() ?? $this->userSdkService->me($accessToken);
        } catch (\Throwable $e) {
            throw new RpcInvalidTokenException($e->getMessage(), previous: $e);
        }
        return UserMeInfoView::fromArray(DTOTransformer::toArray($user));
    }

    /**
     * Оновлення доступу
     *
     * @param string $refreshToken Рефреш токен
     * @return object повертає оновленний обʼєкт входу
     */
    public function refresh(
        #[RPC\Assertions([new Assert\NotBlank, new Assert\Uuid])] string $refreshToken
    ): object
    {
        return $this->userSdkService->refresh($refreshToken);
    }
}
