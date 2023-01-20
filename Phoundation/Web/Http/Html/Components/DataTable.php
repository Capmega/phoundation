<?php

namespace Phoundation\Web\Http\Html\Components;



/**
 * Class DataTable
 *
 *
 *
 * @author Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @license http://opensource.org/licenses/GPL-2.0 GNU Public License, Version 2
 * @copyright Copyright (c) 2022 Sven Olaf Oostenbrink <so.oostenbrink@gmail.com>
 * @package Phoundation\Web
 */
class DataTable extends Table
{
    /**
     * Renders and returns the HTML for this component
     *
     * @return string|null
     */
    public function render(): ?string
    {
        $html = '   <div id="example1_wrapper" class="dataTables_wrapper dt-bootstrap4">
                        <div class="row">
                            <div class="col-sm-12 col-md-6">
                                <div class="dt-buttons btn-group flex-wrap">
                                    <button class="btn btn-secondary buttons-copy buttons-html5" tabindex="0" aria-controls="example1" type="button">
                                        <span>Copy</span>
                                    </button> 
                                    <button class="btn btn-secondary buttons-csv buttons-html5" tabindex="0" aria-controls="example1" type="button">
                                        <span>CSV</span>
                                    </button> 
                                    <button class="btn btn-secondary buttons-excel buttons-html5" tabindex="0" aria-controls="example1" type="button">
                                        <span>Excel</span>
                                    </button> 
                                    <button class="btn btn-secondary buttons-pdf buttons-html5" tabindex="0" aria-controls="example1" type="button">
                                        <span>PDF</span>
                                    </button> 
                                    <button class="btn btn-secondary buttons-print" tabindex="0" aria-controls="example1" type="button">
                                        <span>Print</span>
                                    </button> 
                                    <div class="btn-group">
                                        <button class="btn btn-secondary buttons-collection dropdown-toggle buttons-colvis" tabindex="0" aria-controls="example1" type="button" aria-haspopup="true" aria-expanded="false">
                                            <span>Column visibility</span>
                                            <span class="dt-down-arrow"></span>
                                        </button>
                                    </div> 
                                </div>
                            </div>
                            <div class="col-sm-12 col-md-6">
                                <div id="example1_filter" class="dataTables_filter">
                                    <label>Search:<input type="search" class="form-control form-control-sm" placeholder="" aria-controls="example1"></label>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12">
                                <table id="example1" class="table table-bordered table-striped dataTable dtr-inline" aria-describedby="example1_info">
                                    <thead>
                                    <tr><th class="sorting sorting_asc" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Rendering engine: activate to sort column descending" aria-sort="ascending">Rendering engine</th><th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="Engine version: activate to sort column ascending">Engine version</th><th class="sorting" tabindex="0" aria-controls="example1" rowspan="1" colspan="1" aria-label="CSS grade: activate to sort column ascending">CSS grade</th></tr>
                                    </thead>
                                    <tbody>
                                    <tr class="odd">
                                    <td class="dtr-control sorting_1" tabindex="0">Gecko</td>
                                    
                                    
                                    <td class="">1</td>
                                    <td>A</td>
                                    </tr><tr class="even">
                                    <td class="sorting_1 dtr-control">Gecko</td>
                                    
                                    
                                    <td class="">1.1</td>
                                    <td>A</td>
                                    </tr><tr class="odd">
                                    <td class="sorting_1 dtr-control">Gecko</td>
                                    
                                    
                                    <td class="">1.2</td>
                                    <td>A</td>
                                    </tr><tr class="even">
                                    <td class="sorting_1 dtr-control">Gecko</td>
                                    
                                    
                                    <td class="">1.3</td>
                                    <td>A</td>
                                    </tr><tr class="odd">
                                    <td class="sorting_1 dtr-control">Gecko</td>
                                    
                                    
                                    <td class="">1.4</td>
                                    <td>A</td>
                                    </tr><tr class="even">
                                    <td class="sorting_1 dtr-control">Gecko</td>
                                     
                                    
                                    <td class="">1.5</td>
                                    <td>A</td>
                                    </tr><tr class="odd">
                                    <td class="sorting_1 dtr-control">Gecko</td>
                                    
                                    
                                    <td class="">1.6</td>
                                    <td>A</td>
                                    </tr><tr class="even">
                                    <td class="dtr-control sorting_1" tabindex="0">Gecko</td>
                                    
                                    
                                    <td class="">1.7</td>
                                    <td>A</td>
                                    </tr><tr class="odd">
                                    <td class="dtr-control sorting_1" tabindex="0">Gecko</td>
                                    
                                    
                                    <td class="">1.7</td>
                                    <td>A</td>
                                    </tr><tr class="even">
                                    <td class="dtr-control sorting_1" tabindex="0">Gecko</td>
                                    
                                    
                                    <td class="">1.7</td>
                                    <td>A</td>
                                    </tr></tbody>
                                    <tfoot>
                                    <tr><th rowspan="1" colspan="1">Rendering engine</th><th rowspan="1" colspan="1">Engine version</th><th rowspan="1" colspan="1">CSS grade</th></tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 col-md-5">
                                <div class="dataTables_info" id="example1_info" role="status" aria-live="polite">Showing 1 to 10 of 57 entries</div>
                            </div>                
                            <div class="col-sm-12 col-md-7">
                                ' . Pager::new()->render() . '
                            </div>
                        </div>
                    </div>';

        return $html;
    }
}