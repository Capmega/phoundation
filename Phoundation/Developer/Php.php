<?php

namespace Phoundation\Debug;

use Phoundation\Core\Arrays;
use Phoundation\Filesystem\Each;
use Phoundation\Filesystem\File;
use Phoundation\Filesystem\Restrictions;



/**
 * Php class
 *
 *
 *
 * @author Sven Oostenbrink <support@capmega.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @category Function reference
 * @package Phoundation\Debug
 */
class Php extends Each
{
    /**
     * Returns development statistics for all files in the specified path
     *
     * @todo TEST
     * @param string|array $paths
     * @param bool $recurse
     * @return array
     */
    public function getStats(string|array $paths, bool $recurse = false): array
    {
        $return = [];
        $statistics = [
            'files' => [
                'css'   => 0,
                'ini'   => 0,
                'js'    => 0,
                'html'  => 0,
                'htm'   => 0,
                'php'   => 0,
                'phps'  => 0,
                'phtml' => 0,
                'yaml'  => 0
            ]
        ];

        foreach (Arrays::force($paths, null) as $path) {
            File::new()->new()->each($path)
                ->setRecurse($recurse)
                ->setWhitelistExtensions(array_keys($statistics['files']))
                ->execute(function(string $file) use (&$statistics) {
                    $data = file($file);
                    $next_comment = false;
                    $extension = $this->file->getExtension($file);

                    $statistics['files'][$extension]++;
                    $statistics['lines'] = 0;

                    // Process file content
                    foreach ($data as $line) {
                        $line = trim($line);
                        $line = strtolower($line);

                        if ($next_comment) {
                            $statistics['comment_lines']++;

                            // End of comment block
                            if (strpos($line, '*/')) {
                                $next_comment = false;
                            }
                        } else {
                            // Class
                            if (str_starts_with($line, 'class')) {
                                $statistics['classes']++;
                            }

                            // Function
                            if (str_starts_with($line, 'function')) {
                                $statistics['functions']++;
                            }

                            // Comment line
                            if (str_starts_with($line, '//')) {
                                $statistics['comments']++;
                            }

                            // Comment block
                            if (str_contains($line, '/*')) {
                                $next_comment = true;
                                $statistics['comment_lines']++;
                                $statistics['comment_blocks']++;
                            }

                            // Blank line
                            if (trim($line) == '') {
                                $statistics['blank_lines']++;
                            }
                        }

                    }

                    $statistics['lines'] += count($data);
                });

            $return = array_merge($return, $statistics);
        }

        return $return;
    }
}