<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Input;

use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Requests\Response;

/**
 * Class InputSelect2
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */
class InputSelect2 extends InputSelect
{
    /**
     * InputSelect2 class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        showbacktrace();
        showdie();
        parent::__construct($content);
        $this->addClass('select2bs4');
        Response::loadCss('css/plugins/select2/css/select2');
        Response::loadCss('css/plugins/select2-bootstrap4-theme/select2-bootstrap4');
        Response::loadJavascript('js/plugins/select2/js/select2.full');
    }


    /**
     * Returns the <select> and required javascript
     *
     * @return string|null
     */
    public function render(): ?string
    {
        static $rendered = false;
        if (!$rendered) {
            $rendered   = true;
            $javascript = Script::new('
                // Initialize the Select2 Elements
                $(".select2").select2()

                $(".select2bs4").select2({
                  theme: "bootstrap4"
                })
            ')
                                ->render();
        }

        return parent::render() . isset_get($javascript);
    }
}
