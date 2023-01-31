<?php

namespace Phoundation\Web\Http\Html\Components\Widgets\Cards;

use Phoundation\Core\Arrays;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Buttons;
use Phoundation\Web\Http\Html\Components\Widgets\Widget;



/**
 * Card class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Card extends Widget
{
    /**
     * The card title
     *
     * @var string|null $title
     */
    protected ?string $title = null;

    /**
     * If this card is collapsable or not
     *
     * @var bool $has_collapse_switch
     */
    protected bool $has_collapse_switch = false;

    /**
     * If this card can reload or not
     *
     * @var bool $has_reload_switch
     */
    protected bool $has_reload_switch = false;

    /**
     * If this card can close or not
     *
     * @var bool $has_close_switch
     */
    protected bool $has_close_switch = false;

    /**
     * If this card can maximize or not
     *
     * @var bool $has_maximize_switch
     */
    protected bool $has_maximize_switch = false;

    /**
     * If this card is shown with outline color or not
     *
     * @var bool $outline
     */
    protected bool $outline = true;

    /**
     * Extra content for the card header
     *
     * @var string|null $header_content
     */
    protected ?string $header_content = null;

    /**
     * Buttons for this card
     *
     * @var Buttons|null $buttons
     */
    protected ?Buttons $buttons = null;



    /**
     * Returns the title for this card
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }



    /**
     * Sets the title for this card
     *
     * @param string $title
     * @return static
     */
    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }



    /**
     * Returns the buttons for this card
     *
     * @return Buttons|null
     */
    public function getButtons(): ?Buttons
    {
        return $this->buttons;
    }



    /**
     * Sets the buttons for this card
     *
     * @param Buttons|null $buttons
     * @return static
     */
    public function setButtons(?Buttons $buttons): static
    {
        $this->buttons = $buttons;
        return $this;
    }



    /**
     * Returns a list of enabled switches
     *
     * @return array
     */
    public function getSwitches(): array
    {
        $return = [];

        if ($this->has_close_switch) {
            $return['close'] = true;
        }

        if ($this->has_maximize_switch) {
            $return['maximize'] = true;
        }

        if ($this->has_collapse_switch) {
            $return['collapse'] = true;
        }

        if ($this->has_reload_switch) {
            $return['reload'] = true;
        }

        return $return;
    }



    /**
     * Sets a list of enabled switches
     *
     * @param array|string|null $switches
     * @return static
     */
    public function setSwitches(array|string|null $switches = null): static
    {
        $this->has_close_switch    = false;
        $this->has_reload_switch   = false;
        $this->has_maximize_switch = false;
        $this->has_collapse_switch = false;

        foreach (Arrays::force($switches) as $switch) {
            switch ($switch) {
                case '':
                    // Ignore
                    break;

                case 'close':
                    $this->has_close_switch = true;
                    break;

                case 'reload':
                    $this->has_reload_switch = true;
                    break;

                case 'collapse':
                    $this->has_collapse_switch = true;
                    break;

                case 'maximize':
                    $this->has_maximize_switch = true;
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown switch ":switch" specified', [
                        ':switch' => $switch
                    ]));
            }
        }

        return $this;
    }



    /**
     * Returns extra header content for the card
     *
     * @return string|null
     */
    public function getHeaderContent(): ?string
    {
        return $this->header_content;
    }



    /**
     * Returns extra header content for the card
     *
     * @param string|null $header_content
     * @return static
     */
    public function setHeaderContent(string|null $header_content): static
    {
        $this->header_content = $header_content;
        return $this;
    }



    /**
     * Returns if the card can collapse
     *
     * @return bool
     */
    public function hasCollapseSwitch(): bool
    {
        return $this->has_collapse_switch;
    }



    /**
     * Sets if the card can collapse
     *
     * @param bool $has_collapse_switch
     * @return static
     */
    public function setHasCollapseSwitch(bool $has_collapse_switch): static
    {
        $this->has_collapse_switch = $has_collapse_switch;
        return $this;
    }



    /**
     * Returns if the card can close
     *
     * @return bool
     */
    public function hasCloseSwitch(): bool
    {
        return $this->has_close_switch;
    }



    /**
     * Sets if the card can close
     *
     * @param bool $has_close_switch
     * @return static
     */
    public function setHasCloseSwitch(bool $has_close_switch): static
    {
        $this->has_close_switch = $has_close_switch;
        return $this;
    }



    /**
     * Returns if the card can reload
     *
     * @return bool
     */
    public function hasReloadSwitch(): bool
    {
        return $this->has_reload_switch;
    }



    /**
     * Sets if the card can reload
     *
     * @param bool $has_reload_switch
     * @return static
     */
    public function setHasReloadSwitch(bool $has_reload_switch): static
    {
        $this->has_reload_switch = $has_reload_switch;
        return $this;
    }



    /**
     * Returns if the card can maximize
     *
     * @return bool
     */
    public function hasMaximizeSwitch(): bool
    {
        return $this->has_maximize_switch;
    }



    /**
     * Sets if the card can maximize
     *
     * @param bool $has_maximize_switch
     * @return static
     */
    public function setHasMaximizeSwitch(bool $has_maximize_switch): static
    {
        $this->has_maximize_switch = $has_maximize_switch;
        return $this;
    }



    /**
     * Returns if this card is shown with outline color or not
     *
     * @return bool
     */
    public function getOutline(): bool
    {
        return $this->outline;
    }



    /**
     * Sets if this card is shown with outline color or not
     *
     * @param bool $outline
     * @return static
     */
    public function setOutline(bool $outline): static
    {
        $this->outline = $outline;
        return $this;
    }
}