<?php

/**
 * Class FsFile
 *
 * This library contains various filesystem file-related functions
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem;

use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\Exception\FileUploadException;
use Phoundation\Filesystem\Interfaces\FsRestrictionsInterface;
use Phoundation\Filesystem\Interfaces\FsUploadedFileInterface;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Web\Uploads\Interfaces\UploadInterface;


class FsUploadedFile extends FsFileCore implements FsUploadedFileInterface
{
    /**
     * Tracks the file upload error code
     *
     * @var int $error
     */
    protected int $error = 0;

    /**
     * Tracks the real name of the uploaded file
     *
     * @var string $real_name
     */
    protected string $real_name;


    /**
     * TraitPathConstructor class constructor
     *
     * @param UploadInterface              $source
     * @param FsRestrictionsInterface|null $restrictions
     *
     * @throws \Exception
     */
    public function __construct(UploadInterface $source, ?FsRestrictionsInterface $restrictions = null)
    {
        // Uploaded file restrictions always require write access to /tmp/ and DIRECTORY_TMP
        $restrictions = $restrictions ?? new FsRestrictions();
        $restrictions->addDirectory(DIRECTORY_TMP, true);
        $restrictions->addDirectory('/tmp/', true);

        // This uploaded file refers to the PHP temp file
        $this->setSource($source->getTmpName())
             ->setRestrictions($restrictions)
             ->setError($source->getError())
             ->setRealName($source->getName());

        // Process errors
        if ($source->getError()) {
            throw new FileUploadException(tr('Upload of file ":file" failed because ":e"', [
                ':e'    => $this->getUploadErrorMessage($source['error']),
                ':file' => $source['name']
            ]));
        }

        // Verify the size and mimetype
        if ($this->getSize() != $source['size']) {
            throw new FileUploadException(tr('Upload of file ":file" failed because found file size ":size" does not match indicated file size ":indicated"', [
                ':file'      => $source['name'],
                ':size'      => $source['size'],
                ':indicated' => $this->getSize()
            ]));
        }

        if ($this->getMimetype() != $source['type']) {
            // Fix the mimetype. If the new filetype is not supported, it will be ignored anyway
            Incident::new()
                    ->setSeverity(EnumSeverity::medium)
                    ->setTitle(tr('Uploaded file ":file" was specified as mimetype ":indicated" but has mimetype ":detected", correcting mimetype to what was detected', [
                        ':file'      => $source['name'],
                        ':indicated' => $source['type'],
                        ':detected'  => $this->getMimetype()
                    ]))
                    ->save();

            $source->setError($this->getError() . ' / ' . tr('Fixed mimetype from indicated ":indicated" to detected ":detected"', [
                ':indicated' => $source['type'],
                ':detected'  => $this->getMimetype()
            ]));
        }

        // Move the uploaded file to the Phoundation temporary directory
        $tmp = FsFile::getTemporary(false, $this->real_name);

        Log::action(tr('Moving uploaded file from PHP temporary directory ":file" to Phoundation temporary directory ":phoundation"', [
            ':file'        => $tmp,
            ':phoundation' => (string) $this
        ]), 3);

        move_uploaded_file((string) $this, (string) $tmp);

        $this->setSource($tmp->getSource());
    }


    /**
     * Returns a new Path object with the specified restrictions
     *
     * @param array                   $source
     * @param FsRestrictionsInterface $restrictions
     *
     * @return static
     */
    public static function new(array $source, FsRestrictionsInterface $restrictions): static
    {
        return new static($source, $restrictions);
    }


    /**
     * Sets the error code for this uploaded file
     *
     * @param int $error
     *
     * @return static
     */
    protected function setError(int $error): static
    {
        $this->error = $error;
        return $this;
    }


    /**
     * Returns the error code for this file
     *
     * @return int
     */
    public function getError(): int
    {
        return $this->error;
    }


    /**
     * Sets the real_name for this uploaded file
     *
     * @param string $real_name
     *
     * @return static
     */
    protected function setRealName(string $real_name): static
    {
        $this->real_name = $real_name;
        return $this;
    }


    /**
     * Returns the real_name code for this file
     *
     * @return string
     */
    public function getRealName(): string
    {
        return $this->real_name;
    }


    /**
     * Returns the error message for the specified error code
     *
     * @param int $error
     *
     * @return string
     */
    protected function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_OK         => tr('There is no error, the file uploaded with success'),
            UPLOAD_ERR_INI_SIZE   => tr('The uploaded file exceeds the upload_max_filesize directive with value ":value" in php.ini', [
                ':value' => ini_get('upload_max_filesize')
            ]),
            UPLOAD_ERR_FORM_SIZE  => tr('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form'),
            UPLOAD_ERR_PARTIAL    => tr('The uploaded file was only partially uploaded'),
            UPLOAD_ERR_NO_FILE    => tr('No file was uploaded'),
            UPLOAD_ERR_NO_TMP_DIR => tr('Missing a temporary folder'),
            UPLOAD_ERR_CANT_WRITE => tr('Failed to write file to disk'),
            UPLOAD_ERR_EXTENSION  => tr('A PHP extension stopped the file upload. PHP does not provide a way to ascertain which extension caused the file upload to stop; examining the list of loaded extensions with phpinfo() may help')
        };
    }
}

