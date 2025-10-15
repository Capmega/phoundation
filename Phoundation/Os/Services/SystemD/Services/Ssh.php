<?php

/**
 * Class Ssh
 *
 *
 * @see       https://askubuntu.com/questions/1439461/ssh-default-port-not-changing-ubuntu-22-10-and-later
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Os
 */


declare(strict_types=1);

namespace Phoundation\Os\Services\SystemD\Services;

class Ssh
{
    public function enableSocket(): static
    {
//        systemctl disable --now ssh.socket
//        systemctl enable --now ssh.service
    }


    public function enableService(): static
    {
//        systemctl disable --now ssh.socket
//        systemctl enable --now ssh.service
    }
}
