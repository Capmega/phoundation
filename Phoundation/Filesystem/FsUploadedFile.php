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
                ':e'    => $source->getUploadErrorMessage(),
                ':file' => $source->getError()
            ]));
        }

        // Verify the size and mimetype
        if ($this->getSize() != $source->getSize()) {
            throw new FileUploadException(tr('Upload of file ":file" failed because found file size ":size" does not match indicated file size ":indicated"', [
                ':file'      => $source->getName(),
                ':size'      => $source->getSize(),
                ':indicated' => $this->getSize()
            ]));
        }

        if ($this->getMimetype() != $source->getType()) {
            // Fix the mimetype. If the new filetype is not supported, it will be ignored anyway
            Incident::new()
                    ->setSeverity(EnumSeverity::medium)
                    ->setTitle(tr('Uploaded file ":file" was specified as mimetype ":indicated" but has mimetype ":detected", correcting mimetype to what was detected', [
                        ':file'      => $source->getName(),
                        ':indicated' => $source->getType(),
                        ':detected'  => $this->getMimetype()
                    ]))
                    ->setDetails([
                        'file'      => $source->getName(),
                        'indicated' => $source->getType(),
                        'detected'  => $this->getMimetype()
                    ])
                    ->save();

            // Add the comment to the Upload object too
            $source->addComment($this->getError() . ' / ' . tr('Fixed mimetype from indicated ":indicated" to detected ":detected"', [
                ':indicated' => $source->getType(),
                ':detected'  => $this->getMimetype()
            ]))->save();
        }

        // Move the uploaded file to the Phoundation temporary directory
        $tmp = FsFile::getTemporaryObject(false, $this->real_name);

        Log::action(tr('Moving uploaded file from PHP temporary directory ":tmp" to Phoundation temporary directory ":phoundation"', [
            ':tmp'         => $this->getSource(),
            ':phoundation' => $tmp->getRootname(),
        ]), 3);

        move_uploaded_file((string) $this, (string) $tmp);

        $this->setSource($tmp->getSource());
    }


    /**
     * Returns a new Path object with the specified restrictions
     *
     * @param UploadInterface         $source
     * @param FsRestrictionsInterface $restrictions
     *
     * @return static
     */
    public static function new(UploadInterface $source, FsRestrictionsInterface $restrictions): static
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
}

