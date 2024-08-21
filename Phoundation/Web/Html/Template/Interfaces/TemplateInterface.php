<?php

/**
 * Interface TemplateInterface
 *
 * This class contains basic template functionalities. All template classes must extend this class!
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Template\Interfaces;

use Phoundation\Filesystem\Interfaces\FsDirectoryInterface;
use Phoundation\Web\Html\Components\Input\Interfaces\RenderInterface;

interface TemplateInterface
{
    /**
     * This function checks if this template is the required template
     *
     * This is in case a specific site requires a specific template
     *
     * @param string $name
     *
     * @return void
     */
    public function requires(string $name): void;


    /**
     * Returns a new TemplatePage for this template
     *
     * @return TemplatePageInterface
     */
    public function getPage(): TemplatePageInterface;


    /**
     * Returns the name for this template
     *
     * @return string
     */
    public function getName(): string;


    /**
     * Returns a Renderer class for the specified component in the current Template, or NULL if none available
     *
     * @param RenderInterface|string $class
     *
     * @return string|null
     */
    public function getRendererClass(RenderInterface|string $class): ?string;


    /**
     * Returns the description for this template
     *
     * @return string|null
     */
    public function getDescription(): ?string;


    /**
     * Returns the root path for this template
     *
     * @return FsDirectoryInterface
     */
    public function getDirectory(): FsDirectoryInterface;
}
