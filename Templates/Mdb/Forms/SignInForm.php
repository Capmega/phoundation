<?php

namespace Templates\Mdb\Forms;

use Phoundation\Core\Config;
use Phoundation\Web\Http\Html\Elements\Form;
use Phoundation\Web\Http\Url;



/**
 * MDB Plugin SignIn form class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\Mdb
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
        $this->content = '<!-- Email input -->
                          <div class="form-outline mb-4">
                            <input type="email" id="email" name="email" class="form-control" />
                            <label class="form-label" for="email">' . tr('Email address') . '</label>
                          </div>
                        
                          <!-- Password input -->
                          <div class="form-outline mb-4">
                            <input type="password" id="password" name="password" class="form-control" />
                            <label class="form-label" for="password">' . tr('Password') . '</label>
                          </div>
                        
                          <!-- 2 column grid layout for inline styling -->
                          <div class="row mb-4">
                            <div class="col d-flex justify-content-center">
                              <!-- Checkbox -->
                              <div class="form-check">
                                <input class="form-check-input" type="checkbox" value="1" id="remember_me" name="remember_me" checked />
                                <label class="form-check-label" for="remember_me"> ' . tr('Remember me') . ' </label>
                              </div>
                            </div>
                        
                            <div class="col">
                              <!-- Simple link -->
                              <a href="' . $this->forgot_password_url . '">' . tr('Forgot password?') . '</a>
                            </div>
                          </div>
                        
                          <!-- Submit button -->
                          <button type="submit" class="btn btn-primary btn-block mb-4">' . tr('Sign in') . '</button>
                        
                          <!-- Register buttons -->
                          <div class="text-center">
                            <p>' . tr('Not a member?') . ' <a href="' . $this->register_url . '">' . tr('Register') . '</a></p>
                            <p>' . tr('or sign up with:') . '</p>
                            <button type="button" class="btn btn-primary btn-floating mx-1">
                              <i class="fab fa-facebook-f"></i>
                            </button>
                        
                            <button type="button" class="btn btn-primary btn-floating mx-1">
                              <i class="fab fa-google"></i>
                            </button>
                        
                            <button type="button" class="btn btn-primary btn-floating mx-1">
                              <i class="fab fa-twitter"></i>
                            </button>
                        
                            <button type="button" class="btn btn-primary btn-floating mx-1">
                              <i class="fab fa-github"></i>
                            </button>
                          </div>';

       return parent::render();
    }
}