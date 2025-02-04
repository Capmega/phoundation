<?php

/**
 * Class GuestUser
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Accounts
 */


declare(strict_types=1);

namespace Phoundation\Accounts\Users;

use Phoundation\Accounts\Users\Interfaces\GuestUserInterface;
use Phoundation\Data\DataEntry\Interfaces\IdentifierInterface;
use Phoundation\Data\Validator\Interfaces\ValidatorInterface;
use Phoundation\Web\Html\Enums\EnumInputType;


class GuestUser extends User implements GuestUserInterface
{
    /**
     * GuestUser class constructor
     *
     * @param IdentifierInterface|array|string|int|false|null $identifier
     */
    public function __construct(IdentifierInterface|array|string|int|false|null $identifier = null)
    {
        parent::__construct();

        $this->getDefinitionsObject()->get('email')->setInputType(EnumInputType::text)
                                                   ->clearValidationFunctions()
                                                   ->addValidationFunction(function (ValidatorInterface $validator) {
                                                       $validator->hasMaxCharacters(5);
                                                   });

        $this->loadOrThisInitialize('guest');
    }
}
