<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Stringable;

interface SignInKeyInterface extends DataEntryInterface
{
    /**
     * Returns the valid_until for this object
     *
     * @return string|null
     */
    public function getValidUntil(): ?string;


    /**
     * Sets the allow_navigation for this object
     *
     * @param string|null $valid_until
     *
     * @return static
     */
    public function setValidUntil(?string $valid_until): static;


    /**
     * Returns the allow_navigation for this object
     *
     * @return bool
     */
    public function getAllowNavigation(): bool;


    /**
     * Sets the allow_navigation for this object
     *
     * @param int|bool|null $allow_navigation
     *
     * @return static
     */
    public function setAllowNavigation(int|bool|null $allow_navigation): static;


    /**
     * Returns the once for this object
     *
     * @return ?bool
     */
    public function getOnce(): ?bool;


    /**
     * Sets the once for this object
     *
     * @param bool|null $once
     *
     * @return static
     */
    public function setOnce(?bool $once): static;


    /**
     * Generates the requested sign-in key and returns the corresponding UUID
     *
     * @param string|null $redirect
     *
     * @return static
     */
    public function generate(?string $redirect): static;


    /**
     * Apply this sign-in key
     *
     * @return $this
     */
    public function execute(): static;


    /**
     * Returns the redirect for this object
     *
     * @return string|null
     */
    public function getRedirect(): ?string;


    /**
     * Sets the redirect for this object
     *
     * @param Stringable|string|null $redirect
     *
     * @return static
     */
    public function setRedirect(Stringable|string|null $redirect): static;


    /**
     * Returns true if this object's redirect URL
     *
     * @param Stringable|String $url
     * @param string            $target
     *
     * @return bool
     */
    public function signKeyAllowsUrl(Stringable|string $url, string $target): bool;
}
