<?php

namespace Phoundation\Web\Http\Html\Forms;

use Phoundation\Core\Config;
use Phoundation\Web\Http\Html\Elements\Form;
use Phoundation\Web\Http\Url;



/**
 * AdminLte Plugin SignIn form class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class SignInForm extends Form
{
    /**
     * The signin URL
     *
     * @var string|null
     */
    protected ?string $sign_in_url = null;

    /**
     * The register page URL
     *
     * @var string|null
     */
    protected ?string $register_url = null;

    /**
     * The forgot password page URL
     *
     * @var string|null $forgot_password_url
     */
    protected ?string $forgot_password_url = null;



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
     * Returns the signin URL
     *
     * @return string
     */
    public function getSignInUrl(): string
    {
        return $this->sign_in_url;
    }



    /**
     * Sets the signin URL
     *
     * @param string $sign_in_url
     * @return static
     */
    public function setSignInUrl(string $sign_in_url): static
    {
        $this->sign_in_url = Url::build($sign_in_url)->www();
        return $this;
    }



    /**
     * Returns the register URL
     *
     * @return string
     */
    public function getRegisterUrl(): string
    {
        return $this->register_url;
    }



    /**
     * Sets the register URL
     *
     * @param string $register_url
     * @return static
     */
    public function setRegisterUrl(string $register_url): static
    {
        $this->register_url = Url::build($register_url)->www();
        return $this;
    }



    /**
     * Returns the register URL
     *
     * @return string
     */
    public function getForgotPasswordUrl(): string
    {
        return $this->forgot_password_url;
    }



    /**
     * Sets the register URL
     *
     * @param string $forgot_password_url
     * @return static
     */
    public function setForgotPasswordUrl(string $forgot_password_url): static
    {
        $this->forgot_password_url = Url::build($forgot_password_url)->www();
        return $this;
    }



    /**
     * Render the HTML for this Sign-in form
     *
     * @return string
     */
    public function render(): string
    {
        return parent::render();
    }
}