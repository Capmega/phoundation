<?php

/**
 * Class State
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\Sessions;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\Sessions\Exception\SessionStateException;
use Phoundation\Accounts\Users\Sessions\Interfaces\SessionStateInterface;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Requests\Request;
use Stringable;


class SessionState implements SessionStateInterface
{
    /**
     * SessionState class constructor
     */
    public function __construct(UserInterface $o_user)
    {
        if (!array_key_exists('state', $_SESSION)) {
            $_SESSION['state'] = Json::decode($o_user->getSessionState());
        }
    }


    /**
     * Returns true if the request URI contains a state variable list
     *
     * @return bool
     */
    public static function requestHasStateVariables(): bool
    {
        return GetValidator::new()->keyExists('state');
    }


    /**
     * Processes state variables, if available
     *
     * @return void
     */
    public static function processRequestStateVariables(): void
    {
        if (!static::requestHasStateVariables()) {
            // Request has no state variables, we are good
            return;
        }
    }


    /**
     * Returns the source for this State object
     *
     * @return array
     */
    public function getSource(): array
    {
        return array_get_safe($_SESSION, 'state', []);
    }


    /**
     * Returns the value for the specified key within the current page or (if not exist) in the alternative pages
     *
     * Returns NULL (or exception, if specified) if the key doesn't exist
     *
     * @param Stringable|string|float|int                    $key
     * @param IteratorInterface|Stringable|array|string|null $pages
     * @param bool                                           $exception
     *
     * @return Stringable|string|float|int
     */
    public function get(Stringable|string|float|int $key, IteratorInterface|Stringable|array|string|null $pages = null, bool $exception = false): Stringable|string|float|int
    {
        // Try to get it for the current page
        $return = $this->getForPage($key, $this->getPage(), $exception);

        if ($return === null) {
            // Try getting it for the alternative page(s)
            foreach ($pages as $page) {
                $return = $this->getForPage($key, $page, $exception);

                if ($return === null) {
                    continue;
                }

                // We found a value, yay!
                break;
            }
        }

        return $return;
    }


    /**
     * Returns the value for the specified state key within the specified page.
     *
     * Returns NULL (or exception, if specified) if the page or key doesn't exist
     *
     * @param Stringable|string|float|int $key
     * @param string                      $page
     * @param bool                        $exception
     *
     * @return string|null
     */
    protected function getForPage(Stringable|string|float|int $key, string $page, bool $exception = false): ?string
    {
        $return = array_get_safe($_SESSION['state'], $page);

        if ($return) {
            $return = array_get_safe($return, $key);

            if ($return) {
                return $return;
            }
        }

        if ($exception) {
            throw new SessionStateException('State key not found');
        }

        return null;
    }


    /**
     * Sets the value for the specified key within the current page or (if not exist) in the alternative pages
     *
     * @param Stringable|string|float|int $value
     * @param Stringable|string|float|int $key
     * @param Stringable|string|null      $page
     *
     * @return static
     */
    public function set(Stringable|string|float|int $value, Stringable|string|float|int $key, Stringable|string|null $page = null): static
    {
        if (empty($page)) {
            $page = $this->getPage();
        }

        // Make sure the current page is registered in the state array
        Arrays::ensure($_SESSION['state'], $page, []);

        $_SESSION['state'][$page][$key] = $value;

        return $this;
    }


    /**
     * Returns a unique page identifier for the current requested page
     *
     * @return string
     */
    public function getPage(): string
    {
        return Strings::from(Request::getExecutedPath(), 'pages/');
    }
}
