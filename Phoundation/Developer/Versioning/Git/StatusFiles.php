<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Core\Classes\Iterator;
use Phoundation\Core\Strings;
use Phoundation\Developer\Versioning\Git\Traits\Path;
use Phoundation\Processes\Process;


/**
 * Class StatusFiles
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Versioning
 */
class StatusFiles extends Iterator
{
    use Path;



    /**
     * Changes class constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->setPath($path);
        $this->scanChanges();
    }



    /**
     * Display the files status on the CLI
     *
     * @return void
     */
    public function CliDisplayTable(): void
    {
        $list = [];

        foreach ($this->getList() as $file => $status) {
            $list[$file] = ['status' => $status->];
        }

        Cli::displayTable($list, ['file' => tr('File'), 'status' => tr('Status')], 'file');
    }



    /**
     * Scans for changes
     *
     * @return static
     */
    public function scanChanges(): static
    {
        $this->list = [];

        $files = $this->git
            ->addArgument('status')
            ->addArgument($this->path)
            ->addArgument('--porcelain')
            ->executeReturnArray();

        // Parse output
        foreach ($files as $file) {
            $status = substr($file, 0, 2);
            $file   = substr($file, 3);
            $target = '';

            if (str_contains($file, ' -> ')) {
                $target = Strings::from($file, ' -> ');
                $file   = Strings::until($file, ' -> ');
            }

            $this->list[$file] = StatusFile::new($status, $file, $target);
        }

        return $this;
    }
}