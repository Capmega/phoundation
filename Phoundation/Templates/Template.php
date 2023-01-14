<?php

namespace Phoundation\Templates;



/**
 * Class Template
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Templates
 */
class Template
{
    /**
     * The template text
     *
     * @var string|null $text
     */
    protected ?string $text = null;



    /**
     * Template class constructor
     */
    public function __construct(string $text = null)
    {
        $this->text = $text;
    }



    /**
     * Returns the template text
     *
     * @return string|null
     */
    public function getText(): ?string
    {
        return $this->text;
    }



    /**
     * Set the template text
     *
     * @param string|null $text
     * @return static
     */
    public function setText(?string $text): static
    {
        $this->text = $text;
        return $this;
    }



    /**
     * @param array $source
     * @return string
     */
    public function apply(array $source): string
    {
        $text = $this->text;

        foreach ($source as $search => $replace) {
            $text = str_replace($search, $replace, $text);
        }

        return $text;
    }
}