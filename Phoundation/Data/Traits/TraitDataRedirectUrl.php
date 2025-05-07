<?php

/**
 * Trait TraitDataRedirectUrl
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Http\Interfaces\UrlInterface;
use Phoundation\Web\Http\Url;


trait TraitDataRedirectUrl
{
    /**
     * The redirect_url for this object
     *
     * @var string|null $redirect_url
     */
    protected ?string $redirect_url = null;


    /**
     * Returns the redirect_url
     *
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirect_url;
    }


    /**
     * Returns the URL as an object
     *
     * @return UrlInterface|null
     */
    public function getRedirectUrlObject(): ?UrlInterface
    {
        $url = $this->getRedirectUrl();

        if ($url) {
            return Url::new($url);
        }

        return null;
    }


    /**
     * Sets the redirect_url
     *
     * @param UrlInterface|string|null $redirect_url
     *
     * @return static
     */
    public function setRedirectUrl(UrlInterface|string|null $redirect_url): static
    {
        if ($redirect_url instanceof UrlInterface) {
            $redirect_url = $redirect_url->getSource();
        }

        $this->redirect_url = get_null($redirect_url);
        return $this;
    }
}
