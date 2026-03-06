<?php

/**
 * Class PdfTk
 *
 * This class handles the pdftk command and allows a variety of PDF operations
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Processes\Commands;

use Phoundation\Content\Documents\Interfaces\PdfInterface;
use Phoundation\Filesystem\Interfaces\PhoDirectoryInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Os\Processes\Enum\EnumExecuteMethod;


class PdfTk extends Command
{
    /**
     * Tracks the PDF file to use
     *
     * @var PdfInterface
     */
    protected PdfInterface $o_file;


    /**
     * Returns the pdf file object
     *
     * @return PdfInterface
     */
    public function getPdfFileObject(): PdfInterface
    {
        return $this->o_file;
    }


    /**
     * Sets the pdf file object
     *
     * @param PdfInterface $o_file
     *
     * @return static
     */
    public function setPdfFileObject(PdfInterface $o_file): static
    {
        $this->o_file = $o_file;
        return $this;
    }


    /**
     * Will split the specified PDF file by pages
     *
     * @param string|null $file_pattern
     * @param bool        $background
     *
     * @return PhoDirectoryInterface
     */
    public function burst(?string $file_pattern = 'page_%05d.pdf', bool $background = false): PhoDirectoryInterface
    {
        $o_directory = PhoDirectory::new($this->o_file->getWithoutExtension())->ensure();

        if (empty($file_pattern)) {
            $file_pattern = 'page_%02d.pdf';
        }

        $this->clearArguments()
             ->setExecutionDirectory($this->o_file->getParentDirectoryObject())
             ->setCommand('pdftk')
             ->addArguments(['burst', $this->o_file, 'output', $o_directory . $file_pattern])
             ->execute($background ? EnumExecuteMethod::background : EnumExecuteMethod::noReturn);

        return $o_directory;
    }
}
