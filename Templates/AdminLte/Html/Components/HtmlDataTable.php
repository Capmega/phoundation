<?php

declare(strict_types=1);

namespace Templates\AdminLte\Html\Components;

use Phoundation\Web\Html\Html;
use Phoundation\Web\Html\Layouts\GridRow;
use Phoundation\Web\Html\Renderer;


/**
 * AdminLte Template HtmlDataTable class
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2023 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Templates\AdminLte
 */
class HtmlDataTable extends Renderer
{
    /**
     * Table class constructor
     */
    public function __construct(\Phoundation\Web\Html\Components\HtmlTable $element)
    {
        $element->addClass('table');
        parent::__construct($element);
    }


    /**
     * Renders and returns the HTML for this object
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $id    = $this->render_object->getId();
        $table = GridRow::new()->addColumn(parent::render());

        return '<div id="' . Html::safe($id) . '_wrapper" class="dataTables_wrapper dt-bootstrap4">' .
                    $table->render() .
               '</div>';

        return $this->render;

//<script defer="" referrerpolicy="origin" src="/cdn-cgi/zaraz/s.js?z=JTdCJTIyZXhlY3V0ZWQlMjIlM0ElNUIlNUQlMkMlMjJ0JTIyJTNBJTIyQWRtaW5MVEUlMjAzJTIwJTdDJTIwRGF0YVRhYmxlcyUyMiUyQyUyMnglMjIlM0EwLjE0MzQ5MzM0MTYyMTU2NjI0JTJDJTIydyUyMiUzQTE5MjAlMkMlMjJoJTIyJTNBMTA4MCUyQyUyMmolMjIlM0E2ODMlMkMlMjJlJTIyJTNBMTUzNiUyQyUyMmwlMjIlM0ElMjJodHRwcyUzQSUyRiUyRmFkbWlubHRlLmlvJTJGdGhlbWVzJTJGdjMlMkZwYWdlcyUyRnRhYmxlcyUyRmRhdGEuaHRtbCUyMiUyQyUyMnIlMjIlM0ElMjJodHRwcyUzQSUyRiUyRmFkbWlubHRlLmlvJTJGdGhlbWVzJTJGdjMlMkZwYWdlcyUyRnRhYmxlcyUyRnNpbXBsZS5odG1sJTIyJTJDJTIyayUyMiUzQTI0JTJDJTIybiUyMiUzQSUyMlVURi04JTIyJTJDJTIybyUyMiUzQTQ4MCUyQyUyMnElMjIlM0ElNUIlNUQlN0Q="></script><script nonce="1820e903-0f3d-490d-a5c9-82318f3f5e0f">(function(w,d){!function(f,g,h,i){f[h]=f[h]||{};f[h].executed=[];f.zaraz={deferred:[],listeners:[]};f.zaraz.q=[];f.zaraz._f=function(j){return function(){var k=Array.prototype.slice.call(arguments);f.zaraz.q.push({m:j,a:k})}};for(const l of["track","set","debug"])f.zaraz[l]=f.zaraz._f(l);f.zaraz.init=()=>{var m=g.getElementsByTagName(i)[0],n=g.createElement(i),o=g.getElementsByTagName("title")[0];o&&(f[h].t=g.getElementsByTagName("title")[0].text);f[h].x=Math.random();f[h].w=f.screen.width;f[h].h=f.screen.height;f[h].j=f.innerHeight;f[h].e=f.innerWidth;f[h].l=f.location.href;f[h].r=g.referrer;f[h].k=f.screen.colorDepth;f[h].n=g.characterSet;f[h].o=(new Date).getTimezoneOffset();if(f.dataLayer)for(const s of Object.entries(Object.entries(dataLayer).reduce(((t,u)=>({...t[1],...u[1]})))))zaraz.set(s[0],s[1],{scope:"page"});f[h].q=[];for(;f.zaraz.q.length;){const v=f.zaraz.q.shift();f[h].q.push(v)}n.defer=!0;for(const w of[localStorage,sessionStorage])Object.keys(w||{}).filter((y=>y.startsWith("_zaraz_"))).forEach((x=>{try{f[h]["z_"+x.slice(7)]=JSON.parse(w.getItem(x))}catch{f[h]["z_"+x.slice(7)]=w.getItem(x)}}));n.referrerPolicy="origin";n.src="/cdn-cgi/zaraz/s.js?z="+btoa(encodeURIComponent(JSON.stringify(f[h])));m.parentNode.insertBefore(n,m)};["complete","interactive"].includes(g.readyState)?zaraz.init():f.addEventListener("DOMContentLoaded",zaraz.init)}(w,d,"zarazData","script");})(window,document);</script><link rel="stylesheet" type="text/css" href="chrome-extension://enejkfhimljloggnimhebadbjajhgkad/font/Inter.css">
//<style type="text/css">
//        @font-face {
//        font-weight: 400;
//  font-style:  normal;
//  font-family: 'Circular-Loom';
//
//  src: url('chrome-extension://liecbddmkiiihnedobmlmillhodjkdmb/fonts/CircularXXWeb-Book.woff2') format('woff2');
//}
//
//@font-face {
//        font-weight: 500;
//  font-style:  normal;
//  font-family: 'Circular-Loom';
//
//  src: url('chrome-extension://liecbddmkiiihnedobmlmillhodjkdmb/fonts/CircularXXWeb-Medium.woff2') format('woff2');
//}
//
//@font-face {
//        font-weight: 700;
//  font-style:  normal;
//  font-family: 'Circular-Loom';
//
//  src: url('chrome-extension://liecbddmkiiihnedobmlmillhodjkdmb/fonts/CircularXXWeb-Bold.woff2') format('woff2');
//}
//
//@font-face {
//        font-weight: 900;
//  font-style:  normal;
//  font-family: 'Circular-Loom';
//
//  src: url('chrome-extension://liecbddmkiiihnedobmlmillhodjkdmb/fonts/CircularXXWeb-Black.woff2') format('woff2');
//}</style>


//                                <table id="' . Html::safe($id) . '" class="table table-bordered table-striped dataTable dtr-inline" aria-describedby="' . Html::safe($id) . '_info">
//                                    <thead>
//                                    <tr><th class="sorting sorting_asc" tabindex="0" aria-controls="' . Html::safe($id) . '" rowspan="1" colspan="1" aria-label="Rendering engine: activate to sort column descending" aria-sort="ascending">Rendering engine</th><th class="sorting" tabindex="0" aria-controls="' . Html::safe($id) . '" rowspan="1" colspan="1" aria-label="Engine version: activate to sort column ascending">Engine version</th><th class="sorting" tabindex="0" aria-controls="' . Html::safe($id) . '" rowspan="1" colspan="1" aria-label="CSS grade: activate to sort column ascending">CSS grade</th></tr>
//                                    </thead>
//                                    <tbody>
//                                    <tr class="odd">
//                                    <td class="dtr-control sorting_1" tabindex="0">Gecko</td>
//
//
//                                    <td class="">1</td>
//                                    <td>A</td>
//                                    </tr><tr class="even">
//                                    <td class="sorting_1 dtr-control">Gecko</td>
//
//
//                                    <td class="">1.1</td>
//                                    <td>A</td>
//                                    </tr><tr class="odd">
//                                    <td class="sorting_1 dtr-control">Gecko</td>
//
//
//                                    <td class="">1.2</td>
//                                    <td>A</td>
//                                    </tr><tr class="even">
//                                    <td class="sorting_1 dtr-control">Gecko</td>
//
//
//                                    <td class="">1.3</td>
//                                    <td>A</td>
//                                    </tr><tr class="odd">
//                                    <td class="sorting_1 dtr-control">Gecko</td>
//
//
//                                    <td class="">1.4</td>
//                                    <td>A</td>
//                                    </tr><tr class="even">
//                                    <td class="sorting_1 dtr-control">Gecko</td>
//
//
//                                    <td class="">1.5</td>
//                                    <td>A</td>
//                                    </tr><tr class="odd">
//                                    <td class="sorting_1 dtr-control">Gecko</td>
//
//
//                                    <td class="">1.6</td>
//                                    <td>A</td>
//                                    </tr><tr class="even">
//                                    <td class="dtr-control sorting_1" tabindex="0">Gecko</td>
//
//
//                                    <td class="">1.7</td>
//                                    <td>A</td>
//                                    </tr><tr class="odd">
//                                    <td class="dtr-control sorting_1" tabindex="0">Gecko</td>
//
//
//                                    <td class="">1.7</td>
//                                    <td>A</td>
//                                    </tr><tr class="even">
//                                    <td class="dtr-control sorting_1" tabindex="0">Gecko</td>
//
//
//                                    <td class="">1.7</td>
//                                    <td>A</td>
//                                    </tr></tbody>
//                                    <tfoot>
//                                    <tr><th rowspan="1" colspan="1">Rendering engine</th><th rowspan="1" colspan="1">Engine version</th><th rowspan="1" colspan="1">CSS grade</th></tr>
//                                    </tfoot>
//                                </table>
    }
}
