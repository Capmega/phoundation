<?php

namespace Phoundation\Accounts\Users\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


interface AuthenticationInterface extends DataEntryInterface
{
    /**
     * Returns the account for this authentication
     *
     * @return string|null
     */
    public function getAccount(): ?string;


    /**
     * Sets the account for this authentication
     *
     * @param string|null $user_agent
     *
     * @return static
     */
    public function setAccount(?string $user_agent): static;


    /**
     * Returns the captcha_required for this authentication
     *
     * @return int|bool|null
     */
    public function getCaptchaRequired(): int|bool|null;


    /**
     * Sets the captcha_required for this authentication
     *
     * @param int|bool|null $user_agent
     *
     * @return static
     */
    public function setCaptchaRequired(int|bool|null $user_agent): static;


    /**
     * Returns the failed_reason for this authentication
     *
     * @return string|null
     */
    public function getFailedReason(): ?string;


    /**
     * Sets the failed_reason for this authentication
     *
     * @param string|null $user_agent
     *
     * @return static
     */
    public function setFailedReason(?string $user_agent): static;

    /**
     * Returns the user object that created this data entry
     *
     * @note Returns NULL if this class has no support for created_by information or has not been written to disk yet
     *
     * @param UserInterface|null $user
     *
     * @return static
     */
    public function setCreatedByUserObject(?UserInterface $user): static;

    /**
     * Sets the users_id that created this data entry
     *
     * @param int|null $users_id
     *
     * @return static
     */
    public function setCreatedBy(?int $users_id): static;
}
