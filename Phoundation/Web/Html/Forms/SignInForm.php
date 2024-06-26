<?php

/**
 * Class SignInForm
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Forms;

use Phoundation\Utils\Config;
use Phoundation\Web\Html\Components\Forms\Form;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Http\Interfaces\UrlBuilderInterface;
use Phoundation\Web\Http\UrlBuilder;
use Stringable;

class SignInForm extends Form
{
    /**
     * The sign in URL
     *
     * @var UrlBuilderInterface|null $sign_in_url
     */
    protected UrlBuilderInterface|null $sign_in_url = null;

    /**
     * The register page URL
     *
     * @var UrlBuilderInterface|null $register_url
     */
    protected UrlBuilderInterface|null $register_url = null;

    /**
     * The forgot password page URL
     *
     * @var UrlBuilderInterface|null $forgot_password_url
     */
    protected UrlBuilderInterface|null $forgot_password_url = null;


    /**
     * SignInForm class constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->setRegisterUrl(Config::get('web.defaults.urls.register', 'register'))
             ->setForgotPasswordUrl(Config::get('web.defaults.urls.forgot-password', 'forgot-password'))
             ->setAction(Config::get('web.defaults.urls.signin', 'signin'))
             ->setMethod(EnumHttpRequestMethod::post);
    }


    /**
     * Returns the sign in URL
     *
     * @return UrlBuilderInterface
     */
    public function getSignInUrl(): UrlBuilderInterface
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
        $this->sign_in_url = ((string) $sign_in_url) ? UrlBuilder::getWww($sign_in_url) : null;

        return $this;
    }


    /**
     * Returns the register URL
     *
     * @return UrlBuilderInterface
     */
    public function getRegisterUrl(): UrlBuilderInterface
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
        $this->register_url = ((string) $register_url) ? UrlBuilder::getWww($register_url) : null;

        return $this;
    }


    /**
     * Returns the register URL
     *
     * @return UrlBuilderInterface
     */
    public function getForgotPasswordUrl(): UrlBuilderInterface
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
        $this->forgot_password_url = ((string) $forgot_password_url) ? UrlBuilder::getWww($forgot_password_url) : null;

        return $this;
    }
}
