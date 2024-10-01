<?php

/**
 * Activity class
 *
 * This Core library HTML widget component object can render the HTML required to display a single metadata activity
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Core\Meta\Activities;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\User;
use Phoundation\Core\Meta\Activities\Interfaces\ActivityInterface;
use Phoundation\Data\Traits\TraitDataSourceArray;
use Phoundation\Data\Traits\TraitMethodHasRendered;
use Phoundation\Date\DateTime;
use Phoundation\Date\Interfaces\DateTimeInterface;
use Phoundation\Utils\Arrays;
use Phoundation\Utils\Exception\JsonException;
use Phoundation\Utils\Json;
use Phoundation\Utils\Strings;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Http\Url;


class Activity implements ActivityInterface, RenderInterface
{
    use TraitMethodHasRendered;
    use TraitDataSourceArray;


    /**
     * Caches the user that created this activity
     *
     * @var UserInterface $user
     */
    protected UserInterface $user;

    /**
     * Caches the DateTime object for when this activity was created
     *
     * @var DateTimeInterface $date
     */
    protected DateTimeInterface $date;


    /**
     * Activity class constructor
     *
     * @param array|null $source
     */
    public function __construct(?array $source = null)
    {
        $this->setSource($source);
    }


    /**
     * Returns a new Activity object
     *
     * @param array|null $source
     *
     * @return static
     */
    public static function new(?array $source = null): static
    {
        return new static($source);
    }


    /**
     * Returns a user object for the user that performed this action
     *
     * @return UserInterface|null
     */
    public function getUserObject(): ?UserInterface
    {
        if (array_key_exists('created_by', $this->source)) {
            if (empty($this->user)) {
                $this->user = new User($this->source['created_by']);
            }

            return $this->user;
        }

        return null;
    }


    /**
     * Returns the executed action
     *
     * @return string
     */
    public function getAction(): string
    {
        return isset_get($this->source['action'], tr('Unknown'));
    }


    /**
     * Returns if the executed action is the specified action
     *
     * @param string $action
     *
     * @return bool
     */
    public function isAction(string $action): bool
    {
        return $this->getAction() === $action;
    }


    /**
     * Returns the executed action
     *
     * @return string
     */
    public function getComment(): string
    {
        return isset_get($this->source['comment'], tr('-'));
    }


    /**
     * Returns the data for the executed action
     *
     * @return array
     */
    public function getData(): array
    {
        $data = isset_get($this->source['data']);

        if ($data) {
            try {
                return Json::decode($data);

            } catch (JsonException) {
                // Fall through
            }
        }

        return [];
    }


    /**
     * Returns the moment this action was performed
     *
     * @return string
     */
    public function getMoment(): string
    {
        if (array_key_exists('created_on', $this->source)) {
            if (empty($this->date)) {
                $this->date = new DateTime($this->source['created_on']);
            }

            return $this->date->getAge();
        }

        return tr('Unknown');
    }


    /**
     * Ensures that the specified file is an array with the correct fields
     *
     * @param mixed $file
     *
     * @return array|null
     */
    protected function ensureFileData(mixed $file): ?array
    {
        if (!$file) {
            return null;
        }

        Arrays::ensure($file, 'url,name');

        return $file;
    }


    /**
     * Renders and returns the HTML
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if (empty($this->render)) {
            $fileuploads = '';

            // Render actions
            switch ($this->getAction()) {
                case 'fileupload':
                    // Render for fileupload actions
                    $data  = $this->getData();
                    $files = $data['files'];
                    $html  = '';

                    foreach ($files as $file) {
                        $file = $this->ensureFileData($file);

                        if ($file) {
                            $html .= '<a href="' . $file['url'] . '" class="link-black text-sm"><i class="fas fa-link mr-1"></i> ' . $file['name'] . '</a>';

                        } else {
                            $html .= '<span class="link-black text-sm"><i class="fas fa-corss mr-1"></i> ' . tr('Corrupted file data') . '</span>';
                        }
                    }

                    $action      = tr('Uploaded files');
                    $fileuploads = '<p> ' . $html . ' </p>';
                    break;

                default:
                    $action = Strings::capitalize($this->getAction());
            }

            $this->render = '   <div class="post">
                                    <div class="user-block">'.
                                        $this->getUserObject()
                                                 ->getProfileImageObject()
                                                     ->getHtmlImgObject()
                                                         ->setClass("img-circle img-bordered-sm")
                                                         ->setAlt(tr("Profile picture for :user", [":user" => Html::safe($this->getUserObject()->getDisplayName())]))
                                                         ->render() .'
                                        <span class="username">
                                          <a href="' . Url::getWww('profiles/profile+' . $this->getUserObject()->getId() . '.html') .'">' . $this->getUserObject()->getDisplayName() . '</a>
                                        </span>
                                        <span class="description">' . $action . ' - ' . tr(':time ago', [':time' => $this->getMoment()]) . '</span>
                                    </div>
                                    <!-- /.user-block -->
                                    <p>' . $this->getComment() . '</p>
                                            
                                    ' . $fileuploads . '
                                    
                                </div>';
        }

        return $this->render;
    }
}
