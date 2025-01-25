<?php

/**
 * Class Apache
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Server;

use Phoundation\Web\Server\Interfaces\VirtualhostInterface;


class Apache extends Webserver
{
    /**
     * Returns an ApacheVirtualhostObject object for
     *
     * @return VirtualhostInterface
     */
    public function getVirtualhostObject(): VirtualhostInterface
    {
        return ApacheVirtualHost::new();
    }
}
