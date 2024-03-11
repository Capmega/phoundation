<?php

declare(strict_types=1);

namespace Phoundation\Web\Html\Components\Widgets;

use PDOStatement;
use Phoundation\Data\Interfaces\IteratorInterface;
use Phoundation\Data\Interfaces\TreeInterface;
use Phoundation\Data\Traits\DataRenderMethod;
use Phoundation\Data\Traits\DataUrl;
use Phoundation\Data\Tree;
use Phoundation\Exception\OutOfBoundsException;
use Phoundation\Utils\Json;
use Phoundation\Web\Html\Components\Div;
use Phoundation\Web\Html\Components\Script;
use Phoundation\Web\Html\Enums\EnumWebRenderMethods;
use Phoundation\Web\Html\Enums\Interfaces\EnumWebRenderMethodsInterface;
use Phoundation\Web\Html\Html;
use Phoundation\Web\Page;


/**
 * TreeViewer class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2024 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class TreeViewer extends Widget
{
    use DataUrl;
    use DataRenderMethod;


    /**
     * ProfileImage class constructor
     *
     * @param string|null $content
     */
    public function __construct(?string $content = null)
    {
        parent::__construct();

        if ($content) {
            $this->setSource($content);
        }
    }


    /**
     * Sets the internal source directly
     *
     * @param IteratorInterface|PDOStatement|array|string|null $source
     * @param array|null $execute
     * @return static
     */
    public function setSource(IteratorInterface|PDOStatement|array|string|null $source = null, array|null $execute = null): static
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

        Page::loadJavascript('mdb/plugins/js/treeview.min');

        if ($this->render_method === EnumWebRenderMethods::html) {
            // Render the tree-view using pure HTML
            return Div::new()
                ->setId($this->getId())
                ->setContent($this->renderHtml($this->source))
                ->addClass('treeview')
                ->addData(null, 'mdb-treeview-init')
                ->render() .
                Script::new('$("#' . $this->getId() . '").treeview()')->render();
        }

        // Render the tree-view using javascript data
        return Div::new()->setId($this->getId())->addClass('treeview')->render() .
           Script::new('
             const jsTreeview = document.getElementById("' . $this->getId() . '");
             const jsInstance = new Treeview(jsTreeview, {
               structure: ' . Tree::new($this->source)->getJson(true) . ',
             });
             // Fix issue where sub trees are missing the "collapse" class at first load
             $("div.tree-view li[role=\'tree-item\'] ul:not(.collapse)").addClass("collapse")')->render();
    }


    /**
     * Renders and returns tree-view content HTML for the given source
     *
     * @param array $source
     * @return string
     */
    protected function renderHtml(array $source, bool $child = false): string
    {
        $return = '<ul' . ($child ? ' class="collapse"' : '') . '>';

        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $return .= '<li><a class="not-a-link">' . $key . '</a>' . $this->renderHtml($value, true) . '</li>';

            } else {
                if ($this->url) {
                    $url     = str_replace(':ID', (string)$key, $this->url);
                    $return .= '<li><a href="' . $url . '">' . Html::safe($value) . '</a></li>';
                } else {
                    $return .= '<li>' . $value . '</li>';
                }
            }
        }

        return $return . '</ul>';
    }
}
