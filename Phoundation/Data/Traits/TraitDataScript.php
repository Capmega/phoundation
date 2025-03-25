<?php

/**
 * Trait TraitDataScript
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Data
 */


declare(strict_types=1);

namespace Phoundation\Data\Traits;

use Phoundation\Web\Html\Components\Interfaces\ScriptInterface;


trait TraitDataScript
{
    /**
     * The script to use
     *
     * @var ScriptInterface|null $script
     */
    protected ?ScriptInterface $script = null;


    /**
     * Returns the script
     *
     * @return ScriptInterface|null
     */
    public function getScriptObject(): ?ScriptInterface
    {
        return $this->script;
    }


    /**
     * Sets the script
     *
     * @param ScriptInterface|null $script
     *
     * @return static
     */
    public function setScriptObject(ScriptInterface|null $script): static
    {
        $this->script = $script;
        return $this;
    }
}
