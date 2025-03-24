<?php

/**
 * Class InputAutoSuggest
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataSelector;
use Phoundation\Data\Traits\TraitDataWidth;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Requests\Response;
use Stringable;


class InputAutoSuggest extends InputText
{
    use TraitDataWidth;
    use TraitDataSelector {
        getSelector as __getSelector;
    }


    /**
     * The URL where the auto-suggest will retrieve the displayed data
     *
     * @var string|null $source_url
     */
    protected ?string $source_url = null;

    /**
     * Extra data fields to send to the source_url. Format should be like
     * [
     *     'countries_id' => '$("#countries_id").val()',
     *     'states_id'    => '$("#states_id").val()'
     * ]
     *
     * @var IteratorInterface|null $variables
     */
    protected IteratorInterface|null $variables = null;

    /**
     * The number of mS after typing stopped before auto-suggest will start querying the source URL
     *
     * @var int $delay
     */
    protected int $delay = 300;

    /**
     * The minimal number of characters typed before auto-suggest starts
     *
     * @var int $min_suggest_length
     */
    protected int $min_suggest_length = 2;


    /**
     * InputAutoSuggest class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
        $this->width = 300;
    }


    /**
     * Returns the internal source URL for this auto suggest component
     *
     * @return string
     */
    public function getSourceUrl(): string
    {
        return $this->source_url;
    }


    /**
     * Sets the internal source URL for this auto suggest component
     *
     * @param Stringable|string|null $source_url
     *
     * @return static
     */
    public function setSourceUrl(Stringable|string|null $source_url): static
    {
        $this->source_url = (string) $source_url;
        return $this;
    }


    /**
     * Returns the selector
     *
     * @return string|null
     */
    public function getSelector(): ?string
    {
        $selector = $this->__getSelector();

        if ($selector === null) {
            $selector = $this->getProperty('selector');

            if ($selector) {
                return $selector;
            }

            if ($this->getId()) {
                return '#' . $this->getId();
            }

            if ($this->getName()) {
                return '[name="' . $this->getName() . '"]';
            }

            throw new OutOfBoundsException(tr('Cannot return selector for InputAutosuggest object. No selector was specified and the object has no id or name specified either'));
        }

        return $selector;
    }


    /**
     * Returns the internal source URL for this auto suggest component
     *
     * @return IteratorInterface|null
     */
    public function getVariables(): IteratorInterface|null
    {
        if (!$this->variables) {
            $this->variables = new Iterator();
        }

        return $this->variables;
    }


    /**
     * Sets the internal source URL for this auto suggest component
     *
     * Extra data fields to send to the source_url. Format should be like
     * [
     *     'countries_id' => '$("#countries_id").val()',
     *     'states_id'    => '$("#states_id").val()'
     * ]
     *
     * @param IteratorInterface|array|null $variables
     *
     * @return static
     */
    public function setVariables(IteratorInterface|array|null $variables): static
    {
        $this->variables = Iterator::new()
                                   ->setSource($variables);

        return $this;
    }


    /**
     * Returns the
     *
     * @return int
     */
    public function getMinSuggestLength(): int
    {
        return $this->min_suggest_length;
    }


    /**
     * Sets the internal source URL for this auto suggest component
     *
     * @param int $min_suggest_length
     *
     * @return static
     */
    public function setMinSuggestLength(int $min_suggest_length): static
    {
        $this->min_suggest_length = $min_suggest_length;
        return $this;
    }


    /**
     * Returns the internal source URL for this auto suggest component
     *
     * @return int
     */
    public function getDelay(): int
    {
        return $this->delay;
    }


    /**
     * Sets the internal source URL for this auto suggest component
     *
     * @param int $delay
     *
     * @return static
     */
    public function setDelay(int $delay): static
    {
        $this->delay = $delay;
        return $this;
    }


    /**
     * Render and return the HTML for this AutoSuggest Input Element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $this->attributes = $this->renderInputAttributes()
                                 ->appendSource($this->attributes);

        return parent::render();
    }
}
