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

namespace Phoundation\Content\Documents\Interfaces;

use Phoundation\Content\Documents\Exception\PdfPasswordProtectedException;
use Phoundation\Content\Documents\Pdf;
use Phoundation\Data\Interfaces\EntryInterface;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Os\Processes\Exception\ProcessFailedException;

interface PdfInterface
{
    /**
     * This method will return
     *
     * @return int
     */
    public function getPageCount(): int;


    /**
     * Makes this PDF file password protected
     *
     * @param string       $owner_password        The password to encrypt the file with
     * @param string       $user_password         The password to encrypt the file with
     * @param PhoFile|null $o_output_file  [null] The output file. If not specified, this method will encrypt the file itself
     *
     * @return static
     */
    public function addPassword(string $owner_password, string $user_password, ?PhoFile $o_output_file = null): static;


    /**
     * Removes the password from this PDF file
     *
     * @param string       $password      The password to encrypt the file with
     * @param PhoFile|null $o_output_file The output file. If not specified, this method will decrypt the file itself
     *
     * @return static
     */
    public function removePassword(string $password, ?PhoFile $o_output_file = null): static;


    /**
     * Returns true if this PDF file is password protected
     *
     * @return bool
     */
    public function isPasswordProtected(): bool;


    /**
     * Returns an Entry object with basic information about this PDF document
     *
     * @return EntryInterface
     *
     * @throws ProcessFailedException
     * @throws PdfPasswordProtectedException
     */
    public function getInfo(): EntryInterface;


    /**
     * Will split this PDF file by pages
     *
     * @param string|null $file_pattern
     *
     * @return static
     */
    public function split(?string $file_pattern = 'page_%05d.pdf'): static;


    /**
     * Will merge the specified PDF files into this file
     *
     * @param Pdf|null ...$o_pdfs
     *
     * @return static
     */
    public function merge(?Pdf ...$o_pdfs): static;
}
