<?php

namespace Phoundation\Web\Http\Html\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Phoundation\Core\Config;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\Url;
use function Phoundation\Web\Http\Html\Elements\str_starts_with;


/**
 * Form class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class Form extends Element
{
    /**
     * The submit method
     *
     * @var string $method
     */
    #[ExpectedValues(values: ["get", "post"])]
    protected string $method = 'post';

    /**
     * The submit target
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
     * Form class constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->setElement('form');
        $this->setAcceptCharset(Config::get('languages.encoding.', 'utf-8'));
    }



    /**
     * Sets the form action
     *
     * @return string|null
     */
    public function getAction(): ?string
    {
        return $this->action;
    }



    /**
     * Sets the form action
     *
     * @param string $action
     * @return static
     */
    public function setAction(string $action): static
    {
        $this->action = Url::build($action)->www();
        return $this;
    }



    /**
     * Sets the form method
     *
     * @return string|null
     */
    public function getMethod(): ?string
    {
        return $this->method;
    }



    /**
     * Sets the form method
     *
     * @param string $method
     * @return static
     */
    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
    }



    /**
     * Sets the form no_validate
     *
     * @return string|null
     */
    public function getNoValidate(): ?string
    {
        return $this->no_validate;
    }



    /**
     * Sets the form no_validate
     *
     * @param string $no_validate
     * @return static
     */
    public function setNoValidate(string $no_validate): static
    {
        $this->no_validate = $no_validate;
        return $this;
    }



    /**
     * Sets the form auto_complete
     *
     * @return string|null
     */
    public function getAutoComplete(): ?string
    {
        return $this->auto_complete;
    }



    /**
     * Sets the form auto_complete
     *
     * @param string $auto_complete
     * @return static
     */
    public function setAutoComplete(string $auto_complete): static
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
     * @return static
     */
    public function setTarget(string $target): static
    {
        if (str_starts_with($target, '_')) {
            switch ($target) {
                case '_parent':
                    // no-break
                case '_blank':
                    // no-break
                case '_self':
                    // no-break
                case '_top':
                    // These are all fine
                    break;

                default:
                    throw new OutOfBoundsException(tr('Unknown form target ":target" specified', [
                        ':target' => $target
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
     *       values that were added as general attributes using Element::addAttribute()
     * @return array
     */
    protected function buildAttributes(): array
    {
        if (!$this->method) {
            throw new OutOfBoundsException(tr('Cannot render form, no "method" specified'));
        }

        if (!$this->action) {
            throw new OutOfBoundsException(tr('Cannot render form, no "action" specified'));
        }

        // These are obligatory
        $return = [
            'action'       => $this->action,
            'method'       => $this->method,
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
        return array_merge(parent::buildAttributes(), $this->attributes, $return);
    }
}