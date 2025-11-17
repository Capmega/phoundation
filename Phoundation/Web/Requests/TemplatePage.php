<?php

/**
 * Class TemplatePage
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Requests;

use Phoundation\Web\Html\Template\Interfaces\TemplatePageInterface;


abstract class TemplatePage implements TemplatePageInterface
{
    /**
     * Returns the page instead of sending it to the client
     *
     * This WILL send the HTTP headers, but will return the HTML instead of sending it to the browser
     *
     * @return string|null
     */
    abstract public function execute(): ?string;


    /**
     * Build the page body
     *
     * @return string|null
     */
    public function renderMain(): ?string
    {
        return execute();
    }


    /**
     * Build the HTML footer
     *
     * @return string|null
     */
    public function renderHtmlFooters(): ?string
    {
        $footers = Response::renderHtmlFooters();

        if (Response::getRenderMainWrapper()) {
            return $footers . '
                      </body>
                  </html>';
        }

        return $footers . '
               </html>';
    }


    /**
     * Returns a warning about the used environment when said environment is not production
     *
     * @return string|null
     */
    public function renderEnvironmentWarning(): ?string
    {
        if (ENVIRONMENT === 'production') {
            return null;
        }

        if (!config()->getBoolean('web.warnings.environments.enabled', true)) {
            return null;
        }

        $mode    = config()->getString('web.warnings.environments.' . ENVIRONMENT . '.background', 'warning');
        $title   = config()->getString('web.warnings.environments.' . ENVIRONMENT . '.title'     , tr('Notice!'));
        $message = config()->getString('web.warnings.environments.' . ENVIRONMENT . '.message'   , tr('You are currently navigating the ":environment" environment of this system. All data presented is artificially generated and the database can be reset upon request. Any data you enter into this system may be purged at any moment without prior notice!', [':environment' => ENVIRONMENT]));

        return $this->getRenderedEnvironmentWarning($mode, $title, $message);
    }


    /**
     * Actually renders the environment warning message
     *
     * @param string|null $mode
     * @param string|null $title
     * @param string|null $message
     *
     * @return string|null
     */
    abstract protected function getRenderedEnvironmentWarning(?string $mode, ?string $title, ?string $message): ?string;


    /**
     * Build and send HTTP headers
     *
     * @param string $output
     *
     * @return void
     */
    abstract public function renderHttpHeaders(string $output): void;
}
