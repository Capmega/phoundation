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

use Phoundation\Core\Log\Log;
use Phoundation\Filesystem\PhoFile;
use Phoundation\Filesystem\PhoRestrictions;
use Phoundation\Utils\Strings;


class ApacheVirtualHost extends Virtualhost
{
    /**
     * @return $this
     */
    public function installFile(): static
    {
        // Build the apache virtualhost file & target link
        $domain  = $this->domain;
        $domain  = config()->getString('web.domains.' . $domain . '.' . $this->type);
        $domain  = Strings::from($domain, '//');
        $domain  = Strings::until($domain, '/');
        $secure  = !str_ends_with($domain, '.local');
        $domain .= $secure ? '-le-ssl' : null;
        $link    = PhoFile::new('/etc/apache2/sites-available/' . $domain . '.conf', PhoRestrictions::newWritableObject([
            '/etc/apache2/sites-available/',
            '/etc/httpd/'
        ]));
        $file    = PhoFile::new(DIRECTORY_ROOT . 'config/webservers/apache/' . $domain . '.conf', PhoRestrictions::newWritableObject([
            DIRECTORY_ROOT . 'config/webservers/apache/',
        ]));

        if ($link->exists()) {
            Log::warning(ts('The Apache webserver virtualhost file ":file" for project / environment ":project/:environment" is already installed', [
                ':file'        => $link->getRootname(),
                ':project'     => PROJECT,
                ':environment' => ENVIRONMENT,
            ]));

        } else {
            $file->symlinkTargetFromThis($link);

            Log::warning(ts('Installed Apache webserver virtualhost file ":file" for project / environment ":project/:environment"', [
                ':file'        => $link->getRootname(),
                ':project'     => PROJECT,
                ':environment' => ENVIRONMENT,
            ]));
        }

        return $this;
    }
}
