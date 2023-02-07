<?php

namespace Phoundation\Developer\Versioning\Git;

use Phoundation\Cli\Cli;
use Phoundation\Cli\Color;
use Phoundation\Core\Strings;
use Phoundation\Data\Classes\Iterator;
use Phoundation\Developer\Versioning\Git\Traits\GitProcess;


/**
 * Class StatusFiles
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Developer
 */
class StatusFiles extends Iterator
{
    use GitProcess;



    /**
     * StatusFiles class constructor
     *
     * @param string $path
     */
    public function __construct(string $path)
    {
        $this->setPath($path);
        $this->scanChanges();
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
            ->clearArguments()
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



    /**
     * Display the files status on the CLI
     *
     * @return void
     */
    public function CliDisplayTable(): void
    {
        $list = [];

        foreach ($this->getList() as $file => $status) {
            if (trim(substr($status->getStatus(), 0, 1))) {
                $status = Color::apply($status->getStatus()->getReadable(), 'green');
            } else {
                $status = Color::apply($status->getStatus()->getReadable(), 'red');
            }
            $list[$file] = ['status' => $status];
        }

        Cli::displayTable($list, ['file' => tr('File'), 'status' => tr('Status')], 'file');
    }
}