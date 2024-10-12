<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms\Interfaces;

use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Stringable;

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
     *
     * @return static
     */
    public function setAction(Stringable|string|null $action): static;


    /**
     * Sets the form method
     *
     * @return EnumHttpRequestMethod|null
     */
    public function getRequestMethod(): ?EnumHttpRequestMethod;


    /**
     * Sets the form method
     *
     * @param EnumHttpRequestMethod $method
     *
     * @return static
     */
    public function setRequestMethod(EnumHttpRequestMethod $method): static;


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
     *
     * @return static
     */
    public function setNoValidate(bool $no_validate): static;


    /**
     * Sets the form auto_complete
     *
     * @return bool
     */
    public function getAutoComplete(): bool;


    /**
     * Sets the form auto_complete
     *
     * @param bool $auto_complete
     *
     * @return static
     */
    public function setAutoComplete(bool $auto_complete): static;


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
     *
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
     *
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
     *
     * @return static
     */
    public function setTarget(string $target): static;
}
