<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Cards;

use Phoundation\Data\Traits\DataDescription;
use Phoundation\Data\Traits\DataTitle;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Button;
use Phoundation\Web\Html\Components\Buttons;
use Phoundation\Web\Html\Components\Widgets\Widget;


/**
 * Card class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Card extends Widget
{
    use DataTitle;
    use DataDescription;


    /**
     * If this card is collapsable or not
     *
     * @var bool $collapse_switch
     */
    protected bool $collapse_switch = false;

    /**
     * If this card can reload or not
     *
     * @var bool $reload_switch
     */
    protected bool $reload_switch = false;

    /**
     * If this card can close or not
     *
     * @var bool $close_switch
     */
    protected bool $close_switch = false;

    /**
     * If this card can maximize or not
     *
     * @var bool $maximize_switch
     */
    protected bool $maximize_switch = false;

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
     * Tracks if the card is collapsed or not
     *
     * @var bool $collapsed
     */
    protected bool $collapsed = false;

    /**
     * Buttons for this card
     *
     * @var Buttons|null $buttons
     */
    protected ?Buttons $buttons = null;


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
     * @param Buttons|Button|null $buttons
     * @return static
     */
    public function setButtons(Buttons|Button|null $buttons): static
    {
        if (is_object($buttons) and ($buttons instanceof Button)) {
            // This is a single button, store it in a buttons group
            $buttons = Buttons::new()->addButton($buttons);
        }

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

        if ($this->close_switch) {
            $return['close'] = true;
        }

        if ($this->maximize_switch) {
            $return['maximize'] = true;
        }

        if ($this->collapse_switch) {
            $return['collapse'] = true;
        }

        if ($this->reload_switch) {
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
        $this->close_switch    = false;
        $this->reload_switch   = false;
        $this->maximize_switch = false;
        $this->collapse_switch = false;

        foreach (Arrays::force($switches) as $switch) {
            switch ($switch) {
                case '':
                    // Ignore
                    break;

                case 'close':
                    $this->close_switch = true;
                    break;

                case 'reload':
                    $this->reload_switch = true;
                    break;

                case 'collapse':
                    $this->collapse_switch = true;
                    break;

                case 'maximize':
                    $this->maximize_switch = true;
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
    public function setHeaderContent(?string $header_content): static
    {
        $this->header_content = $header_content;
        return $this;
    }


    /**
     * Returns if the card can collapse
     *
     * @return bool
     */
    public function getCollapseSwitch(): bool
    {
        return $this->collapse_switch;
    }


    /**
     * Sets if the card can collapse
     *
     * @param bool $collapse_switch
     * @return static
     */
    public function setCollapseSwitch(bool $collapse_switch): static
    {
        $this->collapse_switch = $collapse_switch;
        return $this;
    }


    /**
     * Returns if the card is collapsed or not
     *
     * @return bool
     */
    public function getCollapsed(): bool
    {
        return $this->collapsed;
    }


    /**
     * Sets if the card is collapsed or not
     *
     * @param bool $collapsed
     * @return static
     */
    public function setCollapsed(bool $collapsed): static
    {
        $this->collapsed = $collapsed;

        if ($this->collapsed) {
            $this->classes->add(true, 'collapsed-card');

        } else {
            $this->classes->delete('collapsed-card');
        }

        return $this;
    }


    /**
     * Returns if the card can close
     *
     * @return bool
     */
    public function getCloseSwitch(): bool
    {
        return $this->close_switch;
    }


    /**
     * Sets if the card can close
     *
     * @param bool $close_switch
     * @return static
     */
    public function setCloseSwitch(bool $close_switch): static
    {
        $this->close_switch = $close_switch;
        return $this;
    }


    /**
     * Returns if the card can reload
     *
     * @return bool
     */
    public function getReloadSwitch(): bool
    {
        return $this->reload_switch;
    }


    /**
     * Sets if the card can reload
     *
     * @param bool $reload_switch
     * @return static
     */
    public function setReloadSwitch(bool $reload_switch): static
    {
        $this->reload_switch = $reload_switch;
        return $this;
    }


    /**
     * Returns if the card can maximize
     *
     * @return bool
     */
    public function getMaximizeSwitch(): bool
    {
        return $this->maximize_switch;
    }


    /**
     * Sets if the card can maximize
     *
     * @param bool $maximize_switch
     * @return static
     */
    public function setMaximizeSwitch(bool $maximize_switch): static
    {
        $this->maximize_switch = $maximize_switch;
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
