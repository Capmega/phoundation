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
use Phoundation\Content\Documents\Interfaces\PdfInterface;
use Phoundation\Data\Entry;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Data\Traits\TraitDataPassword;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoFiles;
use Phoundation\Os\Processes\Commands\PdfTk;
use Phoundation\Os\Processes\Exception\ProcessFailedException;
use Phoundation\Os\Processes\Process;
use Phoundation\Utils\Strings;


class Pdf extends PhoFile implements PdfInterface
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
     * @param string       $owner_password        The password to encrypt the file with
     * @param string       $user_password         The password to encrypt the file with
     * @param PhoFile|null $o_output_file  [null] The output file. If not specified, this method will encrypt the file itself
     *
     * @return static
     */
    public function addPassword(string $owner_password, string $user_password, ?PhoFile $o_output_file = null): static
    {
        // qpdf --encrypt OWNER_PASSWORD USER_PASSWORD 256 -- input.pdf output.pdf
        Process::new()->setCommand('qpdf')
                      ->appendArguments(['--encrypt', $owner_password, $user_password])
                      ->appendArguments([$this->getSource(), $o_output_file?->getSource() ?? $this->getSource()]);

        return $this;
    }


    /**
     * Removes the password from this PDF file
     *
     * @param string       $password      The password to encrypt the file with
     * @param PhoFile|null $o_output_file The output file. If not specified, this method will decrypt the file itself
     *
     * @return static
     */
    public function removePassword(string $password, ?PhoFile $o_output_file = null): static
    {
        Process::new()->setCommand('qpdf')
                      ->appendArguments(['--password=' . $password])
                      ->appendArguments(['--decrypt', $this->getSource(), $o_output_file?->getSource() ?? $this->getSource()]);

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
                              ->appendArguments($this->source)
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
        $_file = $this->copy(PhoDirectory::newDataTmp());

        return PdfTk::new()
                    ->setPdfFileObject($_file)
                    ->burst($file_pattern);
    }


    /**
     * Will merge the specified PDF files into this file
     *
     * @param Pdf|null ...$_pdfs
     * @return static
     */
    public function merge(?Pdf ...$_pdfs): static
    {
        $_files = PhoFiles::new();

        foreach ($_pdfs as $_pdf) {
            $_files->add($_pdf);
        }

        return PdfUnite::new()
                       ->setSourceFilesObject($_files)
                       ->setTargetFileObject($this)
                       ->execute();
    }
}
