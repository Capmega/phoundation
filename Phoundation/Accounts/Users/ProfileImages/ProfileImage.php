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
use Phoundation\Data\DataEntry\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryFile;
use Phoundation\Data\DataEntry\Traits\TraitDataEntryUsersId;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Validator\Exception\ValidatorException;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
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
     * @param IdentifierInterface|array|string|int|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|null $identifier = null)
    {
        parent::__construct($identifier);

        $this->restrictions = PhoRestrictions::newWritable([DIRECTORY_TMP, DIRECTORY_CDN]);
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
        return static::new()->setFileObject($source);
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
    public static function getEntryName(): string
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

        if ($this->getFileObject()) {
            // This profile image object can ony be the default if it has a file
            return $this->getUserObject()->getProfileImageObject() === $this->getFileObject();
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
            ':file' => $this->getFileObject()->getRootname(),
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
        return new ImageFile($this->getFileObject());
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
        return $this->setFileObject($file);
    }


    /**
     * Returns the img for this profile image
     *
     * @return ImgInterface
     */
    public function getHtmlImgObject(): ImgInterface
    {
        return Img::new($this->getFileObject()->getSource())->setAlt($this->getDescription());
    }


    /**
     * Sets the img for this profile image
     *
     * @param ImgInterface $o_img
     *
     * @return static
     */
    public function setHtmlImgObject(ImgInterface $o_img): static
    {
        return $this->setFile($o_img->getSrc())
                    ->setDescription($o_img->getAlt());
    }


    /**
     * Will assign this profile image to the specified user
     *
     * @param UserInterface $o_user
     *
     * @return static
     */
    public function setUserObject(UserInterface $o_user): static
    {
        if ($o_user->isNew()) {
            if (!$this->isReadonly()) {
                throw new OutOfBoundsException(tr('Cannot assign profile image ":image" to user ":user", the user is new and does not yet have a database id', [
                    ':image' => $this->getSource(),
                    ':user'  => $o_user->getLogId()
                ]));
            }

            // This profile image is readonly and cannot be saved anyway, so likely is a default image for a new user
        }

        return $this->__setUsersId($o_user->getId(), $o_user);
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
        return $this->__setUsersId($users_id);
    }


    /**
     * Sets the users_id for this object and will assign the profile image to the user
     *
     * @param int|null           $users_id
     * @param UserInterface|null $o_user
     *
     * @return static
     */
    protected function __setUsersId(?int $users_id, ?UserInterface $o_user = null): static
    {
        $o_file = $this->getFileObject();

        if ($o_file) {
            // This profile image object has a file set, process it

            if ($users_id) {
                // Assign the profile image to the specified user
                if (empty($o_user)) {
                    // We need a user object
                    $o_user = User::load($users_id);
                }

                $cdn_directory = PhoDirectory::newCdnObject(true, '/img/files/profile/' . $users_id)
                                             ->ensure();

                Log::action(tr('Adding image ":file" to profile images for user ":user"', [
                    ':file' => $this->getFileObject()->getRootname(),
                    ':user' => $o_user->getLogId()
                ]), 4);

                if ($o_file) {
                    // If the profile image file is NOT in location CDN/img/files/profile/USERS_ID, move it there first
                    if (!$o_file->isInDirectory($cdn_directory)) {
                        Log::action(tr('Moving file ":file" to users profile image directory ":directory"', [
                            ':file'      => $o_file->getRootname(),
                            ':directory' => $cdn_directory->getRootname()
                        ]));

                        $this->setFileObject($o_file->move($cdn_directory));
                    }
                }

            } else {
                // Assign the profile image to no user
                $o_user = $this->getUserObject();

                if ($o_user) {
                    // This profile image is currently assigned to a user and, as such, in its user directory. Move the file
                    // to a generic profile image directory
                    $cdn_directory = PhoDirectory::newCdnObject(true, '/img/files/profile/' . $this->getUsersId())
                                                 ->ensure();

                    if (!$o_file->isInDirectory($cdn_directory)) {
                        Log::warning(tr('Profile image ":image" is linked to user ":user" and should be in ":path" but, well, its not...', [
                            ':image' => $o_file->getRootname(),
                            ':user'  => $o_user->getLogId(),
                            ':path'  => $cdn_directory->getRootname()
                        ]));
                    }

                    $cdn_directory = PhoDirectory::newCdnObject(true, '/img/files/profile/0')
                                                 ->ensure();

                    Log::action(tr('Moving file ":file" to general users profile image directory ":directory"', [
                        ':file'      => $o_file->getRootname(),
                        ':directory' => $cdn_directory->getRootname()
                    ]));

                    $this->setFileObject($o_file->move($cdn_directory));
                }

                $users_id = null;
            }
        }

        return $this->set($users_id, 'users_id');
    }


    /**
     * Sets the file for this profile image
     *
     * @param string|null $file
     *
     * @return static
     */
    public function setFile(string|null $file): static
    {
        if ($file) {
            if (str_starts_with($file, '/')) {
                if (str_starts_with($file, DIRECTORY_CDN)) {
                    $file = Strings::from($file, DIRECTORY_CDN . LANGUAGE, needle_required: true);
                    $file = Strings::ensureStartsNotWith($file, '/');
                }
            }
        }

        return $this->__setFile(get_null($file));
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
     *
     * @return static
     */
    protected function setDefinitions(DefinitionsInterface $definitions): static
    {
        $definitions->add(DefinitionFactory::newUsersId($this))

                    ->add(DefinitionFactory::newId($this, 'uploads_id'))

                    ->add(DefinitionFactory::newFile($this)
                                           ->setMaxlength(2048)
                                           ->setRender(false)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isFile(
                                                   [
                                                       PhoDirectory::newDataTmpObject(),
                                                       ($this->getUserObject() ? PhoDirectory::newCdnObject(true, '/img/files/profile/' . $this->getUserObject()?->getId() . '/') : null)
                                                   ],
                                                   prefix: PhoDirectory::newCdnObject());
                                           }))

                    ->add(DefinitionFactory::newDescription($this));

        return $this;
    }
}
