<?php

namespace Templates\AdminLte\Components\Widgets\Cards;

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
     * The body content of the card
     *
     * @var string|null $body
     */
    protected ?string $body = null;

    /**
     * If this card is collapsable or not
     *
     * @var bool $has_collapse
     */
    protected bool $has_collapse = true;

    /**
     * If this card can reload or not
     *
     * @var bool $has_reload
     */
    protected bool $has_reload = true;

    /**
     * If this card can close or not
     *
     * @var bool $has_close
     */
    protected bool $has_close = true;

    /**
     * If this card can maximize or not
     *
     * @var bool $has_maximize
     */
    protected bool $has_maximize = true;

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
     * Returns the body content of the card
     *
     * @return string|null
     */
    public function getBody(): ?string
    {
        return $this->body;
    }



    /**
     * Sets the body content of the card
     *
     * @param string|null $body
     * @return static
     */
    public function setBody(?string $body): static
    {
        $this->body = $body;
        return $this;
    }



    /**
     * Returns if the card can collapse
     *
     * @return bool
     */
    public function getHasCollapse(): bool
    {
        return $this->has_collapse;
    }



    /**
     * Sets if the card can collapse
     *
     * @param bool $has_collapse
     * @return static
     */
    public function setHasCollapse(bool $has_collapse): static
    {
        $this->has_collapse = $has_collapse;
        return $this;
    }



    /**
     * Returns if the card can close
     *
     * @return bool
     */
    public function getHasClose(): bool
    {
        return $this->has_close;
    }



    /**
     * Sets if the card can close
     *
     * @param bool $has_close
     * @return static
     */
    public function setHasClose(bool $has_close): static
    {
        $this->has_close = $has_close;
        return $this;
    }



    /**
     * Returns if the card can reload
     *
     * @return bool
     */
    public function getHasReload(): bool
    {
        return $this->has_reload;
    }



    /**
     * Sets if the card can reload
     *
     * @param bool $has_reload
     * @return static
     */
    public function setHasReload(bool $has_reload): static
    {
        $this->has_reload = $has_reload;
        return $this;
    }



    /**
     * Returns if the card can maximize
     *
     * @return bool
     */
    public function getHasMaximize(): bool
    {
        return $this->has_maximize;
    }



    /**
     * Sets if the card can maximize
     *
     * @param bool $has_maximize
     * @return static
     */
    public function setHasMaximize(bool $has_maximize): static
    {
        $this->has_maximize = $has_maximize;
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
                          ' . ($this->has_reload ? '  <button type="button" class="btn btn-tool" data-card-widget="card-refresh" data-source="widgets.html" data-source-selector="#card-refresh-content" data-load-on-init="false">
                                                        <i class="fas fa-sync-alt"></i>
                                                      </button>' : '') . '
                          ' . ($this->has_maximize ? '<button type="button" class="btn btn-tool" data-card-widget="maximize">
                                                        <i class="fas fa-expand"></i>
                                                      </button>' : '') . '
                          ' . ($this->has_collapse ? '<button type="button" class="btn btn-tool" data-card-widget="collapse">
                                                        <i class="fas fa-minus"></i>
                                                      </button>' : '') . '
                          ' . ($this->has_close ? '   <button type="button" class="btn btn-tool" data-card-widget="remove">
                                                        <i class="fas fa-times"></i>
                                                      </button>' : '') . '
                        </div>
                      </div>
                      <!-- /.card-header -->
                      <div class="card-body">
                        ' . $this->body. '
                      </div>
                    </div>';

        return $html;
    }
}