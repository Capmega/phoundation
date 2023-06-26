<?php

declare(strict_types=1);

namespace Phoundation\Web\Http\Html\Components\Input;


use Phoundation\Web\Page;

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
     * Render and return the HTML for this AutoSuggest Input Element
     *
     * @return string|null
     */
    public function render(): ?string
    {
        // This input element requires some javascript
        Page::loadJavascript('plugins/select2/js/select2.full');

        // This input element also requires a <select>

        $this->attributes = array_merge($this->buildInputAttributes(), $this->attributes);
        return parent::render();


        $render = ' <select class="select2 select2-hidden-accessible" multiple="" data-placeholder="Select a State" style="width: 100%;" data-select2-id="7" tabindex="-1" aria-hidden="true">
                </select>
                <span class="select2 select2-container select2-container--default select2-container--below" dir="ltr" data-select2-id="8" style="width: 100%;">
                    <span class="selection">
                        <span class="select2-selection select2-selection--multiple" role="combobox" aria-haspopup="true" aria-expanded="false" tabindex="-1" aria-disabled="false">
                            <ul class="select2-selection__rendered">
                                <li class="select2-search select2-search--inline">
                                    <input class="select2-search__field" type="search" tabindex="0" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" role="searchbox" aria-autocomplete="list" placeholder="Select a State" style="width: 580.4px;">
                                </li>
                            </ul>
                        </span>
                    </span>
                    <span class="dropdown-wrapper" aria-hidden="true"></span>
                </span>';
    }

}