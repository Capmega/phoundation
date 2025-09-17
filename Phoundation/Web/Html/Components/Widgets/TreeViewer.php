<?php

/**
 * TreeViewer class
 *
 *
 *
 * @author    Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license   http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright © 2025 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package   Phoundation\Web
 */


declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Interfaces\TreeInterface;
use Phoundation\Data\Traits\TraitDataRenderMethod;
use Phoundation\Data\Traits\TraitDataUrl;
use Phoundation\Data\Tree;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\Anchor;
use Phoundation\Web\Html\Components\Div;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Components\Widgets\Interfaces\TreeViewerInterface;
use Phoundation\Web\Html\Enums\EnumWebRenderMethods;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Requests\Response;


class TreeViewer extends Widget implements TreeViewerInterface
{
    use TraitDataUrl;
    use TraitDataRenderMethod;

    /**
     * TreeViewer class constructor
     *
     * @param string|null $source
     */
    public function __construct(?string $source = null)
    {
        parent::__construct();

        if ($source) {
            $this->setSource($source);
        }
    }


    /**
     * Sets the internal source directly
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null                                       $execute
     * @param bool                                             $filter_meta
     *
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null, bool $filter_meta = false): static
    {
        $source = get_null($source);

        if ($source) {
            if (is_string($source)) {
                // This must be a JSON source, try to decode it.
                $source = Json::decode($source);
            }

            if (!is_array($source) and !($source instanceof TreeInterface)) {
                // This is not a valid source
                throw OutOfBoundsException::new(tr('Cannot use specified source for TreeViewer object, source must be an array or a TreeInterface object'))
                                          ->setData(['source' => $source]);
            }
        }

        return parent::setSource($source);
    }


    /**
     * @inheritDoc
     */
    public function render(): ?string
    {
        if (!$this->getId()) {
            throw new OutOfBoundsException(tr('Cannot render tree viewer, no HTML id specified'));
        }

        Response::loadJavaScript('mdb/js/plugins/treeview.min');

        if ($this->render_method === EnumWebRenderMethods::html) {
            // Render the tree-view using pure HTML
            return Div::new()
                      ->setId($this->getId())
                      ->setContent($this->renderHtml($this->source))
                      ->addClasses('treeview')
                      ->addData('', 'mdb-treeview-init')
                      ->render() . Script::new('$("#' . $this->getId() . '").treeview()')
                                         ->render();
        }

        // Render the tree-view using javascript data
        return Div::new()
                  ->setId($this->getId())
                  ->addClasses('treeview')
                  ->render() . Script::new('
             const jsTreeview = document.getElementById("' . $this->getId() . '");
             const jsInstance = new Treeview(jsTreeview, {
               structure: ' . Tree::new($this->source)
                                  ->getJson(true) . ',
             });
             // Fix issue where sub trees are missing the "collapse" class at first load
             $("div.tree-view li[role=\'tree-item\'] ul:not(.collapse)").addClass("collapse")')
                                     ->render();
    }


    /**
     * Renders and returns tree-view content HTML for the given source
     *
     * @param array $source
     * @param bool  $child
     *
     * @return string
     */
    protected function renderHtml(array $source, bool $child = false): string
    {
        $return = '<ul' . ($child ? ' class="collapse"' : '') . '>';

        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $return .= '<li>
                                ' . Anchor::new()->setClass('not-a-link')->setContent($key) . $this->renderHtml($value, true) . '
                            </li>';

            } else {
                if ($this->url) {
                    $url    = str_replace(':ID', (string) $key, $this->url);
                    $return .= '<li>
                                    ' . Anchor::new($url, $value) . '
                                </li>';

                } else {
                    $return .= '<li>' . $value . '</li>';
                }
            }
        }

        return $return . '</ul>';
    }
}
