<?php

/**
 * Accordion class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use Phoundation\Core\Log\Log;
use Phoundation\Data\Traits\TraitDataRenderMethod;
use Phoundation\Data\Traits\TraitDataUrl;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Seo\Seo;
use Phoundation\Utils\Numbers;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\Interfaces\AccordionInterface;
use Phoundation\Web\Html\Html;
use Stringable;


class Accordion extends Widget implements AccordionInterface
{
    use TraitDataUrl;
    use TraitDataRenderMethod;


    /**
     * The key of the source element that is open
     *
     * @var Stringable|string|float|int|null $open
     */
    protected Stringable|string|float|int|null $open = null;

    /**
     * Tracks if this accordion uses selectors or not
     *
     * @var bool
     */
    protected bool $selectors = false;


    /**
     * Accordion class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct();

        if ($content) {
            $this->setSource($content);
        }
    }


    /**
     * Returns if this accordion should use selectors or not
     *
     * @return bool
     */
    public function getSelectors(): bool
    {
        return $this->selectors;
    }


    /**
     * Sets if this accordion should use selectors or not
     *
     * @param bool $selectors
     * @return static
     */
    public function setSelectors(bool $selectors): static
    {
        $this->selectors = $selectors;
        return $this;
    }


    /**
     * Returns the key of the accordion element that is open
     *
     * @return Stringable|string|float|int|null $open
     */
    public function getOpen(): Stringable|string|float|int|null
    {
        return $this->open;
    }


    /**
     * Sets the key of the accordion element that is open
     *
     * @param Stringable|string|float|int|null $open
     *
     * @return static
     */
    public function setOpen(Stringable|string|float|int|null $open): static
    {
        $this->open = $open;
        return $this;
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        if (!$this->getId()) {
            throw new OutOfBoundsException(tr('Cannot render accordion, no HTML id specified'));
        }

        $return   =  '        <div class="accordion" id="' . $this->getId() . '">';

        foreach ($this->source as $key => $value) {
            $seo_key = Seo::string($key);
            $return .= '        <div class="accordion-item">
                                    <h2 class="accordion-header' . ($this->selectors ? ' accordion-header-selectors' : null) . '" id="accordion-heading-' . $seo_key . '">
                                        ' . $this->getSelector($seo_key) . '
                                        <button data-mdb-collapse-init class="accordion-button text-dark' . ($key === $this->open ? ' collapsed' : '') . '" type="button" data-mdb-toggle="collapse" data-mdb-target="#accordion-collapse-' . $seo_key . '" aria-expanded="' . ($key === $this->open ? 'true' : 'false') . '" aria-controls="accordion-collapse-' . $seo_key . '">
                                            ' . $key . '
                                        </button>
                                    </h2>
                                    <div id="accordion-collapse-' . $seo_key . '" class="accordion-collapse collapse' . ($key === $this->open ? ' show' : '') . '" aria-labelledby="accordion-heading' . $seo_key . '" data-mdb-parent="#' . $this->getId() . '">
                                        <div class="accordion-body">
                                            ' . $value . '
                                        </div>
                                    </div>
                                </div>';

Log::debug(Numbers::getHumanReadableBytes(strlen($return)));
Log::debug(Numbers::getHumanReadableBytes(memory_get_peak_usage()));
        }

        if ($this->selectors) {
            return $return . '  </div>' . Script::new('
                $("div.accordion-selector").on("click", function() {
                    $(this).siblings("button").toggleClass("accordion-selected bg-primary")
                                              .toggleClass("text-dark text-light");
                });
            ')->render();
        }

        return $return . '  </div>';
    }


    /**
     * Returns a selector div in case those are enabled
     *
     * @param string|int $key
     *
     * @return string|null
     */
    protected function getSelector(string|int $key): ?string
    {
        if ($this->selectors) {
            return '<div class="accordion-selector accordion-selector-' . $key . '"></div>';
        }

        return null;
    }
}
