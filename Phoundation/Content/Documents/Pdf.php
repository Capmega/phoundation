<?php

/**
 * Class Pdf
 *
 * This class managed PDF files and contains a wide variety of PDF functionalities
 *
 * @see       https://tubitv.com/
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Content
 */


declare(strict_types=1);

namespace Phoundation\Content\Documents;

use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoFiles;


class Pdf extends PhoFile
{
    /**
     * Will split this PDF file by pages
     *
     * @param string|null $file_pattern
     *
     * @return static
     */
    public function split(?string $file_pattern = 'page_%05d.pdf'): static
    {
        $o_file = $this->copy(PhoDirectory::newDataTmpObject());

        return PdfTk::new()
                    ->setPdfFileObject($o_file)
                    ->burst($file_pattern);
    }


    /**
     * Will merge the specified PDF files into this file
     *
     * @param Pdf|null ...$o_pdfs
     * @return static
     */
    public function merge(?Pdf ...$o_pdfs): static
    {
        $o_files = PhoFiles::new();

        foreach ($o_pdfs as $o_pdf) {
            $o_files->add($o_pdf);
        }

        return PdfUnite::new()
                       ->setSourceFilesObject($o_files)
                       ->setTargetFileObject($this)
                       ->execute();
    }
}