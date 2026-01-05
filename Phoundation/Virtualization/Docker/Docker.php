<?php

/**
 * Class Docker
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Virtualization
 */


declare(strict_types=1);

namespace Phoundation\Virtualization\Docker;

use Phoundation\Os\Packages\Packages;


class Docker
{
    /**
     * Installs docker on this server
     *
     * Note: This currently is only supported on Debian / Ubuntu systems
     *
     * @see https://docs.docker.com/engine/install/ubuntu/
     *
     * @return static
     */
    public function install(): static
    {
        // Remove possible old installation
        // Add requirements
        // Add repository
        // Install docker



        // sudo apt update
        // sudo apt install ca-certificates curl
        // sudo install -m 0755 -d /etc/apt/keyrings
        // sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg -o /etc/apt/keyrings/docker.asc
        // sudo chmod a+r /etc/apt/keyrings/docker.asc

        // # Add the repository to Apt sources:
        // sudo tee /etc/apt/sources.list.d/docker.sources <<EOF
        // Types: deb
        // URIs: https://download.docker.com/linux/ubuntu
        // Suites: $(. /etc/os-release && echo "${UBUNTU_CODENAME:-$VERSION_CODENAME}")
        // Components: stable
        // Signed-By: /etc/apt/keyrings/docker.asc
        // EOF

        // sudo apt install docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
        // sudo systemctl restart docker
        // sudo systemctl status docker
        // sudo usermod -aG docker YOURUSER

        return $this;
    }


    public function uninstall(): static
    {
        $packages = Packages::new()->getSelections('docker.io docker-compose docker-compose-v2 docker-doc podman-docker containerd runc');
        // sudo apt remove $(dpkg --get-selections  | cut -f1)
        Packages::new()->remove($packages);

        return $this;
    }
}
