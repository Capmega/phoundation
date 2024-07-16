<?php

/**
 * Phoundation Modals class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets\Modals;

use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Web\Html\Components\ElementsBlock;

class Modals extends ElementsBlock
{
//    /**
//     * An optional list of modals that are required before rendering can be done
//     *
//     * @var array|null $required
//     */
//    protected ?array $required = null;
    /**
     * Add a new modal
     *
     * @param string $identifier
     * @param Modal  $modal
     *
     * @return $this
     */
    public function addModal(string $identifier, Modal $modal): static
    {
        $this->source[$identifier] = $modal;

        return $this;
    }


//    /**
//     * ???
//     *
//     * @param array|string|null $required
//     * @return Modals
//     */
//    public function setRequired(array|string|null $required): static
//    {
//        if (!$required) {
//            $this->required = null;
//        } else {
//            $this->required = Arrays::force($required);
//        }
//
//        return $this;
//    }
    /**
     * Render the modals and return the HTML
     *
     * @return string|null
     */
    public function render(): ?string
    {
        if ($this->required) {
            // Ensure that these modals are available
            foreach ($this->required as $required) {
                if (!array_key_exists($required, $this->source)) {
                    throw new OutOfBoundsException(tr('Cannot render modals, the required modal ":modal" is not set', [
                        ':modal' => $required,
                    ]));
                }
            }
        }
        $this->render = '';
        foreach ($this->source as $modal) {
            $this->render .= $modal->render();
        }

        return parent::render();
    }
}
