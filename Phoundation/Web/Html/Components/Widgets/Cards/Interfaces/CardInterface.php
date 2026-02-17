<?php

namespace Phoundation\Web\Html\Components\Widgets\Cards\Interfaces;

use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonInterface;
use Phoundation\Web\Html\Components\Input\Buttons\Interfaces\ButtonsInterface;
use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Widgets\Interfaces\WidgetInterface;
use Phoundation\Web\Html\Components\Widgets\Tabs\Interfaces\TabsInterface;
use Stringable;

interface CardInterface extends WidgetInterface
{
    /**
     * Returns the buttons for this card
     *
     * @return ButtonsInterface
     */
    public function getButtonsObject(): ButtonsInterface;

    /**
     * Sets the buttons for this card
     *
     * @param ButtonsInterface|ButtonInterface|null $buttons
     *
     * @return static
     */
    public function setButtonsObject(ButtonsInterface|ButtonInterface|null $buttons): static;

    /**
     * Returns the header title tag
     *
     * @return string
     */
    public function getHeaderTitleTag(): string;

    /**
     * Sets the header title tag
     *
     * @param string $tag
     *
     * @return static
     */
    public function setHeaderTitleTag(string $tag): static;

    /**
     * Returns a list of enabled switches
     *
     * @return array
     */
    public function getSwitches(): array;

    /**
     * Sets a list of enabled switches
     *
     * @param array|string|null $switches
     *
     * @return static
     */
    public function setSwitches(array|string|null $switches = null): static;

    /**
     * Returns extra header content for the card
     *
     * @return string|null
     */
    public function getHeaderContent(): ?string;

    /**
     * Returns extra header content for the card
     *
     * @param string|null $header_content
     *
     * @return static
     */
    public function setHeaderContent(?string $header_content): static;

    /**
     * Returns extra footer content for the card
     *
     * @return string|null
     */
    public function getFooterContent(): ?string;

    /**
     * Returns extra footer content for the card
     *
     * @param Stringable|string|null $footer_content
     *
     * @return static
     */
    public function setFooterContent(Stringable|string|null $footer_content): static;

    /**
     * Returns if the card can collapse
     *
     * @return bool
     */
    public function getCollapseSwitch(): bool;

    /**
     * Sets if the card can collapse
     *
     * @param bool $collapse_switch
     *
     * @return static
     */
    public function setCollapseSwitch(bool $collapse_switch): static;

    /**
     * Returns if the card is collapsed or not
     *
     * @return bool
     */
    public function getCollapsed(): bool;

    /**
     * Sets if the card is collapsed or not
     *
     * @param bool $collapsed
     *
     * @return static
     */
    public function setCollapsed(bool $collapsed): static;

    /**
     * Returns if the card can close
     *
     * @return bool
     */
    public function getCloseSwitch(): bool;

    /**
     * Sets if the card can close
     *
     * @param bool $close_switch
     *
     * @return static
     */
    public function setCloseSwitch(bool $close_switch): static;

    /**
     * Returns if the card can reload
     *
     * @return bool
     */
    public function getReloadSwitch(): bool;

    /**
     * Sets if the card can reload
     *
     * @param bool $reload_switch
     *
     * @return static
     */
    public function setReloadSwitch(bool $reload_switch): static;

    /**
     * Returns if the card can maximize
     *
     * @return bool
     */
    public function getMaximizeSwitch(): bool;

    /**
     * Sets if the card can maximize
     *
     * @param bool $maximize_switch
     *
     * @return static
     */
    public function setMaximizeSwitch(bool $maximize_switch): static;

    /**
     * Returns if this card is shown with outline color or not
     *
     * @return bool
     */
    public function getOutline(): bool;

    /**
     * Sets if this card is shown with outline color or not
     *
     * @param bool $outline
     *
     * @return static
     */
    public function setOutline(bool $outline): static;

    /**
     * @inheritDoc
     */
    public function setContent(RenderInterface|callable|string|float|int|null $content, bool $make_safe = false): static;

    /**
     * Returns true if this card uses tabs
     *
     * @return bool
     */
    public function usesTabs(): bool;

    /**
     * Returns the Tabs object
     *
     * @param bool $create
     *
     * @return TabsInterface|null
     */
    public function getTabsObject(bool $create = true): ?TabsInterface;
}