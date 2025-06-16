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
     * @var ScriptsInterface|null $o_scripts
     */
    protected ?ScriptsInterface $o_scripts = null;


    /**
     * Returns the scripts
     *
     * @param bool $auto_initialize
     *
     * @return ScriptsInterface|null
     */
    public function getScriptsObject(bool $auto_initialize = false): ?ScriptsInterface
    {
        if ($this->o_scripts === null) {
            if ($auto_initialize) {
                // Auto initialize
                $this->o_scripts = new Scripts();
            }
        }

        return $this->o_scripts;
    }


    /**
     * Adds the specified Script object callbacks to this class
     *
     * @param callable $o_scripts
     *
     * @return static
     */
    public function addScriptObjectCallback(callable $o_scripts): static
    {
        $this->getScriptsObject(true)->add($o_scripts);
        return $this;
    }


    /**
     * Adds the specified script(s) to this class
     *
     * @param ScriptsInterface|ScriptInterface|callable|null $o_scripts
     *
     * @return static
     */
    public function addScriptObject(ScriptsInterface|ScriptInterface|callable|null $o_scripts): static
    {
        if ($o_scripts instanceof ScriptsInterface) {
            foreach ($o_scripts as $o_script) {
                $this->addScriptObject($o_script);
            }

        } else {
            $this->getScriptsObject(true)->add($o_scripts);
        }

        return $this;
    }


    /**
     * Sets the scripts
     *
     * @param ScriptsInterface|null $o_scripts
     *
     * @return static
     */
    public function setScriptsObject(?ScriptsInterface $o_scripts): static
    {
        $this->o_scripts = $o_scripts;
        return $this;
    }
}
