<?php

namespace Phoundation\Core\Meta\Activities\Interfaces;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Data\Interfaces\ArraySourceInterface;

interface ActivityInterface extends ArraySourceInterface
{
    /**
     * Returns a user object for the user that performed this action
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface;

    /**
     * Returns the executed action
     *
     * @return string
     */
    public function getAction(): string;

    /**
     * Returns the executed action
     *
     * @return string
     */
    public function getComment(): string;

    /**
     * Returns the data for the executed action
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Returns the moment this action was performed
     *
     * @return string
     */
    public function getMoment(): string;

    /**
     * Renders and returns the HTML
     *
     * @return string|null
     */
    public function render(): ?string;

    /**
     * Returns if the executed action is the specified action
     *
     * @param string $action
     *
     * @return bool
     */
    public function isAction(string $action): bool;
}
