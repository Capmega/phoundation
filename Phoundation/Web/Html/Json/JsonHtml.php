<?php

/**
 * Class JsonHtml
 *
 * This class represents a JSON HTML reply, containing HTML sections that can be replaced or modified
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Json;

use Phoundation\Data\IteratorCore;
use Phoundation\Web\Html\Json\Interfaces\JsonHtmlInterface;
use Phoundation\Web\Html\Json\Interfaces\JsonHtmlSectionInterface;


class JsonHtml extends IteratorCore implements JsonHtmlInterface
{
    /**
     * JsonHtml class constructor
     *
     * @param JsonHtmlSectionInterface|null $source
     */
    public function __construct(?JsonHtmlSectionInterface $source = null)
    {
        $this->setAcceptedDataTypes(JsonHtmlSectionInterface::class)
             ->add($source);
    }


    /**
     * Returns a new JsonHtml object
     *
     * @param JsonHtmlSectionInterface|null $source
     *
     * @return static
     */
    public static function new(?JsonHtmlSectionInterface $source = null): static
    {
        return new static($source);
    }


    /**
     * Renders the JSON data array
     *
     * @return array
     */
    public function renderJson(): array
    {
        $return = [];

        foreach ($this->source as $id => $section) {
            $return[$id] = $section->renderJson();
        }

        return ['html' => $return];
    }
}
