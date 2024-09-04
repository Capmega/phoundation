<?php

/**
 * Ajax my/profile/image/upload.php
 *
 * This is the user profile page AJAX profile image upload management script.
 *
 * It will receive the uploaded profile image and attach it to the user and set it as the default profile image
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);


use Phoundation\Accounts\Users\ProfileImages\ProfileImage;
use Phoundation\Content\Images\ImageFile;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Interfaces\FileValidatorInterface;
use Phoundation\Filesystem\Interfaces\FsUploadedFileInterface;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Json\Enums\EnumJsonHtmlMethods;
use Phoundation\Web\Html\Json\JsonHtml;
use Phoundation\Web\Html\Json\JsonHtmlSection;
use Phoundation\Web\Requests\JsonPage;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Uploads\UploadHandler;


Request::getMethodRestrictionsObject()->require(EnumHttpRequestMethod::upload);
Request::getFileUploadHandlersObject()
    ->add(UploadHandler::new('image')
        ->addValidationFunction(function (FileValidatorInterface $validator) {
            $validator->isImage('jpg,png')->isSmallerThan('10MB')
                      ->validate();
        })
        ->setFunction(function(FsUploadedFileInterface $file) {
            // Set this image as the profile image
            ProfileImage::newFromImageFile(new ImageFile($file))
                ->setUserObject(Session::getUserObject())
                ->save()
                ->setDefault();

            JsonPage::new()
                ->addHtmlSections(JsonHtml::new()->add(
                    JsonHtmlSection::new('#profile-picture')
                        ->setMethod(EnumJsonHtmlMethods::replace)
                        ->setHtml(Session::getUserObject()
                            ->getProfileImageObject()
                            ->getHtmlImgObject()
                            ->setId('profile-picture')
                            ->addClasses('w100')
                            ->setAlt(tr('Profile picture for :name', [
                                ':name' => Session::getUserObject()->getDisplayName()
                            ]))))
                    ->add(
                        JsonHtmlSection::new('#menu-profile-image')
                            ->setMethod(EnumJsonHtmlMethods::replace)
                            ->setHtml(Session::getUserObject()
                                ->getProfileImageObject()
                                ->getHtmlImgObject()
                                ->setId('menu-profile-image')
                                ->addClasses('img-circle elevation-2')
                                ->setAlt(tr('Profile picture for :name', [
                                    ':name' => Session::getUserObject()->getDisplayName()
                                ])))
                    ))
                ->reply();
        })
    )->process();

