<?php

/**
 * Trait TraitDataScripts
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
use Phoundation\Web\Html\Components\Interfaces\ScriptsInterface;
use Phoundation\Web\Html\Components\Scripts;


trait TraitDataScripts
{
    /**
     * The scripts to use
     *
     * @var ScriptsInterface|null $_scripts
     */
    protected ?ScriptsInterface $_scripts = null;


    /**
     * Returns the scripts
     *
     * @param bool $auto_initialize
     *
     * @return ScriptsInterface|null
     */
    public function getScriptsObject(bool $auto_initialize = false): ?ScriptsInterface
    {
        if ($this->_scripts === null) {
            if ($auto_initialize) {
                // Auto initialize
                $this->_scripts = new Scripts();
            }
        }

        return $this->_scripts;
    }


    /**
     * Adds the specified Script object callbacks to this class
     *
     * @param callable $_scripts
     *
     * @return static
     */
    public function addScriptObjectCallback(callable $_scripts): static
    {
        $this->getScriptsObject(true)->add($_scripts);
        return $this;
    }


    /**
     * Adds the specified script(s) to this class
     *
     * @param ScriptsInterface|ScriptInterface|callable|null $_scripts
     *
     * @return static
     */
    public function addScriptObject(ScriptsInterface|ScriptInterface|callable|null $_scripts): static
    {
        if ($_scripts instanceof ScriptsInterface) {
            foreach ($_scripts as $_script) {
                $this->addScriptObject($_script);
            }

        } else {
            $this->getScriptsObject(true)->add($_scripts);
        }

        return $this;
    }


    /**
     * Sets the scripts
     *
     * @param ScriptsInterface|null $_scripts
     *
     * @return static
     */
    public function setScriptsObject(?ScriptsInterface $_scripts): static
    {
        $this->_scripts = $_scripts;
        return $this;
    }
}
