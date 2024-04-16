<?php

declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use JetBrains\PhpStorm\NoReturn;
use Phoundation\Accounts\Users\Exception\AuthenticationException;
use Phoundation\Accounts\Users\Exception\SignInKeyStatusException;
use Phoundation\Accounts\Users\Exception\SignInKeyUsedException;
use Phoundation\Accounts\Users\Interfaces\SignInKeyInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryRedirect;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUser;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUuid;
use Phoundation\Data\Traits\TraitDataUrl;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Http\UrlBuilder;
use Phoundation\Web\Requests\Enums\EnumRequestTypes;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Requests\Response;
use Phoundation\Web\Routing\Route;
use Stringable;

/**
 * Class SignInKey
 *
 *
 *
 * @see       \Phoundation\Core\Libraries\Updates
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */
class SignInKey extends DataEntry implements SignInKeyInterface
{
    use TraitDataUrl;
    use TraitDataEntryUser;
    use TraitDataEntryUuid;
    use TraitDataEntryRedirect;

    /**
     * SignInKey class constructor
     *
     * @param int|string|DataEntryInterface|null $identifier
     * @param string|null                        $column
     * @param bool|null                          $meta_enabled
     */
    public function __construct(DataEntryInterface|string|int|null $identifier = null, ?string $column = null, ?bool $meta_enabled = null)
    {
        parent::__construct($identifier, $column, $meta_enabled);
        $this->setAllowNavigation(false);
    }


    /**
     * Sets the allow_navigation for this object
     *
     * @param int|bool|null $allow_navigation
     *
     * @return static
     */
    public function setAllowNavigation(int|bool|null $allow_navigation): static
    {
        return $this->set('allow_navigation', (bool) $allow_navigation);
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
    public static function getUniqueColumn(): ?string
    {
        return 'uuid';
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
     * Returns the valid_until for this object
     *
     * @return string|null
     */
    public function getValidUntil(): ?string
    {
        return $this->getValueTypesafe('int', 'valid_until');
    }


    /**
     * Sets the allow_navigation for this object
     *
     * @param string|null $valid_until
     *
     * @return static
     */
    public function setValidUntil(?string $valid_until): static
    {
        return $this->set('valid_until', $valid_until);
    }


    /**
     * Sets the once for this object
     *
     * @param bool|null $once
     *
     * @return static
     */
    public function setOnce(?bool $once): static
    {
        if ($once !== null) {
            $once = (int) $once;
        }

        return $this->set('once', $once);
    }


    /**
     * Generates the requested sign-in key and returns the corresponding UUID
     *
     * @param Stringable|string|null $redirect
     *
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
        $this->setUuid($uuid)
             ->save();
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
                ':uuid' => $this->getUuid(),
            ]));
        }
        // Keys only work on web pages
        if (!Request::isRequestType(EnumRequestTypes::html)) {
            throw new AuthenticationException(tr('Cannot execute sign in key ":uuid" for HTTP request type ":type" this is only available on web pages', [
                ':type' => Request::getRequestType(),
                ':uuid' => $this->getUuid(),
            ]));
        }
        // Execute only once?
        if ($this->hasStatus('executed')) {
            if ($this->getOnce()) {
                throw new SignInKeyUsedException(tr('This link ":uuid" has already been used', [
                    ':uuid' => $this->getUuid(),
                ]));
            }

            // No status other than NULL or "executed" allowed!
        } elseif ($this->getStatus()) {
            throw new SignInKeyStatusException(tr('This link has status ":status" and can no longer be used', [
                ':status' => $this->getStatus(),
            ]));
        }
        Log::warning(tr('Accepted UUID key ":key" for user ":user"', [
            ':key'  => $this->getUuid(),
            ':user' => $this->getUser()
                            ->getLogId(),
        ]));
        // Update meta-history and set the status to "executed"
        $this->setStatus('executed');
        $this->addToMetaHistory('executed', tr('The sign in key ":uuid" has been executed', [':uuid' => $this->getUuid()]), [
            ':ip' => Route::getRemoteIp(),
        ]);
        Session::signKey($this);
        if ($this->getRedirect()) {
            Response::redirect($this->getRedirect());
        }

        return $this;
    }


    /**
     * Returns the once for this object
     *
     * @return ?bool
     */
    public function getOnce(): ?bool
    {
        return $this->getValueTypesafe('bool', 'once', true);
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
     * @param string            $target
     *
     * @return bool
     */
    public function signKeyAllowsUrl(Stringable|string $url, string $target): bool
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
        if (!str_starts_with($target, (DIRECTORY_WEB . 'pages/system/'))) {
            // For this URL, we're trying to display a system page instead. Allow too
            return true;
        }

        return $this->getAllowNavigation();
    }


    /**
     * Returns the allow_navigation for this object
     *
     * @return bool
     */
    public function getAllowNavigation(): bool
    {
        return (bool) $this->getValueTypesafe('bool', 'allow_navigation', false);
    }


    /**
     * @inheritDoc
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::getUsersId($this))
                    ->add(DefinitionFactory::getUuid($this))
                    ->add(DefinitionFactory::getUrl($this, 'redirect'))
                    ->add(DefinitionFactory::getDate($this, 'valid_until')
                                           ->setLabel(tr('Valid until')))
                    ->add(DefinitionFactory::getBoolean($this, 'allow_navigation')
                                           ->setLabel(tr('Allow navigation')));
    }
}
