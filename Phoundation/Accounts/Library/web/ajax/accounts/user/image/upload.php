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
use Phoundation\Accounts\Users\User;
use Phoundation\Content\Images\ImageFile;
use Phoundation\Core\Log\Log;
use Phoundation\Core\Sessions\Session;
use Phoundation\Data\Validator\Exception\ValidationFailedException;
use Phoundation\Data\Validator\GetValidator;
use Phoundation\Data\Validator\Interfaces\FileValidatorInterface;
use Phoundation\Filesystem\Interfaces\FsUploadedFileInterface;
use Phoundation\Web\Html\Components\Widgets\FlashMessages\FlashMessage;
use Phoundation\Web\Html\Enums\EnumDisplayMode;
use Phoundation\Web\Html\Enums\EnumHttpRequestMethod;
use Phoundation\Web\Html\Json\Enums\EnumJsonHtmlMethods;
use Phoundation\Web\Html\Json\JsonHtml;
use Phoundation\Web\Html\Json\JsonHtmlSection;
use Phoundation\Web\Requests\JsonPage;
use Phoundation\Web\Requests\Request;
use Phoundation\Web\Uploads\UploadHandler;


// Validate GET and get the requested user
$get = GetValidator::new()
                   ->select('id')->isOptional()->isDbId()
                   ->validate();

$user = User::load($get['id']);


// Process file upload
try {
    Request::getMethodRestrictionsObject()->require(EnumHttpRequestMethod::upload);
    Request::getFileUploadHandlersObject()
        ->add(UploadHandler::new('image')
            ->addValidationFunction(function (FileValidatorInterface $validator) {
                $validator->isImage('jpg,png')->isSmallerThan('10MB')
                    ->validate();
            })
            ->setFunction(function(FsUploadedFileInterface $file) use ($user) {
                // Set this image as the profile image
                ProfileImage::newFromImageFile(new ImageFile($file))
                    ->setUserObject($user)
                    ->save()
                    ->setDefault();

                $user->reload();

                JsonPage::new()
                    ->addFlashMessageSections(FlashMessage::new()
                        ->setMode(EnumDisplayMode::success)
                        ->setTitle(tr('Success!'))
                        ->setMessage(tr('The profile picture of ":user" has been updated', [
                            ':user' => $user->getDisplayName()
                        ])))

                    ->addHtmlSections(JsonHtml::new()->add(
                        JsonHtmlSection::new('#profile-picture')
                            ->setMethod(EnumJsonHtmlMethods::replace)
                            ->setHtml($user->getProfileImageObject()
                                ->getHtmlImgObject()
                                    ->setId('profile-picture')
                                    ->addClasses('w100')
                                    ->setAlt(tr('Profile picture for :name', [
                                        ':name' => $user->getDisplayName()
                                    ])))))
                    ->reply();
            })
        )->process();

} catch (ValidationFailedException $e) {
    if (str_starts_with($e->getMessage(), 'No handler found for mimetype')) {
        JsonPage::new()
            ->addFlashMessageSections(FlashMessage::new()
                                                  ->setMode(EnumDisplayMode::warning)
                                                  ->setTitle(tr('Warning!'))
                                                  ->setMessage(tr('Failed to update your profile image with the uploaded file, it is not an image')))
            ->reply();
    }

    throw $e;
}
