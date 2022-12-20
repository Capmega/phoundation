<?php

namespace Templates\AdminLte\Components\Widgets\Cards;

use Phoundation\Core\Arrays;
use Phoundation\Exception\OutOfBoundsException;
use Templates\AdminLte\Components\Widgets\Widget;



/**
 * AdminLte Plugin Card class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
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
     * @var bool $has_collapse_button
     */
    protected bool $has_collapse_button = true;

    /**
     * If this card can reload or not
     *
     * @var bool $has_reload_button
     */
    protected bool $has_reload_button = true;

    /**
     * If this card can close or not
     *
     * @var bool $has_close_button
     */
    protected bool $has_close_button = true;

    /**
     * If this card can maximize or not
     *
     * @var bool $has_maximize_button
     */
    protected bool $has_maximize_button = true;

    /**
     * If this card is shown with outline color or not
     *
     * @var bool $outline
     */
    protected bool $outline = true;



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
     * Returns a list of enabled buttons
     *
     * @return array
     */
    public function getButtons(): array
    {
        $return = [];

        if ($this->has_close_button) {
            $return['close'] = true;
        }

        if ($this->has_maximize_button) {
            $return['maximize'] = true;
        }

        if ($this->has_collapse_button) {
            $return['collapse'] = true;
        }

        if ($this->has_reload_button) {
            $return['reload'] = true;
        }

        return $return;
    }



    /**
     * Sets a list of enabled buttons
     *
     * @param array|string|null $buttons
     * @return static
     */
    public function setButtons(array|string|null $buttons = null): static
    {
        $this->has_close_button    = false;
        $this->has_reload_button   = false;
        $this->has_maximize_button = false;
        $this->has_collapse_button = false;

        foreach (Arrays::force($buttons) as $button) {
            switch ($button) {
                case '':
                    // Ignore
                    break;

                case 'close':
                    $this->has_close_button = true;
                    break;

                case 'reload':
                    $this->has_reload_button = true;
                    break;

                case 'collapse':
                    $this->has_collapse_button = true;
                    break;

                case 'maximize':
                    $this->has_maximize_button = true;
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown button ":button" specified', [
                        ':button' => $button
                    ]));
            }
        }

        return $this;
    }



    /**
     * Returns if the card can collapse
     *
     * @return bool
     */
    public function getHasCollapseButton(): bool
    {
        return $this->has_collapse_button;
    }



    /**
     * Sets if the card can collapse
     *
     * @param bool $has_collapse_button
     * @return static
     */
    public function setHasCollapseButton(bool $has_collapse_button): static
    {
        $this->has_collapse_button = $has_collapse_button;
        return $this;
    }



    /**
     * Returns if the card can close
     *
     * @return bool
     */
    public function getHasCloseButton(): bool
    {
        return $this->has_close_button;
    }



    /**
     * Sets if the card can close
     *
     * @param bool $has_close_button
     * @return static
     */
    public function setHasCloseButton(bool $has_close_button): static
    {
        $this->has_close_button = $has_close_button;
        return $this;
    }



    /**
     * Returns if the card can reload
     *
     * @return bool
     */
    public function getHasReloadButton(): bool
    {
        return $this->has_reload_button;
    }



    /**
     * Sets if the card can reload
     *
     * @param bool $has_reload_button
     * @return static
     */
    public function setHasReloadButton(bool $has_reload_button): static
    {
        $this->has_reload_button = $has_reload_button;
        return $this;
    }



    /**
     * Returns if the card can maximize
     *
     * @return bool
     */
    public function getHasMaximizeButton(): bool
    {
        return $this->has_maximize_button;
    }



    /**
     * Sets if the card can maximize
     *
     * @param bool $has_maximize_button
     * @return static
     */
    public function setHasMaximizeButton(bool $has_maximize_button): static
    {
        $this->has_maximize_button = $has_maximize_button;
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



    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        $html = '   <div class="card bg-' . ($this->gradient ? 'gradient-' : '') . $this->type . '">
                      <div class="card-header">
                        <h3 class="card-title">' . $this->title . '</h3>
                        <div class="card-tools">
                          ' . ($this->has_reload_button ? '  <button type="button" class="btn btn-tool" data-card-widget="card-refresh" data-source="widgets.html" data-source-selector="#card-refresh-content" data-load-on-init="false">
                                                        <i class="fas fa-sync-alt"></i>
                                                      </button>' : '') . '
                          ' . ($this->has_maximize_button ? '<button type="button" class="btn btn-tool" data-card-widget="maximize">
                                                        <i class="fas fa-expand"></i>
                                                      </button>' : '') . '
                          ' . ($this->has_collapse_button ? '<button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                        <i class="fas fa-minus"></i>
                                                      </button>' : '') . '
                          ' . ($this->has_close_button ? '   <button type="button" class="btn btn-tool" data-card-widget="remove">
                                                        <i class="fas fa-times"></i>
                                                      </button>' : '') . '
                        </div>
                      </div>
                      <!-- /.card-header -->
                      <div class="card-body">
                        ' . $this->content. '
                      </div>
                    </div>';

        return $html;
    }
}