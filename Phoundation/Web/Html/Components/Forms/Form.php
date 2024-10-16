<?php

/**
 * Form class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Forms;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Traits\TraitDataRequestMethod;
use Phoundation\Data\Validator\Exception\GetValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Config;
use Phoundation\Web\Html\Components\Element;
use Phoundation\Web\Html\Components\Forms\Interfaces\FormInterface;
use Phoundation\Web\Html\Components\Input\InputCheckbox;
use Phoundation\Web\Html\Csrf;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Http\Url;
use Stringable;


class Form extends Element implements FormInterface
{
    use TraitDataRequestMethod;


    /**
     * The submit page target
     *
     * @var string|null $target
     */
    protected ?string $target = null;

    /**
     * The submit action
     *
     * @var string|null $action
     */
    protected ?string $action = null;

    /**
     * The no validate setting
     *
     * @var bool $no_validate
     */
    protected bool $no_validate = false;

    /**
     * The auto complete setting
     *
     * @var bool $auto_complete
     */
    protected bool $auto_complete = true;

    /**
     * The accepted character set
     *
     * @var string|null $accept_charset
     */
    protected ?string $accept_charset = null;

    /**
     * The relationship between the action and the current document
     *
     * @var string|null $rel
     */
    protected ?string $rel = null;

    /**
     * Tracks if current GET variables should be automatically passed along if the form has a GET method
     *
     * @var bool $auto_pass_get_variables
     */
    protected bool $auto_pass_get_variables = true;


    /**
     * Form class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);

        $this->setRequestMethod(EnumHttpRequestMethod::post)
             ->setElement('form')
             ->setAcceptCharset(Config::get('languages.encoding.', 'utf-8'));
    }


    /**
     * Returns if current GET variables should be automatically passed along if the form has a GET method
     *
     * @return bool
     */
    public function getAutoPassGetVariables(): bool
    {
        return $this->auto_pass_get_variables;
    }


    /**
     * Sets if current GET variables should be automatically passed along if the form has a GET method
     *
     * @param bool $auto_pass_get_variables
     *
     * @return Form
     */
    public function setAutoPassGetVariables(bool $auto_pass_get_variables): static
    {
        $this->auto_pass_get_variables = $auto_pass_get_variables;
        return $this;
    }


    /**
     * Sets the form no_validate
     *
     * @return bool
     */
    public function getNoValidate(): bool
    {
        return $this->no_validate;
    }


    /**
     * Sets the form no_validate
     *
     * @param bool $no_validate
     *
     * @return static
     */
    public function setNoValidate(bool $no_validate): static
    {
        $this->no_validate = $no_validate;

        return $this;
    }


    /**
     * Sets the form auto_complete
     *
     * @return bool
     */
    public function getAutoComplete(): bool
    {
        return $this->auto_complete;
    }


    /**
     * Sets the form auto_complete
     *
     * @param bool $auto_complete
     *
     * @return static
     */
    public function setAutoComplete(bool $auto_complete): static
    {
        $this->auto_complete = $auto_complete;

        return $this;
    }


    /**
     * Sets the form accept_charset
     *
     * @return string|null
     */
    public function getAcceptCharset(): ?string
    {
        return $this->accept_charset;
    }


    /**
     * Sets the form accept_charset
     *
     * @param string $accept_charset
     *
     * @return static
     */
    public function setAcceptCharset(string $accept_charset): static
    {
        $this->accept_charset = $accept_charset;

        return $this;
    }


    /**
     * Sets the form rel
     *
     * @return string|null
     */
    public function getRel(): ?string
    {
        return $this->rel;
    }


    /**
     * Sets the form rel
     *
     * @param string $rel
     *
     * @return static
     */
    public function setRel(string $rel): static
    {
        $this->rel = $rel;

        return $this;
    }


    /**
     * Sets the form target
     *
     * @return string|null
     */
    public function getTarget(): ?string
    {
        return $this->target;
    }


    /**
     * Sets the form target
     *
     * @param string $target
     *
     * @return static
     */
    public function setTarget(string $target): static
    {
        if (str_starts_with($target, '_')) {
            switch ($target) {
                case '_parent':
                    // no break

                case '_blank':
                    // no break

                case '_self':
                    // no break

                case '_top':
                    // These are all fine
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown form target ":target" specified', [
                        ':target' => $target,
                    ]));
            }
        }

        $this->target = $target;

        return $this;
    }


    /**
     * Add the system arguments to the arguments list
     *
     * @note The system attributes (id, name, class, autofocus, readonly, disabled) will overwrite those same
     *       values that were added as general attributes using Element::getAttributes()->add()
     * @return IteratorInterface
     */
    protected function renderAttributesArray(): IteratorInterface
    {
        // These are obligatory
        $return = [
            'action'       => $this->getAction(),
            'method'       => $this->request_method->value,
            'autocomplete' => $this->auto_complete ? 'on' : 'off',
        ];

        if ($this->no_validate) {
            $return['novalidate'] = null;
        }

        if ($this->accept_charset) {
            $return['accept-charset'] = $this->accept_charset;
        }

        if ($this->rel) {
            $return['rel'] = $this->rel;
        }

        // Merge the system values over the set attributes
        return parent::renderAttributesArray()
                     ->appendSource($this->attributes, $return);
    }


    /**
     * Sets the form action.
     *
     * Defaults to the current URL
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action ?? (string) Url::getWww();
    }


    /**
     * Sets the form action
     *
     * @param Stringable|string|null $action
     *
     * @return static
     */
    public function setAction(Stringable|string|null $action): static
    {
        if ($action) {
            $this->action = (string) Url::getWww($action);

        } else {
            $this->action = null;
        }

        return $this;
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        switch ($this->request_method) {
            case EnumHttpRequestMethod::post:
                // Ensure the CSRF variable is injected before rendering.
                $this->content = Csrf::addHiddenElement($this->content);
                return parent::render();

            case EnumHttpRequestMethod::get:
                if ($this->auto_pass_get_variables) {
                    // Automatically add any current GET variables
                    $get  = GetValidator::getBackup();
                    $html = null;

                    foreach ($get as $key => $value) {
                        $html .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
                    }

                    $this->content = $html . $this->content;
                }
        }

        return parent::render();
    }
}
