<?php

/**
 * Class AnchorBlock
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components;

use Phoundation\Web\Html\Components\Interfaces\RenderInterface;
use Phoundation\Web\Http\Interfaces\UrlInterface;


class AnchorBlock extends Anchor
{
    public function __construct(UrlInterface|string|null $o_href = null, ?string $content = null, callable|RenderInterface|array|string|null $before_content = null) {
        parent::__construct($o_href, $content, $before_content);

        $this->addClass('element-block');
    }
}
