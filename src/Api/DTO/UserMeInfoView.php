<?php

namespace Alcoline\Core\Api\DTO;

use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Interfaces\IArrayConstructible;
use Ufo\DTO\Interfaces\IArrayConvertible;

class UserMeInfoView implements IArrayConvertible, IArrayConstructible
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;

    public ?string $routeName = null;
    public string $userId;
    public string $phone;
    public string $fullName;
    public string $firstName;
    public string $lastName;
    public string $roleName;
    public string $roleSlug;
    public string $createdAt;
    public string $updatedAt;
    public string|null $email;
    public string|null $externalId;
//    public TeamShortInfoView|null $team;
}