<?php

/**
 * Class FsMimetype
 *
 * This class represents a single entry in the "filesystem_mimetypes" table, or a single mimetype
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Filesystem
 */


declare(strict_types=1);

namespace Phoundation\Filesystem\Mimetypes;

use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryNameDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryPriority;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Filesystem\Interfaces\FsMimetypeInterface;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Enums\EnumInputType;


class FsMimetype extends DataEntry implements FsMimetypeInterface
{
    use TraitDataEntryNameDescription;
    use TraitDataEntryPriority;


    /**
     * FsMimetype class constructor
     *
     * @param array|int|string|DataEntryInterface|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|int|string|DataEntryInterface|null $identifier = null, ?bool $meta_enabled = null, bool $init = false)
    {
        $this->min_priority = 0;
        $this->max_priority = 5;

        $this->setDefaultPriority(0);

        parent::__construct($identifier, $meta_enabled, $init);
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
    public static function getDataEntryName(): string
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
        return $this->set(Strings::ensureStartsNotWith($extension, '.'), 'extension');
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
    public function write(?string $comments = null): static
    {
        // Force primary and secondary values from the mimetype
        // Data has been validated at this point, we can safely use it.
        $this->setPrimaryPart(Strings::until($this->getMimetype(), '/'));
        $this->setSecondaryPart(Strings::from($this->getMimetype(), '/'));

        return parent::write($comments);
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::newName($this)
                                           ->setOptional(false)
                                           ->setInputType(EnumInputType::name)
                                           ->setSize(6)
                                           ->setMaxlength(128)
                                           ->setHelpText(tr('The name for this mimetype')))

            ->add(DefinitionFactory::newSeoName($this))

            ->add(DefinitionFactory::newCode($this, 'extension')
                                   ->setOptional(false)
                                   ->setInputType(EnumInputType::code)
                                   ->setSize(2)
                                   ->setMaxlength(16)
                                   ->setLabel('Extension')
                                   ->setHelpText(tr('The extension for this mimetype')))

            ->add(DefinitionFactory::newCode($this, 'primary_part')
                                   ->setRender(false)
                                   ->setDisabled(true)
                                   ->setOptional(true)
                                   ->setLabel('Primary part')
                                   ->setMaxlength(32))

            ->add(DefinitionFactory::newCode($this, 'secondary_part')
                                   ->setRender(false)
                                   ->setDisabled(true)
                                   ->setOptional(true)
                                   ->setLabel('Secondary part')
                                   ->setMaxlength(96))

            ->add(DefinitionFactory::newCode($this, 'mimetype')
                                   ->setOptional(false)
                                   ->setInputType(EnumInputType::code)
                                   ->setSize(4)
                                   ->setMaxlength(128)
                                   ->setLabel('Mimetype')
                                   ->setHelpText(tr('The mimetype for this extension'))
                                   ->addValidationFunction(function (ValidatorInterface $validator) {
                                       $validator->matchesRegex('/\w+\/[a-z0-9-.]+/');
                                   }))

            ->add(DefinitionFactory::newNumber($this, 'priority')
                                   ->setOptional(false)
                                   ->setSize(2)
                                   ->setMin(0)
                                   ->setMax(9)
                                   ->setMaxlength(1)
                                   ->setLabel('Priority')
                                   ->setHelpText(tr('The priority for this mimetype / extension')))

            ->add(DefinitionFactory::newDescription($this)
                                   ->setSize(10)
                                   ->setHelpText(tr('The description for this mimetype')));
    }
}
