<?php

declare(strict_types=1);

namespace Alcoline\Core\Security\Service;

use Alcoline\Core\Api\DTO\UserMeInfoView;
use Alcoline\Core\Contracts\IUserSdk;
use Ufo\DTO\DTOTransformer;

readonly class UserFetcher
{
    public function __construct(
        private IUserSdk $userProcedure
    ) {}

    public function getUserFromAccessToken(string $accessToken): UserMeInfoView
    {
        $user = $this->userProcedure->me($accessToken);
        return UserMeInfoView::fromArray(DTOTransformer::toArray($user));
    }
}
