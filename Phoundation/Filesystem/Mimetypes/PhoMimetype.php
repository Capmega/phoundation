<?php

/**
 * Class FsMimetype
 *
 * This class represents a single entry in the "filesystem_mimetypes" table, or a single mimetype
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Mimetypes;

use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryPriority;
use Phoundation\Data\Traits\TraitDataObjectPath;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\Interfaces\PhoMimetypeInterface;
use Phoundation\Filesystem\Interfaces\PhoPathInterface;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumInputType;


class PhoMimetype extends DataEntry implements PhoMimetypeInterface
{
    use TraitDataEntryNameDescription;
    use TraitDataEntryPriority;
    use TraitDataObjectPath {
        setPathObject as protected __setPathObject;
    }


    /**
     * FsMimetype class constructor
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = false)
    {
        $this->min_priority = 0;
        $this->max_priority = 5;

        $this->setDefaultPriority(0);

        parent::__construct($identifier);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'filesystem_mimetypes';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getEntryName(): string
    {
        return tr('Mimetype');
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
     * Returns a new mimetype object
     *
     * @param PhoPathInterface $o_path
     *
     * @return static
     */
    public static function newFromPath(PhoPathInterface $o_path): static
    {
        return static::new()->setPathObject($o_path);
    }


    /**
     * Sets the file property and reloads the mimetype data based off the path mimetype
     *
     * @param PhoPathInterface|null $o_path
     *
     * @return static
     */
    public function setPathObject(?PhoPathInterface $o_path): static
    {
        $this->initialize([
            'mimetype'  => $o_path->getMimetype(),
            'extension' => $o_path->getExtension(),
            'priority'  => 0
        ]);

        return $this->__setPath($o_path);
    }


    /**
     * Returns if this mimetype has the specified mimetype text
     *
     * @param string $mimetype
     *
     * @return bool
     */
    public function hasMimetype(string $mimetype): bool
    {
        return $this->getMimetype() === $mimetype;
    }


    /**
     * Returns if this mimetype has the specified extension
     *
     * @param string $extension
     *
     * @return bool
     */
    public function hasExtension(string $extension): bool
    {
        return $this->getExtension() === $extension;
    }


    /**
     * Returns the extension for this object
     *
     * @return string|null
     */
    public function getExtension(): ?string
    {
        return $this->getTypesafe('string', 'extension');
    }


    /**
     * Sets the extension for this object
     *
     * @param string|null $extension
     *
     * @return static
     */
    public function setExtension(?string $extension): static
    {
        return $this->set(Strings::ensureBeginsNotWith($extension, '.'), 'extension');
    }


    /**
     * Returns the mimetype for this object
     *
     * @return string|null
     */
    public function getMimetype(): ?string
    {
        return $this->getTypesafe('string', 'mimetype');
    }


    /**
     * Sets the mimetype for this object
     *
     * @param string|null $mimetype
     *
     * @return static
     */
    public function setMimetype(?string $mimetype): static
    {
        return $this->set($mimetype, 'mimetype');
    }


    /**
     * Returns the primary for this object
     *
     * @return string|null
     */
    public function getPrimaryPart(): ?string
    {
        return $this->getTypesafe('string', 'primary_part');
    }


    /**
     * Sets the primary for this object
     *
     * @param string|null $primary
     *
     * @return static
     */
    public function setPrimaryPart(?string $primary): static
    {
        return $this->set($primary, 'primary_part');
    }


    /**
     * Returns the secondary for this object
     *
     * @return string|null
     */
    public function getSecondaryPart(): ?string
    {
        return $this->getTypesafe('string', 'secondary_part');
    }


    /**
     * Sets the secondary for this object
     *
     * @param string|null $secondary
     *
     * @return static
     */
    public function setSecondaryPart(?string $secondary): static
    {
        return $this->set($secondary, 'secondary_part');
    }


    /**
     * @inheritDoc
     */
    protected function write(bool $force = false, ?string $comments = null): static
    {
        // Force primary and secondary values from the mimetype
        // Data has been validated at this point, we can safely use it.
        $this->setPrimaryPart(Strings::until($this->getMimetype(), '/'));
        $this->setSecondaryPart(Strings::from($this->getMimetype(), '/'));

        return parent::write($force, $comments);
    }


    /**
     * Returns true if this file is a PDF file
     *
     * @return bool
     */
    public function isImage(): bool
    {
        return $this->getPrimaryPart() === 'image';
    }


    /**
     * Returns true if this file is a JPG image file
     *
     * @return bool
     */
    public function isImageJpg(): bool
    {
        return $this->getMimetype() === 'image/jpeg';
    }


    /**
     * Returns true if this file is a PNG image file
     *
     * @return bool
     */
    public function isImagePng(): bool
    {
        return $this->getPrimaryPart() === 'image/png';
    }


    /**
     * Returns true if this file is a GIF image file
     *
     * @return bool
     */
    public function isImageGif(): bool
    {
        return $this->getPrimaryPart() === 'image/gif';
    }


    /**
     * Returns true if this file is a PDF file
     *
     * @return bool
     */
    public function isPdf(): bool
    {
        return $this->getMimetype() === 'application/pdf';
    }


    /**
     * Returns true if this file is an HTML file
     *
     * @return bool
     */
    public function isHtml(): bool
    {
        return $this->getMimetype() === 'text/html';
    }


    /**
     * Returns true if this file is a spreadsheet file
     *
     * @return bool
     */
    public function isSpreadsheet(): bool
    {
        return in_array($this->getMimetype(), [
            'application/x-excel',
            'application/excel',
            'application/x-msexcel',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ]);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return PhoMimetype
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(DefinitionFactory::newName()
                                             ->setOptional(false)
                                             ->setInputType(EnumInputType::name)
                                             ->setSize(6)
                                             ->setMaxLength(128)
                                             ->setHelpText(tr('The name for this mimetype')))

                      ->add(DefinitionFactory::newSeoName())

                    ->add(DefinitionFactory::newCode('extension')
                                           ->setOptional(false)
                                           ->setInputType(EnumInputType::code)
                                           ->setSize(2)
                                           ->setMaxLength(16)
                                           ->setLabel('Extension')
                                           ->setHelpText(tr('The extension for this mimetype')))

                    ->add(DefinitionFactory::newCode('primary_part')
                                           ->setRender(false)
                                           ->setDisabled(true)
                                           ->setOptional(true)
                                           ->setLabel('Primary part')
                                           ->setMaxLength(32))

                    ->add(DefinitionFactory::newCode('secondary_part')
                                           ->setRender(false)
                                           ->setDisabled(true)
                                           ->setOptional(true)
                                           ->setLabel('Secondary part')
                                           ->setMaxLength(96))

                    ->add(DefinitionFactory::newCode('mimetype')
                                           ->setOptional(false)
                                           ->setInputType(EnumInputType::code)
                                           ->setSize(4)
                                           ->setMaxLength(128)
                                           ->setLabel('Mimetype')
                                           ->setHelpText(tr('The mimetype for this extension'))
                                           ->addValidationFunction(function (ValidatorInterface $o_validator) {
                                               $o_validator->matchesRegex('/\w+\/[a-z0-9-.]+/');
                                           }))

                    ->add(DefinitionFactory::newNumber('priority')
                                           ->setOptional(true, 0)
                                           ->setSize(2)
                                           ->setMin(0)
                                           ->setMax(9)
                                           ->setMaxLength(1)
                                           ->setLabel('Priority')
                                           ->setHelpText(tr('The priority for this mimetype / extension')))

                    ->add(DefinitionFactory::newDescription()
                                           ->setSize(10)
                                           ->setHelpText(tr('The description for this mimetype')));

        return $this;
    }
}
