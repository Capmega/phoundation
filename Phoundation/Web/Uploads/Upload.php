<?php

/**
 * Class Upload
 *
 * This class represents a single entry in the web_uploads table, a single uploaded file with its status.
 *
 * This class does not contain any information about what happened with this file, it only registers basic data about
 * the file, and its upload error code (0 on success). Any file that could have been uploaded should link to the file in
 * this table
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Uploads;

use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Security\Incidents\EnumSeverity;
use Phoundation\Security\Incidents\Incident;
use Phoundation\Utils\Config;
use Phoundation\Utils\Numbers;
use Phoundation\Web\Uploads\Interfaces\UploadInterface;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat\Wizard\Number;


class Upload extends DataEntry implements UploadInterface
{
    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'web_uploads';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Uploaded file');
    }


    /**
     * Returns the field that is unique for this object
     *
     * @return string|null
     */
    public static function getUniqueColumn(): ?string
    {
        return null;
    }


    /**
     * Returns the name for this uploaded file
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->getTypesafe('string', 'name');
    }


    /**
     * Sets the name for this uploaded file
     *
     * @param string|null $name
     *
     * @return static
     */
    protected function setName(?string $name): static
    {
        return $this->set($name, 'name');
    }


    /**
     * Returns the full path for this uploaded file
     *
     * @return string|null
     */
    public function getFullPath(): ?string
    {
        return $this->getTypesafe('string', 'full_path');
    }


    /**
     * Sets the full path for this uploaded file
     *
     * @param string|null $full_path
     *
     * @return static
     */
    protected function setFullPath(?string $full_path): static
    {
        return $this->set($full_path, 'full_path');
    }


    /**
     * Returns the tmp_name (The local file name in /tmp when it was uploaded to PHP) for this uploaded file
     *
     * @return string|null
     */
    public function getTmpName(): ?string
    {
        return $this->getTypesafe('string', 'tmp_name');
    }


    /**
     * Sets the tmp_name (The local file name in /tmp when it was uploaded to PHP) for this uploaded file
     *
     * @param string|null $tmp_name
     *
     * @return static
     */
    protected function setTmpName(?string $tmp_name): static
    {
        return $this->set($tmp_name, 'tmp_name');
    }


    /**
     * Returns the type for this uploaded file
     *
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->getTypesafe('string', 'type');
    }


    /**
     * Sets the type for this uploaded file
     *
     * @param string|null $type
     *
     * @return static
     */
    protected function setType(?string $type): static
    {
        return $this->set($type, 'type');
    }


    /**
     * Returns the size for this uploaded file
     *
     * @return int|null
     */
    public function getSize(): ?int
    {
        return $this->getTypesafe('int', 'size');
    }


    /**
     * Sets the size for this uploaded file
     *
     * @param int|null $size
     *
     * @return static
     */
    protected function setSize(?int $size): static
    {
        return $this->set($size, 'size');
    }


    /**
     * Returns the error_code for this uploaded file
     *
     * @return int|null
     */
    public function getError(): ?int
    {
        return $this->getTypesafe('int', 'error');
    }


    /**
     * Sets the error_code for this uploaded file
     *
     * @param int|null $error_code
     *
     * @return static
     */
    protected function setError(?int $error_code): static
    {
        return $this->set($error_code, 'error');
    }


    /**
     * Returns the hash for this uploaded file
     *
     * @return string|null
     */
    public function getHash(): ?string
    {
        return $this->getTypesafe('string', 'hash');
    }


    /**
     * Sets the hash for this uploaded file
     *
     * @param string|null $hash
     *
     * @return static
     */
    protected function setHash(?string $hash): static
    {
        return $this->set($hash, 'hash');
    }


    /**
     * Returns the Comments for this uploaded file
     *
     * @return string|null
     */
    public function getComments(): ?string
    {
        return $this->getTypesafe('string', 'Comments');
    }


    /**
     * Sets the Comments for this uploaded file
     *
     * @param string|null $comments
     *
     * @return static
     */
    protected function addComments(?string $comments): static
    {
        $existing = $this->getComments();

        if ($existing) {
            return $this->set($comments, 'Comments');
        }

        return $this->set($comments, 'Comments');
    }


    /**
     * Sets the Comments for this uploaded file
     *
     * @param string|null $comments
     *
     * @return static
     */
    protected function setComments(?string $comments): static
    {
        return $this->set($comments, 'Comments');
    }


    /**
     * Generates and sets the hash for this uploaded file
     *
     * @return static
     */
    protected function generateHash(): static
    {
        $configured = Config::getString('web.uploads.hash.max-filesize', ini_get('upload_max_filesize'));
        $max_size   = Numbers::fromBytes($configured);

        if ($this->getSize() < $max_size) {
            return $this->setHash(sha1_file($this->getTmpName()));
        }

        Incident::new()
                ->setSeverity(EnumSeverity::medium)
                ->setTitle(tr('Did not generate hash for file ":file", the file size ":size" is larger than the configured maximum size ":max" for hashing', [
                    ':file' => $this->getTmpName(),
                    ':size' => $this->getSize(),
                    ':max'  => $max_size,
                ]))
                ->setDetails([
                    'max_configured' => $configured,
                    'max_bytes'      => $max_size,
                    'file'           => $this->source
                ])
                ->save();

        return $this;
    }


    /**
     * If we have a tmp_name, we must have a hash!
     *
     * @param bool        $force
     * @param string|null $comments
     *
     * @return $this
     */
    public function save(bool $force = false, ?string $comments = null): static
    {
        if ($this->getTmpName() and !$this->getHash()) {
            $this->generateHash();
        }

        return parent::save($force, $comments);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::getFilename($this, 'name')
                                           ->setLabel(tr('File name'))
                                           ->setMaxlength(2048)
                                           ->setReadonly(true))

                    ->add(DefinitionFactory::getFilename($this, 'full_path')
                                           ->setLabel(tr('Full file path'))
                                           ->setMaxlength(2048)
                                           ->setReadonly(true))

                    ->add(DefinitionFactory::getFile($this, new FsDirectory('/tmp/', FsRestrictions::getWritable('/tmp/')), 'tmp_name')
                                           ->setLabel(tr('Temporary file name'))
                                           ->setMaxlength(255)
                                           ->setReadonly(true))

                    ->add(DefinitionFactory::getCode($this, 'type')
                                           ->setLabel(tr('File mimetype'))
                                           ->setMaxlength(128)
                                           ->setReadonly(true))

                    ->add(DefinitionFactory::getNumber($this, 'size')
                                           ->setLabel(tr('File size'))
                                           ->setMin(0)
                                           ->setReadonly(true))

                    ->add(DefinitionFactory::getNumber($this, 'error')
                                           ->setLabel(tr('Upload error code'))
                                           ->setMin(0)
                                           ->setMax(8)
                                           ->setReadonly(true))

                    ->add(DefinitionFactory::getCode($this, 'hash')
                                           ->setLabel(tr('File hash'))
                                           ->setMaxlength(128)
                                           ->setReadonly(true));
    }
}