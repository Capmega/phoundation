<?php

/**
 * Class Api
 *
 * This class contains methods to assist in building web pages
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests;

use Phoundation\Web\Requests\Interfaces\ApiPageInterface;


class ApiPage extends JsonPage implements ApiPageInterface
{
}
