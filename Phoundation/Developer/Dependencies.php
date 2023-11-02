<?php

declare(strict_types=1);

namespace Phoundation\Developer;


use Exception;
use Phoundation\Core\Arrays;
use Phoundation\Exception\OutOfBoundsException;


/**
 * Class Dependencies
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Dependencies
 */
class Dependencies
{
    /*
     * Ensure that the specifed library is installed. If not, install it before
     * continuing
     */
    function ensure_installed($params)
    {
        Arrays::ensure($params);

        /*
         * Check if specified library is installed
         */
        if (!isset($params['name'])) {
            throw new OutOfBoundsException(tr('No name specified for library'));
        }

        /*
         * Test available files
         */
        if (isset($params['checks'])) {
            foreach (Arrays::force($params['checks']) as $directory) {
                if (!file_exists($directory)) {
                    $fail = 'path ' . $directory;
                    break;
                }
            }
        }

        /*
         * Test available functions
         */
        if (isset($params['functions']) and !isset($fail)) {
            foreach (Arrays::force($params['functions']) as $function) {
                if (!function_exists($function)) {
                    $fail = 'function ' . $function;
                    break;
                }
            }
        }

        /*
         * Test available functions
         */
        if (isset($params['which']) and !isset($fail)) {
            foreach (Arrays::force($params['which']) as $program) {
                if (!file_which($program)) {
                    $fail = 'which ' . $program;
                    break;
                }
            }
        }

        /*
         * If a test failed, run the installer for this function
         */
        if (!empty($fail)) {
            log_file(tr('Installation test ":test" failed, running installer ":installer"', array(':test' => $fail, ':installer' => $params['callback'])), 'ensure-installed', 'yellow');
            return $params['callback']($params);
        }
    }
}