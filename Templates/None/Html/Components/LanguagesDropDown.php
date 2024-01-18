<?php

declare(strict_types=1);


namespace Templates\None\Html\Components;

use Phoundation\Date\Date;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Html\Renderer;


/**
 * LanguagesDropDown class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\None
 */
class LanguagesDropDown extends Renderer
{
    /**
     * LanguagesDropDown class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\LanguagesDropDown $element)
    {
        parent::__construct($element);
    }


    /**
     * Renders and returns the NavBar
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (!$this->render_object->getSettingsUrl()) {
            throw new OutOfBoundsException(tr('No settings page URL specified'));
        }

        $languages = $this->render_object->getLanguages();
        $count     = $languages?->getCount();

        $this->render = '   <a class="nav-link" data-toggle="dropdown" href="#">
                              <i style="color:red;">&#x1F1E8;&#x1F1E6;</i>                                                                                          
                            </a>
                            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">';

        if ($count) {
            $current = 0;

            $this->render .= '  <span class="dropdown-item dropdown-header">' . tr(':count Languages', [':count' => Html::safe($count)]) . '</span>
                                    <div class="dropdown-divider"></div>';

            foreach ($languages as $language) {
                if (++$current > 12) {
                    break;
                }

                $this->render .= '<a href="' . Html::safe(str_replace(':ID', $language->getId(), $this->render_object->getLanguagesUrl())) . '" class="dropdown-item">
                                    ' . ($language->getIcon() ? '<i class="text-' . Html::safe($language->getMode()->value) . ' fas fa-' . Html::safe($language->getIcon()) . ' mr-2"></i> ' : null) . Html::safe(Strings::truncate($language->getTitle()), 24) . '
                                    <span class="float-right text-muted text-sm"> ' . Html::safe(Date::getAge($language->getCreatedOn())) . '</span>
                                  </a>
                                  <div class="dropdown-divider"></div>';
            }
            
        } else {
            $this->render .= '  <span class="dropdown-item dropdown-header">' . tr('No alternative languages available') . '</span>
                                    <div class="dropdown-divider"></div>';
        }

        $this->render .= '        <a href="' . Html::safe($this->render_object->getSettingsUrl()) . '" class="dropdown-item dropdown-footer">' . tr('Language settings') . '</a>
                                </div>';

        return parent::render();
    }
}