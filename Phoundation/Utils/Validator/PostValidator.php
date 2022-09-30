<?php

namespace Phoundation\Utils\Validator;

/**
 * PostValidator class
 *
 * This class validates data from untrusted arrays
 *
 * $_REQUEST will be cleared automatically as this array should not  be used.
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Company\Utils
 */
class PostValidator extends Validator
{
    /**
     * PostValidator constructor.
     *
     * @note This class will purge the $_REQUEST array as this array contains a mix of $_GET and $_POST variables which
     *       should never be used
     */
    public function __construct() {
        $_REQUEST = [];
        parent::__construct($data);
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