<?php

namespace Phoundation\Filesystem\Interfaces;

use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;


interface FsMimetypeInterface extends DataEntryInterface
{
    /**
     * Returns the extension for this object
     *
     * @return string|null
     */
    public function getExtension(): ?string;


    /**
     * Sets the extension for this object
     *
     * @param string|null $extension
     *
     * @return static
     */
    public function setExtension(?string $extension): static;


    /**
     * Returns the mimetype for this object
     *
     * @return string|null
     */
    public function getMimetype(): ?string;


    /**
     * Sets the mimetype for this object
     *
     * @param string|null $mimetype
     *
     * @return static
     */
    public function setMimetype(?string $mimetype): static;


    /**
     * Returns the primary for this object
     *
     * @return string|null
     */
    public function getPrimaryPart(): ?string;


    /**
     * Sets the primary for this object
     *
     * @param string|null $primary
     *
     * @return static
     */
    public function setPrimaryPart(?string $primary): static;


    /**
     * Returns the secondary for this object
     *
     * @return string|null
     */
    public function getSecondaryPart(): ?string;


    /**
     * Sets the secondary for this object
     *
     * @param string|null $secondary
     *
     * @return static
     */
    public function setSecondaryPart(?string $secondary): static;


    /**
     * @inheritDoc
     */
    public function write(?string $comments = null): static;

    /**
     * Returns if this mimetype has the specified extension
     *
     * @param string $extension
     *
     * @return bool
     */
    public function hasExtension(string $extension): bool;
}
