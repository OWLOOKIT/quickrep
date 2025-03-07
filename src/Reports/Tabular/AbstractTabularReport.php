<?php
/**
 * Created by PhpStorm.
 * User: owlookit
 * Date: 1/14/19
 * Time: 9:51 AM
 */

namespace Owlookit\Quickrep\Reports\Tabular;

use Owlookit\Quickrep\Models\QuickrepReport;

abstract class AbstractTabularReport extends QuickrepReport
{

    /**
     * $VALID_COLUMN_FORMAT
     * Valid Format a column header can be. This is used to validate OverrideHeader
     *
     * @var array
     */
    public $VALID_COLUMN_FORMAT = [
        'TEXT',
        'STRING',
        'DETAIL',
        'URL',
        'CURRENCY',
        'NUMBER',
        'DECIMAL',
        'DATE',
        'DATETIME',
        'TIME',
        'PERCENT'
    ];

    public $SORTABLE_COLUMN_FORMAT = [
        'STRING',
        'NUMBER',
        'DECIMAL',
        'DATE',
        'DATETIME',
        'TIME',
        'PERCENT'
    ];

    public $FILTERABLE_COLUMN_FORMAT = [
        'TEXT',
        'STRING',
        'DETAIL',
        'URL',
        'CURRENCY',
        'NUMBER',
        'DECIMAL',
        'DATE',
        'DATETIME',
        'TIME',
        'PERCENT'
    ];


    /**
     * $DETAIL
     * Header stub that will determine if a header is a 'SENTENCE' format
     *
     * @var array
     */
    public $DETAIL = ['Sentence'];

    /**
     * $URL
     * Header stub that will determine if a header is a 'URL' format
     *
     * @var array
     */
    public $URL = ['URL'];

    /**
     * $CURRENCY
     * Header stub that will determine if a header is a 'CURRENCY' format
     *
     * @var array
     */
    public $CURRENCY = ['Amt', 'Amount', 'Paid', 'Cost'];

    /**
     * $NUMBER
     * Header stub that will determine if a header is a 'NUMBER' format
     *
     * @var array
     */
    public $NUMBER = ['id', '#', 'Num', 'Sum', 'Total', 'Cnt', 'Count'];

    /**
     * $DECIMAL
     * Header stub that will determine if a header is a 'DECIMAL' format
     *
     * @var array
     */
    public $DECIMAL = ['Avg', 'Average'];

    /**
     * $PERCENT
     * Header stub that will determine if a header is a 'PERCENTAGE' format
     *
     * @var array
     */
    public $PERCENT = ['Percent', 'Ratio', 'Perentage'];


    /**
     * $SUGGEST_NO_SUMMARY
     * This will mark the column that should not be used for statistical summary.
     * Any column found with a a 'NO_SUMMARY' flag attached to its column header
     *
     * @var array
     */
    public $SUGGEST_NO_SUMMARY = [];

}
