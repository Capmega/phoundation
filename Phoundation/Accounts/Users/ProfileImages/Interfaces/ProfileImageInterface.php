<?php

namespace Phoundation\Accounts\Users\ProfileImages\Interfaces;

use Phoundation\Accounts\Users\ProfileImages\ProfileImage;
use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Web\Html\Components\Interfaces\ImgInterface;

interface ProfileImageInterface
{
    /**
     * Returns true if this profile image is the default image
     *
     * @return bool
     */
    public function getDefault(): bool;


    /**
     * @return static
     */
    public function setDefault(): static;


    /**
     * Returns the file for this profile image
     *
     * @return ImageFileInterface
     */
    public function getImageFileObject(): ImageFileInterface;


    /**
     * Sets the file for this profile image
     *
     * @param ImageFileInterface $file
     *
     * @return static
     */
    public function setImageFileObject(ImageFileInterface $file): static;


    /**
     * Returns the img for this profile image
     *
     * @return ImgInterface
     */
    public function getHtmlImgObject(): ImgInterface;


    /**
     * Sets the img for this profile image
     *
     * @param ImgInterface $img
     *
     * @return static
     */
    public function setHtmlImgObject(ImgInterface $img): static;
}
