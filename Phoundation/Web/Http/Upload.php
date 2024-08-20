<?php

/**
 * Class Upload
 *
 * This class represents an uploaded file
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Http;

use Phoundation\Web\Requests\FileResponse;


class Upload extends FileResponse
{
    protected string $status;
}
