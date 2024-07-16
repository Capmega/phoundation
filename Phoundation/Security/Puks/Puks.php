<?php

/**
 * Puks class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Security
 */

declare(strict_types=1);

namespace Phoundation\Security\Puks;

use Phoundation\Core\Sessions\Session;
use Phoundation\Puks\Exception\PuksException;
use Phoundation\Utils\Json;

class Puks
{
    /**
     * The key specified by the user
     *
     * @var string $user_key
     */
    protected string $user_key;


    /**
     * Puks class constructor
     *
     * @param string $user_key
     */
    public function __construct(string $user_key)
    {
        if (
            !Session::getUser()
                    ->getId()
        ) {
            throw new PuksException(tr('Puks security is only available for registered users'));
        }
        $this->user_key = $this->encryptKey($user_key);
        // Get rid of the original user key
        $user_key = random_bytes(2048);
        unset($user_key);
    }


    /**
     * @param string      $key
     * @param string|null $encryption_key
     *
     * @return string
     */
    protected function encryptKey(string $key, ?string $encryption_key = null): string
    {
        return $key;
    }


    /**
     * Returns a new Puks type object
     *
     * @param string $user_key
     *
     * @return static
     */
    public static function new(string $user_key): static
    {
        return new static($user_key);
    }


    /**
     * Encrypts and returns the specified data string
     *
     * @param array|string $data
     *
     * @return string
     */
    public function decrypt(array|string $data): string
    {
        $data = Json::encode($data);
        $key  = $this->getKey();

        return $data;
    }


    /**
     * Returns the database stored key
     *
     * @return string|null
     */
    protected function getKey(): ?string
    {
        $key = sql()->getColumn('SELECT `key` FROM `security_puks_keys` WHERE `created_by` = :created_by', [
            ':created_by' => Session::getUser()
                                    ->getId(),
        ]);
        if ($key) {
            return $this->decryptKey($key, $this->user_key);
        }

        return null;
    }


    /**
     * @param string      $data
     * @param string|null $encryption_key
     *
     * @return string
     */
    protected function decryptKey(string $data, ?string $encryption_key = null): string
    {
        return $key;
    }


    /**
     * Encrypts and returns the specified data string
     *
     * @param array|string $data
     *
     * @return string
     */
    public function encrypt(array|string $data): string
    {
        $key = $this->getKey();

        return Json::decode($data);
    }
}
