<?php

/**
 * Trait TraitDataContent
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opencontent.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;


use Phoundation\Web\Html\Html;
use Stringable;


trait TraitDataContent
{
    /**
     * The content to use
     *
     * @var string|null $content
     */
    protected ?string $content = null;


    /**
     * Returns the content
     *
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }


    /**
     * Sets the content
     *
     * @param Stringable|string|float|int|null $content
     * @param bool                             $make_safe
     *
     * @return static
     */
    public function setContent(Stringable|string|float|int|null $content, bool $make_safe = false): static
    {
        if ($make_safe) {
            $this->content = Html::safe($content);
        } else {
            $this->content = (string) $content;
        }

        return $this;
    }
}
