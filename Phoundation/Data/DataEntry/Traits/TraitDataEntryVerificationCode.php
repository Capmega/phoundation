<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;


/**
 * Trait TraitDataEntryVerificationCode
 *
 * This trait contains methods for DataEntry objects that require a verification code
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait TraitDataEntryVerificationCode
{
    /**
     * Returns the verification_code for this user
     *
     * @return string|null
     */
    public function getVerificationCode(): ?string
    {
        return $this->getSourceValueTypesafe('string', 'verification_code');
    }


    /**
     * Sets the verification_code for this user
     *
     * @param string|null $verification_code
     * @return static
     */
    public function setVerificationCode(?string $verification_code): static
    {
        return $this->setSourceValue('verification_code', $verification_code);
    }
}
