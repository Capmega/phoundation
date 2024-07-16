<?php

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

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Accounts\Enums\EnumAccountType;

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
     * @param EnumAccountType|string|null $account_type
     *
     * @return static
     */
    public function setAccountType(EnumAccountType|string|null $account_type): static
    {
        if ($account_type instanceof EnumAccountType) {
            $account_type = $account_type->value;

        } elseif ($account_type) {
            $account_type = EnumAccountType::from($account_type)->value;
        }

        return $this->set(get_null($account_type), 'account_type');
    }
}
