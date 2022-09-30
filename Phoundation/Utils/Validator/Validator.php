<?php

namespace Phoundation\Utils\Validator;

/**
 * Validator class
 *
 * This class validates data from untrusted arrays
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Utils
 */
class Validator
{
    /**
     * Validator constructor.
     *
     * @note Keys that do not exist in $data that are validated will automatically be created
     * @note Keys in $data that are not validated will automatically be removed
     * @param array|null $data The data array that must be validated.
     */
    public function __construct(?array &$data = []) {
        // Ensure we have an array
        if ($data === null) {
            $data = [];
        }

        $this->data = &$data;
    }



    /**
     * Singleton
     */
    public static function getInstance(): Validator
    {
        if (!isset(self::$instance)) {
            $array = [];
            self::$instance = new Validator();
        }

        return self::$instance;
    }
}