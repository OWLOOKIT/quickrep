<?php

namespace Owlookit\Quickrep\Models;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Owlookit\Quickrep\Exceptions\InvalidDatabaseTableException;

class DatabaseCache
{
    protected $exists = false;
    protected $doClearCache = false;
    protected $generatedThisRequest = false;
    protected $columns = [];
    protected $cache_table = null;
    protected $report = null;
    protected $key = null;
    protected $connectionName = null;
    protected $timezone = null;
    protected $cache_pdo = null;
    protected $source_pdo = null;

    public function __construct(QuickrepReport $report, $connectionName = null)
    {
        $this->report = $report;
        $this->timezone = config('app.timezone');

        // by default use the quickrep cache DB
        $this->connectionName = $connectionName ?? quickrep_cache_db();

        $this->cache_pdo = QuickrepDatabase::connection($this->connectionName)->getPdo();
        $this->source_pdo = QuickrepDatabase::connection(config('quickrep.QUICKREP_DB_CONNECTION'))->getPdo();

        $cacheDatabaseSource = $report->getCacheDatabaseSource();

        if ($cacheDatabaseSource !== null && isset($cacheDatabaseSource['database'], $cacheDatabaseSource['table'])) {
            $this->connectionName = $cacheDatabaseSource['database'];

            try {
                QuickrepDatabase::configure($this->connectionName);
            } catch (\Exception $e) {
                throw new InvalidDatabaseTableException(
                    "Попытка использовать `{$this->connectionName}`, но БД не существует или доступ запрещен"
                );
            }

            $this->key = $cacheDatabaseSource['table'];
            $this->cache_table = QuickrepDatabase::connection($this->connectionName)->table("{$this->key}");
        } else {
            $this->key = $this->keygen(strtolower($this->report->getClassName()));
            $this->cache_table = QuickrepDatabase::connection($this->connectionName)->table("{$this->key}");
        }

        $clear_cache = filter_var($report->getInput('clear_cache'), FILTER_VALIDATE_BOOLEAN) === true;
        $this->setDoClearCache($clear_cache);

        if (!$this->exists() || !$report->isCacheEnabled() || $this->getDoClearCache() || $this->isCacheExpired()) {
            $this->createTable();
            $this->generatedThisRequest = true;
        }

        try {
            $this->columns = QuickrepDatabase::getTableColumnDefinition($this->getTableName(), $this->connectionName);
        } catch (\Exception $e) {
            throw new InvalidDatabaseTableException(
                "Попытка доступа к таблице `{$this->getTableName()}` в БД `{$this->connectionName}`, но она не существует или доступ запрещен"
            );
        }
    }

    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /*
        This function generates the name of the cache table.
        It refers to the getDataIdentityKey() function on the report...
    */
    protected function keygen($prefix = "")
    {
        $key = $this->report->getDataIdentityKey($prefix);
        return $key;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function getTable()
    {
        return $this->cache_table;
    }

    public function getTableName()
    {
        return $this->cache_table->from;
    }

    /**
     * @return \Illuminate\Config\Repository|\Illuminate\Foundation\Application|mixed|object|null
     */
    public function getTimezone(): mixed
    {
        return $this->timezone;
    }

    public function getReport()
    {
        return $this->report;
    }

    public function exists(): bool
    {
        return QuickrepDatabase::hasTable($this->cache_table->from, $this->connectionName);
    }

    public function setDoClearCache($doClearCache)
    {
        $this->doClearCache = $doClearCache;
    }

    public function dropTable()
    {
        QuickrepDatabase::drop($this->key, $this->connectionName);
        QuickrepMeta::where('key', $this->key)->delete();
    }

    public function getDoClearCache()
    {
        return $this->doClearCache;
    }

    public function isCacheExpired()
    {
        $expireTime = $this->getExpireTime();
        if (!$expireTime) {
            return true;
        }
        return Carbon::now()->setTimezone($this->timezone)->gte($expireTime);
    }

    public function MapRow(array $row, int $row_number)
    {
        return $this->report->MapRow($row, $row_number);
    }

    public function OverrideHeader(array &$format, array &$tags, ?array &$I18n = []): void
    {
        $this->report->OverrideHeader($format, $tags, $I18n);
    }

    public function getIndividualQueries()
    {
        $sql = $this->report->GetSQL();
        if (!$sql) {
            return false;
        }

        $all_queries = [];
        $sql = is_array($sql) ? $sql : [$sql];

        foreach ($sql as $query) {
            foreach (explode(";", $query) as $single_query) {
                if (!empty(trim($single_query))) {
                    $all_queries[] = trim($single_query);
                }
            }
        }
        return $all_queries;
    }

    public function getColumns()
    {
        return $this->columns;
    }

    public function createTable()
    {
        $tableName = $this->getTableName();

        $this->cache_pdo->exec("DROP TABLE IF EXISTS `{$tableName}`");

        $queries = $this->getIndividualQueries();
        if (!$queries) {
            throw new \LogicException("Не найдены SQL-запросы для создания таблицы");
        }

        $firstQuery = $queries[0];

        if (strpos(strtoupper($firstQuery), "SELECT") !== 0) {
            throw new \LogicException("Ошибка: первый запрос должен быть SELECT.");
        }

        $columns = $this->getColumnsFromQuery($firstQuery);

        $createTableSQL = "CREATE TABLE `{$tableName}` (" . implode(", ", $columns) . ") min_prefix_len = '3' min_infix_len = '3' expand_keywords = '1'";
        $this->cache_pdo->exec($createTableSQL);

        $this->bulkInsertData($firstQuery, $tableName);

        QuickrepMeta::updateOrCreate(
            ['key' => $tableName, 'meta_key' => 'created_at'],
            ['meta_value' => Carbon::now()->toDateTimeString()]
        );
    }

    private function getColumnsFromQuery($query): array
    {
        // LIMIT 1 для получения структуры колонок и проверки наличия данных
        $stmt = $this->source_pdo->query($query . " LIMIT 0");
        $columnCount = $stmt->columnCount();

        $columns = [];
        for ($i = 0; $i < $columnCount; $i++) {
            $meta = $stmt->getColumnMeta($i);
            [$columnName, $explicitType] = $this->parseColumnName($meta['name']);
            $columnType = $explicitType ?? $this->mapColumnType($meta['native_type']);
            $columns[] = "`{$columnName}` {$columnType}";
        }

        $rowWithData = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $columns;
    }

    private function parseColumnName(string $name): array
    {
        $parts = explode('__', $name);
        $columnName = $parts[0];
        $explicitType = $parts[1] ?? null;

        // Приводим тип к верхнему регистру, если он указан
        if ($explicitType !== null) {
            $explicitType = strtoupper($explicitType);
        }

        return [$columnName, $explicitType];
    }

    private function mapColumnType(string $type): string
    {
        // Маппинг типов PostgreSQL на типы Manticoresearch
        return match (strtolower($type)) {
            'smallint', 'integer', 'int', 'int2', 'int4', 'serial', 'serial2', 'serial4' => 'INT',
            'bigint', 'int8', 'serial8' => 'BIGINT',
            'decimal', 'numeric', 'real', 'double precision', 'float4', 'float8' => 'FLOAT',
            'boolean', 'bool' => 'BOOL',
            'date', 'timestamp', 'timestamp without time zone', 'timestamp with time zone', 'time', 'time without time zone', 'time with time zone' => 'TIMESTAMP',
            'character varying', 'varchar', 'character', 'char', 'text', 'citext' => 'TEXT',
            'bytea', 'json', 'jsonb' => 'JSON',
            'inet', 'cidr', 'macaddr', 'macaddr8', 'uuid', 'xml' => 'TEXT',
            'point', 'line', 'lseg', 'box', 'path', 'polygon', 'circle',
            'int4range', 'int8range', 'numrange', 'tsrange', 'tstzrange', 'daterange' => 'JSON',
            default => str_contains($type, '[]') ? 'JSON' : 'TEXT',
        };
    }

    private function getColumnsFromTempTableQuery($query, $nameUnique): array
    {
        try {
            $this->source_pdo->beginTransaction();

            $createTempTableQuery = "CREATE TEMPORARY TABLE temp_{$nameUnique} AS {$query} LIMIT 1";
            $this->source_pdo->exec($createTempTableQuery);

            $columnInfoQuery = "
            SELECT column_name, data_type
            FROM information_schema.columns
            WHERE table_name = 'temp_{$nameUnique}';
        ";
            $stmt = $this->source_pdo->query($columnInfoQuery);
            $columnInfo = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $this->source_pdo->exec("DROP TABLE IF EXISTS temp_{$nameUnique}");

            $this->source_pdo->commit();

            if (empty($columnInfo)) {
                throw new \LogicException("Не удалось определить структуру таблицы.");
            }

            foreach ($columnInfo as $column) {
                $columns[] = "`{$column['column_name']}` " . $this->mapColumnType($column['data_type']);
            }

            return $columns;
        } catch (\Exception $e) {
            $this->source_pdo->rollBack();
            throw new \Exception("Ошибка при получении информации о столбцах: " . $e->getMessage());
        }
    }


    private function bulkInsertData($query, $tableName)
    {
        $stmt = $this->source_pdo->query($query);
        $batchData = [];
        $batchSize = 1000;

        // Fetch the first row to determine the column mappings
        $firstRow = $stmt->fetch(\PDO::FETCH_ASSOC);
        if ($firstRow === false) {
            return; // No data to insert
        }

        // Determine the column mappings
        $columnMappings = $this->getColumnMappings(array_keys($firstRow));

        // Process the first row
        $batchData[] = $this->mapRowData($firstRow, $columnMappings);

        // Process subsequent rows
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $batchData[] = $this->mapRowData($row, $columnMappings);

            if (count($batchData) >= $batchSize) {
                $this->insertBatch($tableName, array_keys($columnMappings), $batchData);
                $batchData = [];
            }
        }

        // Insert any remaining data
        if (!empty($batchData)) {
            $this->insertBatch($tableName, array_keys($columnMappings), $batchData);
        }
    }


    private function getColumnMappings(array $sourceColumns): array
    {
        $mappings = [];
        foreach ($sourceColumns as $col) {
            [$baseName,] = $this->parseColumnName($col);
            $mappings[$baseName] = $col;
        }
        return $mappings;
    }

    private function mapRowData(array $row, array $columnMappings): string
    {
        $mappedRow = [];

        foreach ($columnMappings as $baseName => $sourceName) {
            // Проверяем, есть ли колонка в оригинальной строке данных
            $value = $row[$sourceName] ?? null;

            // Если колонка содержит дату, конвертируем её в UNIX timestamp
            if (isset($value) && strtotime($value) !== false) {
                $value = strtotime($value);
            }

            // Безопасно экранируем значение
            $mappedRow[$baseName] = is_null($value) ? 'NULL' : $this->cache_pdo->quote($value);
        }

        return '(' . implode(", ", $mappedRow) . ')';

//        $mappedRow = [];
//        foreach ($columnMappings as $baseName => $sourceName) {
//            $mappedRow[$baseName] = $this->cache_pdo->quote($row[$sourceName]);
//        }
//        return '(' . implode(", ", $mappedRow) . ')';
    }

    private function insertBatch($table, $columns, $batchData)
    {
        $columnsStr = implode(", ", array_map(fn($col) => "`$col`", $columns));
        $valuesStr = implode(", ", $batchData);
        $sql = "INSERT INTO `$table` ($columnsStr) VALUES $valuesStr;";
        $this->cache_pdo->exec($sql);
    }

    public function getLastGenerated()
    {
        $meta = QuickrepMeta::where('key', $this->getTableName())
            ->where('meta_key', 'created_at')
            ->first();

        return $meta ? $meta->meta_value : null;
    }

    public function getExpireTime()
    {
        if (!$this->report->isCacheEnabled()) {
            return false;
        }

        $lastGenerated = $this->getLastGenerated();

        if (is_null($lastGenerated)) {
            return false;
        }

        $expireTime = Carbon::parse($lastGenerated)
            ->setTimezone($this->timezone)
            ->addSeconds($this->report->howLongToCacheInSeconds());

        return $expireTime->toDateTimeString();
    }

    public function getGeneratedThisRequest()
    {
        return $this->generatedThisRequest;
    }
}