<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;


use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Page;

/**
 * Class FilterBar
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class FilterBar extends InputSelect
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

        // This input element also requires some javascript
        Script::new()->setContent('$("'. $this->getId() .'").select2();')->render();

        $this->attributes = $this->buildInputAttributes()->merge($this->attributes);
        return parent::render();

//        <div class="select2-purple">
//            <select class="select2 select2-hidden-accessible" multiple data-placeholder="Select a State" data-dropdown-css-class="select2-purple" style="width: 100%;" data-select2-id="15" tabindex="-1" aria-hidden="true">
//            </select>
//            <span class="select2 select2-container select2-container--default select2-container--below select2-container--focus" dir="ltr" data-select2-id="16" style="width: 100%;">
//                <span class="selection">
//                    <span class="select2-selection select2-selection--multiple" role="combobox" aria-haspopup="true" aria-expanded="false" tabindex="-1" aria-disabled="false">
//                        <ul class="select2-selection__rendered">
//                            <li class="select2-selection__choice" title="Alabama" data-select2-id="65">
//                                <span class="select2-selection__choice__remove" role="presentation">×</span>
//                                Alabama
//                            </li>
//                            <li class="select2-selection__choice" title="Alaska" data-select2-id="66">
//                                <span class="select2-selection__choice__remove" role="presentation">×</span>
//                                Alaska
//                            </li>
//                            <li class="select2-selection__choice" title="California" data-select2-id="67">
//                                <span class="select2-selection__choice__remove" role="presentation">×</span>
//                                California
//                            </li>
//                            <li class="select2-search select2-search--inline">
//                                <input class="select2-search__field" type="search" tabindex="0" autocomplete="off" autocorrect="off" autocapitalize="none" spellcheck="false" role="searchbox" aria-autocomplete="list" placeholder="" style="width: 0.75em;">
//                            </li>
//                        </ul>
//                    </span>
//                </span>
//                <span class="dropdown-wrapper" aria-hidden="true"></span>
//            </span>
//        </div>';
    }
}