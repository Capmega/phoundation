<?php

 namespace Phoundation\Web\Html\Components\Widgets\FlashMessages\Interfaces;

use Phoundation\Web\Html\Components\Widgets\Interfaces\WidgetInterface;

interface ToastInterface extends WidgetInterface
{
/**
     * Renders and returns the HTML and JavaScript to display a toast
     *
     * @return string|null
     */
    public function render(): ?string;
/**
     * Renders and returns the HTML and JavaScript to display a toast
     *
     * @return array
     */
    public function renderArray(): array;
/**
     * Renders and returns the HTML and JavaScript to display a toast
     *
     * @return string|null
     */
    public function renderJson(): ?string;
}
