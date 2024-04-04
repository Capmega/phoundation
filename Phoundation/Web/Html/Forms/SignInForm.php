<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Forms;

use Phoundation\Utils\Config;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;


/**
 * SignIn form class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class SignInForm extends Form
{
    /**
     * The sign in URL
     *
     * @var Stringable|string|null $sign_in_url
     */
    protected Stringable|string|null $sign_in_url = null;

    /**
     * The register page URL
     *
     * @var Stringable|string|null $register_url
     */
    protected Stringable|string|null $register_url = null;

    /**
     * The forgot password page URL
     *
     * @var Stringable|string|null $forgot_password_url
     */
    protected Stringable|string|null $forgot_password_url = null;


    /**
     * Signin class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setRegisterUrl(Config::get('web.defaults.urls.register', 'register'));
        $this->setForgotPasswordUrl(Config::get('web.defaults.urls.forgot-password', 'forgot-password'));
        $this->setAction(Config::get('web.defaults.urls.signin', 'signin'));
        $this->setMethod('post');
    }


    /**
     * Returns the sign in URL
     *
     * @return Stringable|string
     */
    public function getSignInUrl(): Stringable|string
    {
        return $this->sign_in_url;
    }


    /**
     * Sets the signin URL
     *
     * @param Stringable|string $sign_in_url
     *
     * @return static
     */
    public function setSignInUrl(Stringable|string $sign_in_url): static
    {
        $this->sign_in_url = UrlBuilder::getWww($sign_in_url);
        return $this;
    }


    /**
     * Returns the register URL
     *
     * @return Stringable|string
     */
    public function getRegisterUrl(): Stringable|string
    {
        return $this->register_url;
    }


    /**
     * Sets the register URL
     *
     * @param Stringable|string $register_url
     *
     * @return static
     */
    public function setRegisterUrl(Stringable|string $register_url): static
    {
        $this->register_url = UrlBuilder::getWww($register_url);
        return $this;
    }


    /**
     * Returns the register URL
     *
     * @return string
     */
    public function getForgotPasswordUrl(): Stringable|string
    {
        return $this->forgot_password_url;
    }


    /**
     * Sets the register URL
     *
     * @param Stringable|string $forgot_password_url
     *
     * @return static
     */
    public function setForgotPasswordUrl(Stringable|string $forgot_password_url): static
    {
        $this->forgot_password_url = UrlBuilder::getWww($forgot_password_url);
        return $this;
    }
}