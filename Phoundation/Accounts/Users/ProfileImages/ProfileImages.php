<?php

/**
 * class ProfileImages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users\ProfileImages;

use Phoundation\Accounts\Users\Interfaces\UserInterface;
use Phoundation\Accounts\Users\ProfileImages\Interfaces\ProfileImageInterface;
use Phoundation\Accounts\Users\ProfileImages\Interfaces\ProfileImagesInterface;
use Phoundation\Data\IteratorCore;
class ProfileImages extends IteratorCore implements ProfileImagesInterface
{
    /**
     * ProfileImages class constructor
     *
     * @param UserInterface $user
     */
    public function __construct(UserInterface $user)
    {
        $this->parent              = $user;
        $this->accepted_data_types = [ProfileImageInterface::class];
    }
}
