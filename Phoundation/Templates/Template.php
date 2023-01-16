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
     * Returns a new Template object
     */
    public static function new(string $text = null): Template
    {
        return new Template($text);
    }



    /**
     * Returns a new Template object
     */
    public static function page(string $page_name = null): Template
    {
        $text = self::getPage($page_name);
        return self::new($text);
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
    public function render(array $source): string
    {
        $text = $this->text;

        foreach ($source as $search => $replace) {
            $text = str_replace($search, $replace, $text);
        }

        return $text;
    }



    /**
     * Returns the text for the specified page
     *
     * @todo Implement! For now this just returns hard coded texts
     * @param string $page
     * @return string|null
     */
    protected static function getPage(string $page): ?string
    {
        switch ($page) {
            case 'system/error':
                return '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
                        <html><head>
                            <title>:title</title>
                        </head><body>
                            <h1>:h1</h1>
                            <p>:p</p>
                            <hr>
                            :body
                        </body></html>';

            case 'system/detail-error':
                return ' <div class="error-page">
                            <h2 class="headline text-warning"> :h2</h2>
                        
                            <div class="error-content">
                                <h3><i class="fas fa-exclamation-triangle text-:type"></i> :h3</h3>
                        
                                <p>:p</p>
                        
                                <form class="search-form" method="post" action=":action">
                                    <div class="input-group">
                                        <input type="text" name="search" class="form-control" placeholder=":search">                        
                                        <div class="input-group-append">
                                            <button type="submit" name="submit" class="btn btn-warning"><i class="fas fa-search"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <!-- /.input-group -->
                                </form>
                            </div>
                            <!-- /.error-content -->
                        </div>';

        }

        return tr('TEMPLATE PAGE ":page" NOT FOUND', [':page' => $page]);
    }
}