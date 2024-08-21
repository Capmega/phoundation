<?php

/**
 * Class ProfileImage
 *
 * This class represents a single entry from the accounts_profile_images table, or a single profile image for a user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\ProfileImages;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\ProfileImages\Interfaces\ProfileImageInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Content\Images\ImageFile;
use Phoundation\Content\Images\Interfaces\ImageFileInterface;
use Phoundation\Core\Log\Log;
use Phoundation\Data\DataEntry\DataEntry;
use Phoundation\Data\DataEntry\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntry\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntry\Interfaces\DataEntryInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryFile;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUsersId;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\FsDirectory;
use Phoundation\Filesystem\FsRestrictions;
use Phoundation\Filesystem\Interfaces\FsFileInterface;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Interfaces\ImgInterface;


class ProfileImage extends DataEntry implements ProfileImageInterface
{
    use TraitDataEntryDescription;
    use TraitDataEntryFile {
        setFile as protected __setFile;
    }
    use TraitDataEntryUsersId {
        setUsersId as protected __setUsersId;
    }


    /**
     * ProfileImage class constructor
     *
     * @param array|DataEntryInterface|string|int|null $identifier
     * @param bool|null                                $meta_enabled
     * @param bool                                     $init
     */
    public function __construct(array|DataEntryInterface|string|int|null $identifier = null, ?bool $meta_enabled = null, bool $init = true)
    {
        parent::__construct($identifier, $meta_enabled, $init);

        $this->restrictions = FsRestrictions::getWritable([DIRECTORY_TMP, DIRECTORY_CDN]);
    }


    /**
     * Returns a new ProfileImage object from the specified ImageFileInterface object
     *
     * @param ImageFileInterface $source
     *
     * @return static
     */
    public static function newFromImageFile(ImageFileInterface $source): static
    {
        return static::new()->__setFile($source);
    }


    /**
     * Returns the table name used by this object
     *
     * @return string|null
     */
    public static function getTable(): ?string
    {
        return 'accounts_profile_images';
    }


    /**
     * Returns the name of this DataEntry class
     *
     * @return string
     */
    public static function getDataEntryName(): string
    {
        return tr('Profile image');
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
     * Returns true if this profile image is the default image
     *
     * @return bool
     */
    public function getDefault(): bool
    {
        if (!$this->getUserObject()) {
            throw new OutOfBoundsException(tr('Cannot determine if profile image ":image" is a default profile image, it has not been assigned to any user', [
                ':image' => $this->getSource()
            ]));
        }

        if ($this->getFile()) {
            // This profile image object can ony be the default if it has a file
            return $this->getUserObject()->getProfileImageObject() === $this->getFile();
        }

        return false;
    }


    /**
     * Makes this profile image is the default image for this user
     *
     * @return static
     */
    public function setDefault(): static
    {
        if (!$this->getUserObject()) {
            throw new OutOfBoundsException(tr('Cannot make profile image ":image" default, it has not been assigned to any user', [
                ':image' => $this->getSource()
            ]));
        }

        Log::action(tr('Setting image ":file" as default profile image for user ":user"', [
            ':file' => $this->getFile()->getRootname(),
            ':user' => $this->getUserObject()->getLogId()
        ]));

        $this->getUserObject()->setProfileImageObject($this)->save();
        return $this;
    }


    /**
     * Returns the file for this profile image
     *
     * @return ImageFileInterface
     */
    public function getImageFileObject(): ImageFileInterface
    {
        return new ImageFile($this->getFile());
    }


    /**
     * Sets the file for this profile image
     *
     * @param ImageFileInterface $file
     *
     * @return static
     */
    public function setImageFileObject(ImageFileInterface $file): static
    {
        return $this->setFile($file);
    }


    /**
     * Returns the img for this profile image
     *
     * @return ImgInterface
     */
    public function getHtmlImgObject(): ImgInterface
    {
        return Img::new($this->getFile()->getSource())->setAlt($this->getDescription());
    }


    /**
     * Sets the img for this profile image
     *
     * @param ImgInterface $img
     *
     * @return static
     */
    public function setHtmlImgObject(ImgInterface $img): static
    {
        return $this->setFile($img->getSrc())
                    ->setDescription($img->getAlt());
    }


    /**
     * Will assign this profile image to the specified user
     *
     * @param UserInterface $user
     *
     * @return $this
     */
    public function setUserObject(UserInterface $user): static
    {
        if ($user->isNew()) {
            throw new OutOfBoundsException(tr('Cannot assign profile image ":image" to user ":user", the user is new and does not yet have a database id', [
                ':image' => $this->getSource(),
                ':user'  => $user->getLogId()
            ]));
        }

        return $this->___setUsersId($user);
    }


    /**
     * Sets the users_id for this object
     *
     * @param int|null $users_id
     *
     * @return static
     */
    protected function setUsersId(int|null $users_id): static
    {
        return $this->___setUsersId($users_id);
    }


    /**
     * Sets the users_id for this object and will assign the profile image to the user
     *
     * @param UserInterface|int|null $user
     *
     * @return static
     */
    protected function ___setUsersId(UserInterface|int|null $user): static
    {
        $file = $this->getFile();

        if ($file) {
            // This profile image object has a file set, process it

            if ($user) {
                // Assign the profile image to the specified user
                if (is_integer($user)) {
                    // We need a user object
                    $user = User::load($user);
                }

                $cdn_directory = FsDirectory::getCdnObject(true, '/img/files/profile/' . $user->getId())
                                            ->ensure();

                Log::action(tr('Adding image ":file" to profile images for user ":user"', [
                    ':file' => $this->getFile()->getRootname(),
                    ':user' => $user->getLogId()
                ]), 4);

                if ($file) {
                    // If the profile image file is NOT in the CDN/img/files/profile/USERS_ID location, move it there first

                    if (!$file->isInDirectory($cdn_directory)) {
                        Log::action(tr('Moving file ":file" to users profile image directory ":directory"', [
                            ':file'      => $file->getRootname(),
                            ':directory' => $cdn_directory->getRootname()
                        ]));

                        $this->setFile($file->move($cdn_directory));
                    }
                }

                $user = $user->getId();

            } else {
                // Assign the profile image to no user
                $user = $this->getUserObject();

                if ($user) {
                    // This profile image is currently assigned to a user and, as such, in its user directory. Move the file
                    // to a generic profile image directory
                    $cdn_directory = FsDirectory::getCdnObject(true, '/img/files/profile/' . $this->getUsersId())
                                                ->ensure();

                    if (!$file->isInDirectory($cdn_directory)) {
                        Log::warning(tr('Profile image ":image" is linked to user ":user" and should be in ":path" but, well, its not...', [
                            ':image' => $file->getRootname(),
                            ':user'  => $user->getLogId(),
                            ':path'  => $cdn_directory->getRootname()
                        ]));
                    }

                    $cdn_directory = FsDirectory::getCdnObject(true, '/img/files/profile/0')
                                                ->ensure();

                    Log::action(tr('Moving file ":file" to general users profile image directory ":directory"', [
                        ':file'      => $file->getRootname(),
                        ':directory' => $cdn_directory->getRootname()
                    ]));

                    $this->setFile($file->move($cdn_directory));
                }

                $user = null;
            }
        }

        return $this->set($user, 'users_id');
    }


    /**
     * Sets the file for this profile image
     *
     * @param FsFileInterface|string|null $file
     *
     * @return static
     */
    public function setFile(FsFileInterface|string|null $file): static
    {
        if ($file) {
            if (is_string($file)) {
                if (str_starts_with($file, '/')) {
                    $file = Strings::from($file, DIRECTORY_CDN . LANGUAGE, needle_required: true);
                    $file = Strings::ensureStartsNotWith($file, '/');
                }

            } else {
                if ($file->isAbsolute()) {
                    $file->makeRelative(FsDirectory::getCdnObject(LANGUAGE));
                }
            }
        }

        return $this->__setFile($file);
    }


    /**
     * Returns the uploads_id for this profile image
     *
     * @return int|null
     */
    public function getUploadsId(): ?int
    {
        return $this->getTypesafe('int', 'uploads_id');
    }


    /**
     * Sets the uploads_id for this profile image
     *
     * @param int|null $uploads_id
     *
     * @return static
     */
    public function setUploadsId(int|null $uploads_id): static
    {
        return $this->set($uploads_id, 'uploads_id');
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $definitions
     */
    protected function setDefinitions(DefinitionsInterface $definitions): void
    {
        $definitions->add(DefinitionFactory::getUsersId($this))

                    ->add(DefinitionFactory::getId($this, 'uploads_id'))

                    ->add(DefinitionFactory::getFile($this)
                                           ->setMaxlength(2048)
                                           ->setRender(false)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isFile(
                                                   [
                                                       FsDirectory::getDataTmpObject(),
                                                       FsDirectory::getCdnObject(true, '/img/files/profile/' . $this->getUserObject()->getId() . '/')
                                                   ],
                                                   prefix: FsDirectory::getCdnObject());
                                           }))

                    ->add(DefinitionFactory::getDescription($this));
    }
}
