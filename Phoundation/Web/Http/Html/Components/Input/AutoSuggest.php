<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Html\Components\Script;
use Phoundation\Web\Page;
use Stringable;


/**
 * Class AutoSuggest
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class AutoSuggest extends InputText
{
    /**
     * @var string|null $source_url
     */
    protected ?string $source_url = null;


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
     * @return $this
     */
    public function setSourceUrl(Stringable|string|null $source_url): static
    {
        $this->source_url = (string) $source_url;
        return $this;
    }


    /**
     * Render and return the HTML for this AutoSuggest Input Element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (empty($this->id)) {
            throw new OutOfBoundsException(tr('No ID or name specified for id auto suggest component'));
        }

        if (empty($this->source_url)) {
            throw new OutOfBoundsException(tr('No source URL specified for auto suggest component ":id"', [
                ':id' => $this->id
            ]));
        }

        // This input element requires some javascript
        Page::loadJavascript('adminlte/plugins/jquery-ui/jquery-ui');

        // Setup javascript for the component
        $script = Script::new()->setContent('$( "#' . $this->id . '" ).autocomplete({
              source: function(request, response) {
                $.ajax({
                  url: "' . $this->source_url . '",
                  dataType: "jsonp",
                  data: {
                    term: request.term
                  },
                  success: function(data) {
                    response(data);
                  }
                });
              },
              minLength: 2,
              select: function(event, ui) {
                console.log("Selected: " + ui.item.value + " aka " + ui.item.id);
              }
            });');

        // This input element also requires a <select>

        $this->attributes = array_merge($this->buildInputAttributes(), $this->attributes);
        return $script->render() . parent::render();
    }
}