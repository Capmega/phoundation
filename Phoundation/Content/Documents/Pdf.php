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

use Phoundation\Content\Documents\Exception\PdfPasswordProtectedException;
use Phoundation\Data\Entry;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Data\Traits\TraitDataPassword;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoFiles;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Strings;

class Pdf extends PhoFile
{
    use TraitDataPassword;


    /**
     * This method will return
     *
     * @return int
     */
    public function getPageCount(): int
    {
        return (int) $this->getInfo()->get('pages');
    }


    /**
     * Makes this PDF file password protected
     *
     * @param string $password
     *
     * @return static
     */
    public function addPassword(string $password): static
    {
        //qpdf --password=yourpassword --decrypt input.pdf output.pdf
        return $this;
    }


    /**
     * Removes the password from this PDF file
     *
     * @param string $password
     *
     * @return static
     */
    public function removePassword(string $password): static
    {
        //qpdf --password=yourpassword --decrypt input.pdf output.pdf
        return $this;
    }


    /**
     * Returns true if this PDF file is password protected
     *
     * @return bool
     */
    public function isPasswordProtected(): bool
    {
        try {
            $this->getInfo();

        } catch (PdfPasswordProtectedException) {
            return true;
        }

        return false;
    }


    /**
     * Returns an Entry object with basic information about this PDF document
     *
     * @return EntryInterface
     *
     * @throws ProcessFailedException
     * @throws PdfPasswordProtectedException
     */
    public function getInfo(): EntryInterface
    {
        $this->checkRestrictions(false);

        try {
            $data    = [];
            $results = Process::new()
                              ->setCommand('pdfinfo')
                              ->addArguments($this->source)
                              ->executeReturnArray();

        } catch (ProcessFailedException $e) {
            if ($e->dataContains('incorrect password')) {
                throw new PdfPasswordProtectedException(ts('Cannot process PDF file ":file", the file is password protected and no password was provided', [
                    ':file' => $this->source
                ]));
            }

            throw $e;
        }

        // Parse the command output
        foreach ($results as $line) {
            $data = array_replace($data, Strings::split($line, ':'));
        }

        // Fix data
        if (array_key_exists('file size', $data)) {
            $data['size'] = (int) $data['file size'];
            unset($data['file size']);
        }

        return Entry::new()->setSource($data);
    }


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
