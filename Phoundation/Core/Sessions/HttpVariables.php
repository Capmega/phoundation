<?php

declare(strict_types=1);

namespace Phoundation\Core\Sessions;

use Phoundation\Data\Iterator;
use Phoundation\Utils\Config;

/**
 * Class HttpVariables
 *
 * Manage HTTP GET or POST variables
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Core
 */
abstract class HttpVariables extends Iterator
{
    /**
     * Encode the HTTP variables
     *
     * @return void
     */
    abstract static public function encode(): void;


    /**
     * Decode the HTTP variables
     *
     * @return void
     */
    abstract static public function decode(): void;


    /**
     * Decode the HTTP variables
     *
     * @param array $variables
     *
     * @return void
     */
    public static function decodeVariables(array &$variables): void
    {
        if (!Config::getBoolean('www.http.variables.encode', false)) {
            // Don't encode / decode HTTP variables
            return;
        }
        // Copy $variables locally
        $local     = $variables;
        $variables = [];
        // Decode key and copy variables back
        foreach ($local as $key => $value) {
            $variables[$key] = $value;
        }
    }


    /**
     * Encode the HTTP variables
     *
     * @param array $variables
     *
     * @return void
     */
    protected static function encodeVariables(array &$variables): void
    {
        if (!Config::getBoolean('www.http.variables.encode', false)) {
            // Don't encode / decode HTTP variables
            return;
        }
        // Copy $variables locally
        $local     = $variables;
        $variables = [];
        // Encode key and copy variables back
        foreach ($local as $key => $value) {
            $variables[$key] = $value;
        }
    }
}
