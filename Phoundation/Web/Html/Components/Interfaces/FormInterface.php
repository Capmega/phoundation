<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Interfaces;


use Stringable;

/**
 * Form class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
interface FormInterface
{
    /**
     * Sets the form action.
     *
     * Defaults to the current URL
     *
     * @return string|null
     */
    public function getAction(): ?string;

    /**
     * Sets the form action
     *
     * @param Stringable|string|null $action
     * @return static
     */
    public function setAction(Stringable|string|null $action): static;

    /**
     * Sets the form method
     *
     * @return string|null
     */
    public function getMethod(): ?string;

    /**
     * Sets the form method
     *
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): static;

    /**
     * Sets the form no_validate
     *
     * @return bool
     */
    public function getNoValidate(): bool;

    /**
     * Sets the form no_validate
     *
     * @param bool $no_validate
     * @return static
     */
    public function setNoValidate(bool $no_validate): static;

    /**
     * Sets the form auto_complete
     *
     * @return string|null
     */
    public function getAutoComplete(): ?string;

    /**
     * Sets the form auto_complete
     *
     * @param string $auto_complete
     * @return static
     */
    public function setAutoComplete(string $auto_complete): static;

    /**
     * Sets the form accept_charset
     *
     * @return string|null
     */
    public function getAcceptCharset(): ?string;

    /**
     * Sets the form accept_charset
     *
     * @param string $accept_charset
     * @return static
     */
    public function setAcceptCharset(string $accept_charset): static;

    /**
     * Sets the form rel
     *
     * @return string|null
     */
    public function getRel(): ?string;

    /**
     * Sets the form rel
     *
     * @param string $rel
     * @return static
     */
    public function setRel(string $rel): static;

    /**
     * Sets the form target
     *
     * @return string|null
     */
    public function getTarget(): ?string;

    /**
     * Sets the form target
     *
     * @param string $target
     * @return static
     */
    public function setTarget(string $target): static;
}
