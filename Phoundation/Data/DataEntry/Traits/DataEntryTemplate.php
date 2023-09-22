<?php

declare(strict_types=1);

namespace Phoundation\Data\DataEntry\Traits;

use Phoundation\Templates\Template;
use Phoundation\Templates\Interfaces\TemplateInterface;


/**
 * Trait DataEntryTemplate
 *
 * This trait contains methods for DataEntry objects that require a template
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Data
 */
trait DataEntryTemplate
{
    /**
     * Returns the templates_id for this object
     *
     * @return int|null
     */
    public function getTemplatesId(): ?int
    {
        return $this->getSourceValue('int', 'templates_id');
    }


    /**
     * Sets the templates_id for this object
     *
     * @param int|null $templates_id
     * @return static
     */
    public function setTemplatesId(?int $templates_id): static
    {
        return $this->setSourceValue('templates_id', $templates_id);
    }


    /**
     * Returns the template for this object
     *
     * @return TemplateInterface|null
     */
    public function getTemplate(): ?TemplateInterface
    {
        $templates_id = $this->getSourceValue('int', 'templates_id');

        if ($templates_id) {
            return new Template($templates_id);
        }

        return null;
    }


    /**
     * Returns the templates_name for this object
     *
     * @return string|null
     */
    public function getTemplatesName(): ?string
    {
        return $this->getSourceValue('string', 'templates_name');
    }


    /**
     * Sets the templates_name for this object
     *
     * @param string|null $templates_name
     * @return static
     */
    public function setTemplatesName(?string $templates_name): static
    {
        return $this->setSourceValue('templates_name', $templates_name);
    }
}