<?php

/**
 * Accordion class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataRenderMethod;
use Phoundation\Data\Traits\TraitDataUrl;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Seo;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\Interfaces\AccordionInterface;
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
     * Tracks optional headers for this accordion
     *
     * @var array $headers
     */
    protected array $headers = [];

    /**
     * Tracks optional classes for each of the headers for this accordion
     *
     * @var array $headers
     */
    protected array $header_classes = [];


    /**
     * Accordion class constructor
     *
     * @param string|null $source
     */
    public function __construct(?string $source = null)
    {
        parent::__construct();

        if ($source) {
            $this->setSource($source);
        }
    }


    /**
     * Returns if this input element has before content
     *
     * @return bool
     */
    public function hasHeaders(): bool
    {
        return (bool) count($this->headers);
    }


    /**
     * Returns the modal headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }


    /**
     * Sets the modal headers
     *
     * @param IteratorInterface|RenderInterface|array|callable|string|null $headers
     *
     * @return static
     */
    public function setHeaders(IteratorInterface|RenderInterface|array|callable|string|null $headers): static
    {
        $this->headers = [];
        return $this->addHeaders($headers);
    }


    /**
     * Sets the modal headers
     *
     * @param IteratorInterface|RenderInterface|array|callable|string|null $headers
     *
     * @return static
     */
    public function addHeaders(IteratorInterface|RenderInterface|array|callable|string|null $headers): static
    {
        if ($headers instanceof IteratorInterface) {
            $headers = $headers->getSource();
        }

        if (is_array($headers)) {
            foreach ($headers as $content) {
                $this->addHeaders($content);
            }

            return $this;
        }

        $this->headers[] = $headers;
        return $this;
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
     * Sets the first key of the accordion to open
     *
     * @param bool $do
     *
     * @return static
     */
    public function setOpenFirst(bool $do = true): static
    {
        if ($do) {
            $this->open = $this->getFirstKey();
        }

        return $this;
    }


    /**
     * Sets whether a key should be displayed or not
     *
     * @param string $key
     * @param bool   $display
     *
     * @return static
     */
    public function setDisplayKey(string $key, bool $display): static
    {
        if ($display) {
            $this->header_classes[$key] = null;

        } else {
            $this->header_classes[$key] = 'd-none';
        }

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

        $return = '         <div class="accordion" id="' . $this->getId() . '">' . $this->renderHeaders();

        foreach ($this->source as $key => $value) {
            $seo_key = Seo::string($key);
            $data    = $this->renderDataKey($key);
            $return .= '        <div class="accordion-item ' . array_get_safe($this->header_classes,$key) . '"' . $data . '>
                                    <h2 class="accordion-header' . ($this->selectors ? ' accordion-header-selectors' : null) . '" id="accordion-heading-' . $seo_key . '">
                                        ' . $this->getSelector($seo_key) . '
                                        <button data-mdb-collapse-init class="accordion-button text-dark' . (($key === $this->open) ? ' collapsed' : '') . '" type="button" data-mdb-toggle="collapse" data-mdb-target="#accordion-collapse-' . $seo_key . '" aria-expanded="' . (($key === $this->open) ? 'true' : 'false') . '" aria-controls="accordion-collapse-' . $seo_key . '">
                                            ' . $key . '
                                        </button>
                                    </h2>
                                    <div id="accordion-collapse-' . $seo_key . '" class="accordion-collapse collapse' . (($key === $this->open) ? ' show' : '') . '" aria-labelledby="accordion-heading' . $seo_key . '" data-mdb-parent="#' . $this->getId() . '">
                                        <div class="accordion-body">
                                            ' . $value . '
                                        </div>
                                    </div>
                                </div>';
        }

        $this->render .= $return . '  </div>';

        if ($this->selectors) {
            $this->render .= Script::new('
                $("div.accordion-selector").on("click", function() {
                    $(this).siblings("button").toggleClass("accordion-selected bg-primary")
                                              .toggleClass("text-dark text-light");
                });');
        }

        return parent::render();
    }


    /**
     * Renders and returns the content for an optional header
     *
     * @return string|null
     */
    protected function renderHeaders(): ?string
    {
        $return = null;

        if ($this->headers) {
            foreach ($this->headers as $header) {
                $return .= '    <div class="accordion-item">
                                    <div class="accordion-headers">    
                                        ' . $header . '
                                    </div>
                                </div>';
            }

            return $return;
        }

        return null;
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
