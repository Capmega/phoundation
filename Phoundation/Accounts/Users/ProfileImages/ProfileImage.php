<?php

/**
 * Class ProfileImage
 *
 * This class represents a single entry from the accounts_profile_images table, or a single profile image for a user
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
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
use Phoundation\Data\DataEntries\DataEntry;
use Phoundation\Data\DataEntries\Definitions\DefinitionFactory;
use Phoundation\Data\DataEntries\Definitions\Interfaces\DefinitionsInterface;
use Phoundation\Data\DataEntries\Interfaces\IdentifierInterface;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryDescription;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryFile;
use Phoundation\Data\DataEntries\Traits\TraitDataEntryUser;
use Phoundation\Data\Enums\EnumLoadParameters;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Filesystem\Interfaces\PhoFileInterface;
use Phoundation\Filesystem\PhoDirectory;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Img;
use Phoundation\Web\Html\Components\Interfaces\ImgInterface;


class ProfileImage extends DataEntry implements ProfileImageInterface
{
    use TraitDataEntryDescription;
    use TraitDataEntryFile {
        setFileObject as protected __setFileObject;
    }
    use TraitDataEntryUser {
        setUserObject as protected __setUserObject;
    }


    /**
     * ProfileImage class constructor
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = false)
    {
        $this->initializeVirtualConfiguration([
            'users' => ['id'],
        ]);

        parent::__construct($identifier);
        $this->ensureFile();
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

        Log::action(ts('Setting image ":file" as default profile image for user ":user"', [
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
        return Img::new($this->getFileObject()->getFrom(DIRECTORY_PROJECT_CDN)->getSource())->setAlt($this->getDescription());
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
     * Returns the users_id for this user
     *
     * @param UserInterface|null $o_user
     *
     * @return static
     */
    public function setUserObject(?UserInterface $o_user): static
    {
        $o_file = $this->getFileObject();

        if ($o_file) {
            // This profile image object has a file set, process it
            $this->setReadonly(false)
                 ->setRestrictions(PhoRestrictions::newWritableObject([DIRECTORY_TMP, DIRECTORY_CDN]));

            $o_file->setRestrictions($this->getRestrictions());

            if ($o_user) {
                $cdn_directory = PhoDirectory::newCdnObject(true, 'img/files/profile/' . $o_user->getId())
                                             ->ensure();

                Log::action(ts('Adding image ":file" to profile images for user ":user"', [
                    ':file' => $o_file->getRootname(),
                    ':user' => $o_user->getLogId()
                ]), 4);

                // If the profile image file is NOT in location CDN/img/files/profile/USERS_ID, move it there first
                if (!$o_file->isInDirectory($cdn_directory)) {
                    Log::action(ts('Moving file ":file" to users profile image directory ":directory"', [
                        ':file'      => $o_file->getRootname(),
                        ':directory' => $cdn_directory->getRootname()
                    ]));

                    $this->setFileObject($o_file->move($cdn_directory));
                }

            } else {
                // Assign the profile image to no user
                $o_current = $this->getUserObject();

                if ($o_current) {
                    // This profile image is currently assigned to a user and, as such, in its user directory. Move the file
                    // to a generic profile image directory
                    $cdn_directory = PhoDirectory::newCdnObject(true, '/img/files/profile/' . $o_current->getId())
                                                 ->ensure();

                    if (!$o_file->isInDirectory($cdn_directory)) {
                        Log::warning(ts('Profile image ":image" is linked to user ":user" and should be in ":path" but, well, its not...', [
                            ':image' => $o_file->getRootname(),
                            ':user'  => $o_user->getLogId(),
                            ':path'  => $cdn_directory->getRootname()
                        ]));
                    }

                    $cdn_directory = PhoDirectory::newCdnObject(true, '/img/files/profile/0')
                                                 ->ensure();

                    Log::action(ts('Moving file ":file" to general users profile image directory ":directory"', [
                        ':file'      => $o_file->getRootname(),
                        ':directory' => $cdn_directory->getRootname()
                    ]));

                    $this->setFileObject($o_file->move($cdn_directory));
                }

                $o_user = null;
            }
        }

        return $this->__setUserObject($o_user);
    }


    /**
     * Sets the file for this object
     *
     * @param PhoFileInterface|null $file
     *
     * @return static
     */
    public function setFileObject(PhoFileInterface|null $file): static
    {
        if ($file) {
            $o_directory = PhoDirectory::newCdnObject();

            if ($file->isInDirectory($o_directory)) {
                $file = $file->getFrom($o_directory);
            }
        }

        return $this->__setFileObject($file);
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
     * @inheritDoc
     */
    public function load(IdentifierInterface|array|int|string|null $identifier = null, ?EnumLoadParameters $on_load_null_identifier = null, ?EnumLoadParameters $on_load_not_exists = null): ?static
    {
        parent::load($identifier, $on_load_null_identifier, $on_load_not_exists);
        return $this->ensureFile();
    }


    /**
     * Ensures a file will be available for this profile image
     *
     * @return static
     */
    protected function ensureFile(): static
    {
        if ($this->getFile()) {
            $this->restrictions = PhoRestrictions::newWritableObject([DIRECTORY_TMP, DIRECTORY_CDN]);

        } else {
            // This profile image has no file, assign the default profile image file
            $this->setReadonly(true)
                 ->setRestrictions(PhoRestrictions::newReadonlyObject(DIRECTORY_PROJECT_CDN))
                 ->setFile(DIRECTORY_PROJECT_CDN . 'img/profiles/default.png');
        }

        return $this;
    }


    /**
     * Sets the available data keys for this entry
     *
     * @param DefinitionsInterface $o_definitions
     *
     * @return static
     */
    protected function setDefinitionsObject(DefinitionsInterface $o_definitions): static
    {
        $o_definitions->add(DefinitionFactory::newUsersId())

                      ->add(DefinitionFactory::newId('uploads_id'))

                      ->add(DefinitionFactory::newFile()
                                           ->setMaxlength(2048)
                                           ->setRender(false)
                                           ->addValidationFunction(function (ValidatorInterface $validator) {
                                               $validator->isFile([
                                                   PhoDirectory::newDataTmpObject(),
                                                   ($this->getUserObject() ? PhoDirectory::newCdnObject(true, 'img/files/profile/' . $this->getUserObject()?->getId() . '/') : null)
                                               ]);
                                           }))

                    ->add(DefinitionFactory::newDescription());

        return $this;
    }
}
