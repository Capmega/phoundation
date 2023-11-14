<?php

namespace Phoundation\Accounts\Users;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\SignInKeyStatusException;
use Phoundation\Accounts\Users\Exception\SignInKeyUsedException;
use Phoundation\Accounts\Users\Interfaces\SignInKeyInterface;
use Phoundation\Core\Config;
use Phoundation\Core\Core;
use Phoundation\Core\Enums\EnumRequestTypes;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\DataEntryRedirect;
use Phoundation\Data\DataEntry\Traits\DataEntryUser;
use Phoundation\Data\DataEntry\Traits\DataEntryUuid;
use Phoundation\Data\Traits\DataUrl;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Page;
use Phoundation\Web\Routing\Route;
use Stringable;


/**
 * Class SignInKey
 *
 *
 *
 * @see \Phoundation\Core\Libraries\Updates
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Accounts
 */
class SignInKey extends DataEntry implements SignInKeyInterface
{
    use DataUrl;
    use DataEntryUser;
    use DataEntryUuid;
    use DataEntryRedirect;


    /**
     * SignInKey class constructor
     *
     * @param int|string|DataEntryInterface|null $identifier
     * @param string|null $column
     */
    public function __construct(int|string|DataEntryInterface|null $identifier = null, ?string $column = null)
    {
        parent::__construct($identifier, $column);
        $this->setAllowNavigation(false);
    }


    /**
     * Returns the string version for this object
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getUuid();
    }


    /**
     * @inheritDoc
     */
    public static function getTable(): string
    {
        return 'accounts_signin_keys';
    }


    /**
     * @inheritDoc
     */
    public static function getDataEntryName(): string
    {
        return tr('Signin key');
    }


    /**
     * @inheritDoc
     */
    public static function getUniqueField(): ?string
    {
        return 'uuid';
    }


    /**
     * Returns the valid_until for this object
     *
     * @return string|null
     */
    public function getValidUntil(): ?string
    {
        return $this->getSourceFieldValue('int', 'valid_until');
    }


    /**
     * Sets the allow_navigation for this object
     *
     * @param string|null $valid_until
     * @return static
     */
    public function setValidUntil(?string $valid_until): static
    {
        return $this->setSourceValue('valid_until', $valid_until);
    }


    /**
     * Returns the allow_navigation for this object
     *
     * @return bool
     */
    public function getAllowNavigation(): bool
    {
        return (bool) $this->getSourceFieldValue('bool', 'allow_navigation', false);
    }


    /**
     * Sets the allow_navigation for this object
     *
     * @param int|bool|null $allow_navigation
     * @return static
     */
    public function setAllowNavigation(int|bool|null $allow_navigation): static
    {
        return $this->setSourceValue('allow_navigation', (bool) $allow_navigation);
    }


    /**
     * Returns the once for this object
     *
     * @return ?bool
     */
    public function getOnce(): ?bool
    {
        return $this->getSourceFieldValue('bool', 'once', true);
    }


    /**
     * Sets the once for this object
     *
     * @param bool|null $once
     * @return static
     */
    public function setOnce(?bool $once): static
    {
        if ($once !== null) {
            $once = (int) $once;
        }

        return $this->setSourceValue('once', $once);
    }


    /**
     * Generates the requested sign-in key and returns the corresponding UUID
     *
     * @param Stringable|string|null $redirect
     * @return static
     */
    public function generate(Stringable|string|null $redirect): static
    {
        $this->generateUuid();
        $this->setRedirect($redirect);

        // Set up the sign-key URL
        $uuid = $this->getUuid();

        if (!$uuid) {
            throw new OutOfBoundsException(tr('Cannot generate sign in key, no UUID has been generated yet.'));
        }

        $this->setUuid($uuid)->save();

        $url = UrlBuilder::getWww('sign-key');
        $url = str_replace(':key', $uuid, $url);

        $this->url = UrlBuilder::getWww($url);
        return $this;
    }


    /**
     * Apply this sign-in key
     *
     * @return $this
     */
    #[NoReturn] public function execute(): static
    {
        // UUID sign in is only available on the web platform
        if (!PLATFORM_WEB) {
            throw new AuthenticationException(tr('Cannot execute sign in key ":uuid" this is only available on PLATFORM_WEB', [
                ':uuid' => $this->getUuid()
            ]));
        }

        // Keys only work on web pages
        if (!Core::isRequestType(EnumRequestTypes::html)) {
            throw new AuthenticationException(tr('Cannot execute sign in key ":uuid" for HTTP request type ":type" this is only available on web pages', [
                ':type' => Core::getRequestType(),
                ':uuid' => $this->getUuid()
            ]));
        }

        // Execute only once?
        if ($this->hasStatus('executed')) {
            if ($this->getOnce()) {
                throw new SignInKeyUsedException(tr('This link ":uuid" has already been used', [
                    ':uuid' => $this->getUuid()
                ]));
            }

        // No status other than NULL or "executed" allowed!
        } elseif ($this->getStatus()) {
            throw new SignInKeyStatusException(tr('This link has status ":status" and can no longer be used', [
                ':status' => $this->getStatus()
            ]));
        }

        Log::warning(tr('Accepted UUID key ":key" for user ":user"', [
            ':key'  => $this->getUuid(),
            ':user' => $this->getUser()->getLogId()
        ]));

        // Update meta-history and set the status to "executed"
        $this->setStatus('executed');
        $this->addToMetaHistory('executed', tr('The sign in key ":uuid" has been executed', [':uuid' => $this->getUuid()]), [
            ':ip' => Route::getRemoteIp()
        ]);

        Session::signKey($this);

        if ($this->getRedirect()) {
            Page::redirect($this->getRedirect());
        }

        return $this;
    }


    /**
     * Returns true if this object's redirect URL
     *
     * @return bool
     */
    public function signKeyRedirectUrlMatchesCurrentUrl(): bool
    {
        return $this->getRedirect() === (string) UrlBuilder::getCurrent();
    }


    /**
     * Returns true if this object's redirect URL
     *
     * @param Stringable|String $url
     * @param string $target
     * @return bool
     */
    public function signKeyAllowsUrl(Stringable|String $url, string $target): bool
    {
        $url = (string) $url;

        if ($this->getRedirect() === $url) {
            // Redirect URL is always allowed
            return true;
        }

        if ($url === (string) UrlBuilder::getWww('sign-out')) {
            // sign-out page is always allowed
            return true;
        }

        if (!str_starts_with($target, (DIRECTORY_WWW . 'pages/system/'))) {
            // For this URL, we're trying to display a system page instead. Allow too
            return true;
        }

        return $this->getAllowNavigation();
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions
            ->addDefinition(DefinitionFactory::getUsersId($this))
            ->addDefinition(DefinitionFactory::getUuid($this))
            ->addDefinition(DefinitionFactory::getUrl($this, 'redirect'))
            ->addDefinition(DefinitionFactory::getDate($this, 'valid_until')
                ->setLabel(tr('Valid until')))
            ->addDefinition(DefinitionFactory::getBoolean($this, 'allow_navigation')
                ->setLabel(tr('Allow navigation')));
    }
}