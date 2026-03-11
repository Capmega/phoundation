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
use Phoundation\Data\Traits\TraitDataDisabled;
use Phoundation\Data\Traits\TraitDataIteratorEnabled;
use Phoundation\Data\Traits\TraitDataIteratorImages;
use Phoundation\Data\Traits\TraitDataIteratorSections;
use Phoundation\Data\Traits\TraitDataIteratorTexts;
use Phoundation\Data\Traits\TraitDataIteratorUrls;
use Phoundation\Data\Traits\TraitDataStringName;
use Phoundation\Data\Traits\TraitDataReadonly;
use Phoundation\Data\Traits\TraitDataStringSource;
use Phoundation\Data\Traits\TraitMethodHasRendered;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Csrf;
use Phoundation\Web\Html\Enums\EnumAnchorRenderRightsFail;
use Phoundation\Web\Html\Pages\Interfaces\TemplateInterface;
use Phoundation\Web\Http\Url;
use Phoundation\Web\Requests\Request;


class Template implements TemplateInterface
{
    use TraitDataStringName {
        setName as protected __setName;
    }
    use TraitDataStringSource;
    use TraitDataIteratorTexts {
        getTextsObject as protected __getTextsObject;
        setTextsObject as protected __setTextsObject;
    }
    use TraitDataIteratorUrls {
        getUrlsObject as protected __getUrlsObject;
        setUrlsObject as protected __setUrlsObject;
    }
    use TraitDataIteratorImages {
        getImagesObject as protected __getImagesObject;
        setImagesObject as protected __setImagesObject;
    }
    use TraitDataIteratorSections {
        getSectionsObject as protected __getSectionsObject;
        setSectionsObject as protected __setSectionsObject;
    }
    use TraitDataIteratorEnabled {
        getEnabledsObject as protected __getEnabledsObject;
        setEnabledsObject as protected __setEnabledsObject;
    }
    use TraitMethodHasRendered;
    use TraitDataReadonly;
    use TraitDataDisabled;


    /**
     * Template class constructor
     *
     * @param string|null $name
     */
    public function __construct(?string $name = null)
    {
        $this->setName($name);
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
     *       with Template AdminLteV3 will be rendered by Templates\AdminLteV3\Html\Components\Input\InputText
     *
     * @return string|null
     * @see  Element::render(), ElementsBlock::render()
     */
    public function render(): ?string
    {
        if ($this->render) {
            return $this->render;
        }

        $renderer_class = Request::getTemplateObject()->getRenderClass($this);

        if ($renderer_class and ($renderer_class !== 'Templates\Phoundation\Mdb\Html\Pages\Template')) {
            return $this->render = $renderer_class::new($this)->render();
        }

        $text = $this->source;

        foreach ($this->__getTextsObject() as $search => $replace) {
            $text = str_replace($search, (string)$replace, $text);
        }

        return $this->render = $text;
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
            case '':
                // No template name specified, this is fine.
                break;

            case 'system/http-error':
                $this->source = '<body>
                                    <div class="container pt-5">

                                      <!-- Section: Design Block -->
                                      <section class="mb-8">
                                        <style>
                                          .rounded-t-2-5 {
                                            border-top-left-radius: 0.75rem;
                                            border-top-right-radius: 0.75rem;
                                          }
                                          @media (min-width: 992px) {
                                            .rounded-tr-lg-0 {
                                              border-top-right-radius: 0;
                                            }
                                            .rounded-bl-lg-2-5 {
                                              border-bottom-left-radius: 0.75rem;
                                            }
                                          }
                                        </style>
                                        <div class="card rounded-6 shadow-3-soft" style="background-color: white">
                                          <div class="row g-0 d-flex align-items-center">
                                            <div class="col-lg-6 col-xl-5">
                                              <img src=":img" alt="' . tr('Background image') . '" class="w-100 rounded-t-2-5 rounded-tr-lg-0 rounded-bl-lg-2-5"/>
                                            </div>
                                            <div class="col-lg-6 col-xl-7">
                                              <div class="card-body py-4 py-md-5 py-lg-4 py-xl-5 px-md-5">
                                                <div class="border-top border-dark" style="width: 100px"></div>
                                                <h2 class="display-4 mt-5 mb-4" style="color: #344e41"><i class="fas fa-exclamation-triangle text-:type"></i> :h2 :h3</h2>
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

                break;

            default:
                throw new OutOfBoundsException(tr('Specified template page ":template" does not exist', [
                    ':template' => $name,
                ]));
        }

        return $this;
    }
}
