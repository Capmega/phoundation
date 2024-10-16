<?php

/**
 * Class InputAutoSuggest
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataWidth;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Arrays;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Requests\Response;
use Stringable;


class InputAutoSuggest extends InputText
{
    use TraitDataWidth;

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
        // Auto suggest is only available when not readonly or not disabled
        if ($this->readonly or $this->disabled) {
            return parent::render();
        }

        if (empty($this->name)) {
            throw new OutOfBoundsException(tr('No required HTML name attribute specified for auto suggest component'));
        }

        if (empty($this->source_url)) {
            throw new OutOfBoundsException(tr('No source URL specified for auto suggest component ":name"', [
                ':name' => $this->name,
            ]));
        }

        if ($this->variables) {
            $variables = $this->variables->getSource();
            $variables = ',' . Arrays::implodeWithKeys($variables, ',' . PHP_EOL, ':');

        } else {
            $variables = null;
        }

        // This input element requires some javascript
        Response::loadJavascript('phoundation/adminlte/plugins/jquery-ui/jquery-ui');

        // Create JavaScript code for the component
        $script = Script::new()
                        ->setContent('$(\'[name="' . $this->name . '"]\').autocomplete({
                                          source: function(request, response) {
                                            let $selected = $(\'[name="' . $this->name . '"]\');
                            
                                            $.ajax({
                                              url: "' . $this->source_url . '",
                                              dataType: "jsonp",
                                              data: {
                                                term: request.term
                                                ' . $variables . '
                                              },
                                              success: function(data) {
                                                response(data.data);
                                              }
                                            });
                                          },
                     ' . ($this->width ? 'open: function(event, ui) {
                                               $(this).autocomplete("widget").css({
                                                   width: ' . $this->width . '
                                               });
                                          },' : '') . '
                                          delay: ' . $this->min_suggest_length . ', 
                                          minLength: ' . $this->min_suggest_length . ',
                                          select: function(event, ui) {
                                            console.log("Selected: " + ui.item.value + " aka " + ui.item.id);
                                          }
                                        });');

        $this->attributes = $this->renderInputAttributes()
                                 ->appendSource($this->attributes);

        return $script->render() . parent::render();
    }
}
