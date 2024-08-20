<?php

/**
 * Class Page
 *
 * This is the basic Page class from which other pages can be created. It contains functionality to receive GET and
 * POST data
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Pages;

use Phoundation\Data\Traits\TraitDataEmail;
use Phoundation\Web\Html\Components\ElementsBlock;

abstract class Page extends ElementsBlock
{
    /**
     * The GET data available to this page
     *
     * @var array $get
     */
    protected array $get;

    /**
     * The POST data available to this page
     *
     * @var array $post
     */
    protected array $post;


    /**
     * SignIn class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct($content);
    }


    /**
     * Returns the available GET data for this page
     *
     * @return array
     */
    public function getGetData(): array
    {
        if (isset($this->get)) {
            return $this->get;
        }

        return [];
    }


    /**
     * Sets GET data for this page
     *
     * @param array|null $get
     * @return static
     */
    public function setGetData(?array $get): static
    {
        $this->get = $get ?? [];

        return $this;
    }


    /**
     * Returns the available POST data for this page
     *
     * @return array
     */
    public function getPostData(): array
    {
        if (isset($this->post)) {
            return $this->post;
        }

        return [];
    }


    /**
     * Sets POST data for this page
     *
     * @param array|null $post
     * @return static
     */
    public function setPostData(?array $post): static
    {
        $this->post = $post ?? [];

        return $this;
    }
}
