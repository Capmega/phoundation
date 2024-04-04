<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Accounts\Enums\EnumAccountType;
use Phoundation\Accounts\Enums\Interfaces\EnumAccountTypeInterface;


/**
 * Trait TraitDataEntryVerificationCode
 *
 * This trait contains methods for DataEntry objects that require a verification code
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */
trait TraitDataEntryAccountType
{
    /**
     * Returns the account_type for this user
     *
     * @return string|null
     */
    public function getAccountType(): ?string
    {
        return $this->getValueTypesafe('string', 'account_type');
    }


    /**
     * Sets the account_type for this user
     *
     * @param EnumAccountTypeInterface|string|null $account_type
     *
     * @return static
     */
    public function setAccountType(EnumAccountTypeInterface|string|null $account_type): static
    {
        if ($account_type instanceof EnumAccountTypeInterface) {
            $account_type = $account_type->value;

        } elseif ($account_type) {
            $account_type = EnumAccountType::from($account_type)->value;
        }

        return $this->setValue('account_type', get_null($account_type));
    }
}
