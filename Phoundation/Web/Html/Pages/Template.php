<?php

/**
 * Class Template
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Templates
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Pages;

use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Iterator;
use Phoundation\Data\Traits\TraitDataIteratorSource;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Pages\Interfaces\TemplateInterface;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Request;

class Template implements TemplateInterface
{
    use TraitDataIteratorSource;


    /**
     * The template text
     *
     * @var string|null $text
     */
    protected ?string $text = null;

    /**
     * The template page to display
     *
     * @var string $page
     */
    protected string $page;


    /**
     * Template class constructor
     *
     * @param string $page
     */
    public function __construct(string $page)
    {
        $this->setPage($page);
        $this->source = new Iterator();
    }


    /**
     * Returns a new Template page object
     *
     * @param string $page
     * @return TemplateInterface
     */
    public static function new(string $page): TemplateInterface
    {
        return new static($page);
    }


    /**
     * Returns the template text
     *
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }


    /**
     * Set the template text
     *
     * @param string|null $text
     * @return static
     */
    public function setText(?string $text): static
    {
        $this->text = $text;
        return $this;
    }


    /**
     * Renders and returns the HTML for this object using the template renderer if available
     *
     * @note Templates work as follows: Any component that renders HTML must be in a Html/ directory, either in a
     *       Phoundation library, or in a Plugins library. The path of the component, starting from Html/ is the path
     *       that this method will search for in the Template. If the same path section is found then that file will
     *       render the HTML for the component. For example: Plugins\Example\Section\Html\Components\Input\InputText
     *       with Template AdminLte will be rendered by Templates\AdminLte\Html\Components\Input\InputText
     *
     * @return string|null
     * @see Element::render(), ElementsBlock::render()
     */
    public function render(): ?string
    {
        $text = $this->text;

        foreach ($this->source as $search => $replace) {
            $text = str_replace($search, (string) $replace, $text);
        }

        return $text;
    }


    /**
     * Returns the template page to use
     *
     * @return string|null
     */
    public function getPage(): ?string
    {
        return $this->page;
    }


    /**
     * Sets the template page to use
     *
     * @todo Implement! For now this just returns hard coded texts
     * @param string|null $page
     * @return static
     */
    public function setPage(?string $page): static
    {
        $this->page      = $page;
        $renderer_class  = Request::getTemplate()->getRendererClass($this);

        if ($renderer_class) {
            $this->text = $renderer_class::new($this)->render();

        } else {
            switch ($this->page) {
                case 'system/http-error':
                    $this->text = ' <body class="hold-transition login-page">
                                    <div class="login-box">
                                        <div class="error-page">
                                            <h2 class="headline text-warning"> :h2</h2>

                                            <div class="error-content">
                                                <h3><i class="fas fa-exclamation-triangle text-:type"></i> :h3</h3>

                                                <p>:p</p>
                                                <p>' . tr('Click :here to go to the index page', [':here' => '<a href="' . UrlBuilder::getCurrentDomainRootUrl() . '">here</a>']) . '</p>
                                                <p>' . tr('Click :here to sign out', [':here' => '<a href="' . UrlBuilder::getWww('sign-out') . '">here</a>']) . '</p>';

                    if (!Session::getUser()->isGuest()) {
                        $this->text .= '        <form class="search-form" method="post" action=":action">
                                                <div class="input-group">
                                                    <input type="text" name="search" class="form-control" placeholder=":search">
                                                    <div class="input-group-append">
                                                        <button type="submit" name="submit" class="btn btn-warning"><i class="fas fa-search"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>';
                    }

                    $this->text .= '        </div>
                                <!-- /.error-content -->
                                    </div>
                                </div>
                            </body>';
                    break;

                default:
                    throw new OutOfBoundsException(tr('Specified template page ":template" does not exist', [
                        ':template' => $page
                    ]));
            }
        }

        return $this;
    }
}
