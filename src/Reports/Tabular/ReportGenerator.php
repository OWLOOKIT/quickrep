<?php

namespace Owlookit\Quickrep\Reports\Tabular;

use Illuminate\Pagination\LengthAwarePaginator;
use Owlookit\Quickrep\Exceptions\InvalidHeaderFormatException;
use Owlookit\Quickrep\Exceptions\InvalidHeaderTagException;
use Owlookit\Quickrep\Exceptions\UnexpectedHeaderException;
use Owlookit\Quickrep\Exceptions\UnexpectedMapRowException;
use Owlookit\Quickrep\Interfaces\CacheInterface;
use Owlookit\Quickrep\Interfaces\GeneratorInterface;
use Owlookit\Quickrep\Models\AbstractGenerator;
use Owlookit\Quickrep\Models\DatabaseCache;
use Owlookit\Quickrep\Models\QuickrepDatabase;
use Owlookit\Quickrep\Models\QuickrepReport;

class ReportGenerator extends AbstractGenerator implements GeneratorInterface
{
    const MAX_PAGING_LIMIT = 90000;

    protected $cache = null;

    public function __construct(DatabaseCache $cache)
    {
        $this->cache = $cache;
    }

    public function init(array $params = null)
    {
        parent::init($params);
    }

    public function getHeader(bool $includeSummary = false)
    {
        $mapped_header = []; //this is the result from the MapRow function

        $Table = clone $this->cache->getTable();
        $columns = array_diff(array_keys($this->cache->getColumns()), ['id']); // except id
        $first_row_of_data = $Table->select($columns)->first();

        // Get the column names and their types (column definitions) directly from the database
        $fields = $this->cache->getColumns();

        // convert stdClass to array
        $data_row = [];
        if (!is_null($first_row_of_data)) {
            foreach ($first_row_of_data as $key => $value) {
                $data_row[$key] = $value;  //MapRow needs  at least one row of real data to function properly...
            }
        }

        $has_data = true;
        if (count($data_row) == 0) {
            $data_row = [];
            $has_data = false;
        }

        $original_array_key = array_keys($data_row);

        /*
        Run the MapRow once to get the proper column name from the Report
         */
        $first_row_num = 0;
        if ($has_data) { //this means that the first row had results..
            //but here we are not sure if MapRow might change column names or add columns or even delete columns..
            //so we have to run it on the first row of actual data and then see what columns come back..
            $data_row = $this->cache->MapRow($data_row, $first_row_num);
            $mapped_header = array_keys($data_row);
        }

        /*
        This makes sure no new columns were added or removed.
         */
        if (count($original_array_key) != count($mapped_header)) {
            if (count($original_array_key) < count($mapped_header)) {
                $diff = array_diff($mapped_header, $original_array_key);
                $diff_text = var_export($diff, true);
                $original_text = var_export($original_array_key, true);
                throw new UnexpectedMapRowException(
                    "Quickrep Report Error: There are more values returned in the row than went into MapRow. These field names have been added:  $diff_text, was expecting $original_text"
                );
            } else {
                throw new UnexpectedMapRowException(
                    "Quickrep Report Error: There are fewer values returned in the row than went into MapRow"
                );
            }
        }


        /*
        Converts the header into an key/value pair. the key being the column name.
        Call the OverrideHeader function from the Report to override any kind of header data.
         */
        $header_format = array_combine($mapped_header, array_fill(0, count($mapped_header), null));
        $header_tags = array_combine($mapped_header, array_fill(0, count($mapped_header), null));
        $header_I18n = array_combine($mapped_header, array_fill(0, count($mapped_header), null));

        /*
        Determine the header format based on the column title and type
         */
        $header_format = self::DefaultColumnFormat($this->cache->getReport(), $header_format, $fields);

        /*
        Override the default header with what the report gives back,
        then check to see if the format and tags are valid
         */
        $this->cache->OverrideHeader($header_format, $header_tags, $header_I18n);

        foreach ($header_format as $name => $format) {
            if ($mapped_header && !in_array($name, $mapped_header)) {
                throw new UnexpectedHeaderException("Quickrep Report Error: Column header not found: {$name}");
            }

            if ($format !== null && !in_array($format, $this->cache->getReport()->VALID_COLUMN_FORMAT)) {
                throw new InvalidHeaderFormatException(
                    "Quickrep Report Error: Invalid column header format: {$format}"
                );
            }
        }

        foreach ($header_tags as $name => &$tags) {
            if ($mapped_header && !in_array($name, $mapped_header)) {
                throw new UnexpectedHeaderException("Quickrep Report Error: Column header not found: {$name}");
            }

            if ($tags == null) {
                $tags = [];
            }

            if (!is_array($tags)) {
                $tags = [$tags];
            }

            if (config("quickrep.RESTRICT_TAGS")) {
                $valid_tags = config("quickrep.TAGS");

                foreach ($tags as $tag) {
                    if (!in_array($tag, $valid_tags)) {
                        throw new InvalidHeaderTagException("Quickrep Report Error: Invalid tag: {$tag}");
                    }
                }
            }
        }

        /*
        Calculate the distinct count, sum, avg, min, max for fields
        ensuring compatibility with ManticoreSearch
    */
        $summary_data = [];
        if ($includeSummary) {
            $target_fields = [];
            $text_fields = [];
            $numeric_fields = [];

            foreach ($fields as $field_name => $field) {
                switch($field['type']) {
                    case 'string':
                    case 'text':
                        $text_fields[] = $field_name;
//                        $target_fields[] = "count(distinct `{$field_name}`) as `cnt_{$field_name}`";
                        break;
                    case 'decimal':
                    case 'bigint':
                    case 'integer':
                        $numeric_fields[] = $field_name;
                        $target_fields[] = "SUM({$field_name}) AS sum_{$field_name}";
                        $target_fields[] = "AVG({$field_name}) AS avg_{$field_name}";
                        $target_fields[] = "MIN({$field_name}) AS min_{$field_name}";
                        $target_fields[] = "MAX({$field_name}) AS max_{$field_name}";
                        break;
                    case 'date':
                        $target_fields[] = "FROM_UNIXTIME(avg(UNIX_TIMESTAMP(`{$field_name}`))) as `avg_{$field_name}`";
                        $target_fields[] = "MIN({$field_name}) AS min_{$field_name}";
                        $target_fields[] = "MAX({$field_name}) AS max_{$field_name}";
                        break;
                }
            }

            $target_fields = implode(", ", $target_fields);
            $ResultTable = clone $this->cache->getTable();
            // Запрос для числовых данных
            $result_main = $ResultTable->selectRaw($target_fields)->first();
//            $result_main = \DB::connection($this->cache->getConnectionName())->table($this->cache->getTableName())->selectRaw($target_fields)->first();
            /*
                Обрабатываем числовые результаты
            */
            $summary_data = [];
            foreach ($numeric_fields as $field_name) {
                $summary_data[$field_name] = [
                    "sum" => $result_main->{"sum_{$field_name}"} ?? null,
                    "average" => $result_main->{"avg_{$field_name}"} ?? null,
                    "minimum" => $result_main->{"min_{$field_name}"} ?? null,
                    "maximum" => $result_main->{"max_{$field_name}"} ?? null,
                ];
            }

//            foreach ($result as $col => $value) {
//                $reg = '/^(cnt|sum|avg|std|min|max)_(.*)$/i';
//                if (preg_match($reg, $col, $matches)) {
//                    $summary_type = $matches[1];
//                    $column_name = $matches[2];
//                    static $type_value = [
//                        "cnt" => "count",
//                        "sum" => "sum",
//                        "avg" => "average",
//                        "std" => "standard_deviation",
//                        "min" => "minimum",
//                        "max" => "maximum",
//                    ];
//                    $summary_data[$column_name][$type_value[$summary_type]] = $value;
//                }
//            }

            /*
                @TODO: выполняем отдельные запросы для текстовых скалярных полей:
            */
//            foreach ($text_fields as $text_field) {
//                $result_text = $ResultTable->selectRaw("GROUP_CONCAT({$text_field}) AS concat_{$text_field}")->first();
//                $concatenated_values = $result_text->{"concat_{$text_field}"} ?? '';
//
//                // Подсчитываем уникальные значения в PHP
//                $unique_values_count = $concatenated_values ? count(array_unique(explode(",", $concatenated_values))) : 0;
//
//                $summary_data[$text_field] = [
//                    "count" => $unique_values_count
//                ];
//            }
        }

        /*
        Merge format/tags/summary information together into 1 array
         */
        $header = [];
        if (!config('app.locales')) {
            $error = "The requested Quickrep Report needs app.locales config settings";
            throw new Exception($error);
        }
        foreach ($header_format as $name => $field) {
            $title = ucwords(str_replace('_', ' ', $name), "\t\r\n\f\v ");
            $column = [
                'field' => $name,
                'title' => $title,
                'title_I18n' => $header_I18n[$name] ?? array_fill_keys(config('app.locales'), $title),
                'format' => $header_format[$name] ?? 'TEXT',
                'sortable' => in_array($field, $this->cache->getReport()->SORTABLE_COLUMN_FORMAT),
                'filterable' => in_array($field, $this->cache->getReport()->FILTERABLE_COLUMN_FORMAT),
                'tags' => $header_tags[$name] ?? [],
            ];

            if (key_exists($name, $summary_data)) {
                $column['summary'] = $summary_data[$name];
            }

            $header[] = $column;
        }

        return $header;
    }

    /**
     * DefaultColumnFormat
     * Attempts to return the format of the column based on the column name and the predefine header configuration
     *
     * @param QuickrepReport $Report
     * @param array $format
     * @param array $fields
     * @return array
     */
    private static function DefaultColumnFormat(QuickrepReport $Report, array $format, array $fields): array
    {
        foreach ($format as $name => $value) {

            if (QuickrepDatabase::isColumnInKeyArray($name, $Report->DETAIL)) {
                $format[$name] = 'DETAIL';
            } else if (QuickrepDatabase::isColumnInKeyArray($name, $Report->URL) && in_array($fields[$name]["type"], ["string"])) {
                $format[$name] = 'URL';
            } else if (QuickrepDatabase::isColumnInKeyArray($name, $Report->CURRENCY) /* && in_array($fields[$name]["type"],["integer","decimal"])*/) {
                $format[$name] = 'CURRENCY';
            } else if (QuickrepDatabase::isColumnInKeyArray($name, $Report->NUMBER) /* && in_array($fields[$name]["type"],["integer","decimal"])*/) {
                $format[$name] = 'NUMBER';
            } else if (QuickrepDatabase::isColumnInKeyArray($name, $Report->DECIMAL) /* && in_array($fields[$name]["type"],["integer","decimal"])*/) {
                $format[$name] = 'DECIMAL';
            } else if (in_array($fields[$name]["type"], ["date", "time", "datetime"])) {
                $format[$name] = strtoupper($fields[$name]["type"]);
            } else if (QuickrepDatabase::isColumnInKeyArray($name, $Report->PERCENT) /* && in_array($fields[$name]["type"],["integer","decimal"])*/) {
                $format[$name] = 'PERCENT';
            }

        }

        return $format;
    }

    /**
     * ReportModelJson
     * Return the QuickrepReport as a pagable model
     *
     * @param QuickrepReport $Report
     * @return Collection
     */
    public function toJson()
    {
        $Report = $this->cache->getReport();

        $paging_length = $Report->getInput("length") ?? 1000;

        if ($paging_length > 500000 && $paging_length > 0) {
            $paging_length = 500000;
        }

        if ($paging_length <= 0) {
            $paging_length = self::MAX_PAGING_LIMIT;
        }
        /* no limit*/

        /*
        If there is a filter, lets apply it to each column
         */
        $filter = $Report->getInput('filter');
        if ($filter && is_array($filter)) {
            $associated_filter = [];
            foreach ($filter as $f => $item) {
                $field = key($item);
                $value = $item[$field];
                $associated_filter[$field] = $value;
            }

            $this->addFilter($associated_filter);
        }

        $orderBy = $Report->getInput('order') ?? [];

        // This is where we want to merge in our "defaults" ??
        // The defaults are merged into the 'order' input variable in the function QuickrepReport::setDefaultSortOrder()
        // So we don't need to worry about it here.
        $this->orderBy($orderBy);

        $paging = $this->paginate($paging_length);

        $paging->setCollection($paging->getCollection()->map(function ($value, $key) use ($Report) {
            $value_array = $this->objectToArray($value);
            $mapped_row = $Report->MapRow($value_array, $key);
            return $this->arrayToObject(array_map(fn($v) => mb_convert_encoding($v, 'UTF-8', 'UTF-8'), $mapped_row));
        }));

        $reportSummary = new ReportSummaryGenerator($this->cache);
        $custom = collect($reportSummary->toJson($Report));

        $paginationData = $paging->toArray();
        $merged = $custom->merge(array_merge($paginationData, ['data' => $paginationData['data']]));

        if ($paging_length == self::MAX_PAGING_LIMIT) {
            $merged['per_page'] = 0;
        }

        return $merged;
    }

    public function paginate($perPage)
    {
        $page = request('page', 1);
        $offset = ($page - 1) * $perPage;

        $Pager = clone $this->cache->getTable();
        $total = $Pager->count();

        $maxMatches = min(max($total, ($page * $perPage) + $perPage * 10), 1000000);

        $sql = $Pager->toSql() . " LIMIT {$perPage} OFFSET {$offset} OPTION max_matches = {$maxMatches}";
        $bindings = $Pager->getBindings();

        $items = collect(\DB::connection($this->cache->getConnectionName())->select($sql, $bindings));

        return new LengthAwarePaginator($items, $total, $perPage, $page, [
            'path' => request()->url(),
            'query' => request()->query(),
        ]);
    }

    public function getCollection()
    {
        $Report = $this->cache->getReport();

        /*
        If there is a filter, lets apply it to each column
         */
        $filter = $Report->getInput('filter');
        if ($filter && is_array($filter)) {
            $associated_filter = [];
            foreach ($filter as $f => $item) {
                $field = key($item);
                $value = $item[$field];
                $associated_filter[$field] = $value;
            }

            $this->addFilter($associated_filter);
        }

        $orderBy = $Report->getInput('order') ?? [];

        $this->orderBy($orderBy);

        $table = $this->cache->getTable();

        /*
        Transform each row using $Report->MapRow()
         */
        $collection = $table->limit(self::MAX_PAGING_LIMIT)->get();

        $collection->transform(function ($value, $key) use ($Report) {
            $value_array = $this->objectToArray($value);
            $mapped_row = $Report->MapRow($value_array, $key);
            $mapped_and_encoded = [];
            foreach ($mapped_row as $mapped_key => $mapped_value) {
                $mapped_and_encoded[$mapped_key] = mb_convert_encoding($mapped_value, 'UTF-8', 'UTF-8');
            }
            return $this->arrayToObject($mapped_and_encoded);
        });

        return $collection;
    }

    function objectToArray($d)
    {
        if (is_object($d)) {
            // Gets the properties of the given object
            // with get_object_vars function
            $d = get_object_vars($d);
        }

        if (is_array($d)) {
            /*
            * Return array converted to object
            * Using __FUNCTION__ (Magic constant)
            * for recursive call
            */
            return array_map([$this, 'objectToArray'], $d);
        } else {
            // Return array
            return $d;
        }
    }

    function arrayToObject($arr)
    {
        return json_decode(json_encode($arr));
    }

}
