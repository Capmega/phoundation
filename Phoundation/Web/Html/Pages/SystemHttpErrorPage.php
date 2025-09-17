<?php

/**
 * Class Template
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Templates
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Pages;

use Phoundation\Accounts\Users\Sessions\Session;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataDisabled;
use Phoundation\Data\Traits\TraitDataIteratorSections;
use Phoundation\Data\Traits\TraitDataIteratorTexts;
use Phoundation\Data\Traits\TraitDataReadonly;
use Phoundation\Data\Traits\TraitDataStringName;
use Phoundation\Data\Traits\TraitDataStringSource;
use Phoundation\Data\Traits\TraitMethodHasRendered;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Csrf;
use Phoundation\Web\Html\Enums\EnumAnchorRenderRightsFail;
use Phoundation\Web\Html\Pages\Interfaces\TemplateInterface;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;


class SystemHttpErrorPage extends Page
{
    use TraitDataStringName {
        setName as protected __setName;
    }
    use TraitDataStringSource;
    use TraitDataIteratorTexts;
    use TraitDataIteratorSections;
    use TraitMethodHasRendered;
    use TraitDataReadonly;
    use TraitDataDisabled;


    /**
     * Template class constructor
     *
     * @param string|null $name
     */
    public function __construct(?string $name)
    {
        $this->setName($name);
        $this->o_texts = new Iterator();
    }


    /**
     * Returns the rendered version of this object
     *
     * @return string
     */
    public function __toString(): string
    {
        return (string) $this->render();
    }


    /**
     * Returns a new Template page object
     *
     * @param string|null $name
     *
     * @return TemplateInterface
     */
    public static function new(?string $name = null): TemplateInterface
    {
        return new static($name);
    }


    /**
     * Renders and returns the HTML for this object using the template renderer if available
     *
     * @note Templates work as follows: Any component that renders HTML must be in an html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found, then that file will
     *       render the HTML for the component. For example, Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see  Element::render(), ElementsBlock::render()
     */
    public function render(): ?string
    {
        $text = $this->source;

        foreach ($this->o_texts as $search => $replace) {
            $text = str_replace($search, (string) $replace, $text);
        }

        return $text;
    }


    /**
     * Sets the template page to use
     *
     * @param string|null $name
     *
     * @return static
     * @todo Implement! For now this just returns hard coded texts
     */
    public function setName(?string $name): static
    {
        $this->__setName($name);

        $renderer_class = Request::getTemplateObject()->getRenderClass($this);

        if ($renderer_class) {
            $this->source = $renderer_class::new($this)->render();

        } else {
            if (Session::isGuest()) {
                $sign_out =  '<p>' . tr('Click :here to sign in', [
                                         ':here' => Anchor::new(Url::new('sign-out'), tr('here'))->setRenderRightsFail(EnumAnchorRenderRightsFail::full)
                                     ]) .
                             '</p>';
            } else {
                $sign_out =  '<p>' . tr('Click :here to sign out', [
                                         ':here' => Anchor::new(Url::new('sign-out'), tr('here'))->setRenderRightsFail(EnumAnchorRenderRightsFail::full)
                                     ]) .
                             '</p>';
            }

            // TODO Get rid of this hard coded stuff
            switch ($this->name) {
                case 'system/http-error':
                    $this->source = ' <body class="hold-transition login-page">
                                          <div class="login-box">
                                              <div class="error-page">
                                                  <h2 class="headline text-warning"> :h2</h2>  
                                                  <div class="error-content">
                                                      <h3><i class="fas fa-exclamation-triangle text-:type"></i> :h3</h3>
    
                                                      <p>:p</p>
                                                      <p>' . tr('Click :here to go to the index page', [':here' => Anchor::new(Url::newCurrentDomainRootUrl(), tr('here'))->setRenderRightsFail(EnumAnchorRenderRightsFail::full)]) . '</p>' .
                                                      $sign_out;

                    if (Session::isUser()) {
                        $this->source .= '    <form class="search-form" method="post" action=":action">
                                                  ' . Csrf::getHiddenElement() . '
                                                  <div class="input-group">
                                                      <input type="text" name="search" class="form-control" placeholder=":search">
                                                      <div class="input-group-append">
                                                          <button type="submit" name="submit" class="btn btn-warning"><i class="fas fa-search"></i>
                                                          </button>
                                                      </div>
                                                  </div>
                                              </form>';
                    }

                    $this->source .= '    </div>
                                          <!-- /.error-content -->
                                      </div>
                                  </div>
                              </body>';
                    break;

                default:
                    throw new OutOfBoundsException(tr('Specified template page ":template" does not exist', [
                        ':template' => $name,
                    ]));
            }
        }

        return $this;
    }
}
