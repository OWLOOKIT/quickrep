# Creating Reports

Reports extend `QuickrepReport`.

---

## Basic Example

```php
use Owlookit\Quickrep\Models\QuickrepReport;

class SchoolActivityReport extends QuickrepReport
{
    public function GetSQL()
    {
        return DB::connection(quickrep_source_db())
            ->table('mv_school_activity')
            ->select([
                'school_id',
                'active_users',
                'total_visits'
            ]);
    }
}
```

Supported Return Types from GetSQL()
•	string
•	array
•	QueryBuilder
•	EloquentBuilder
•	array of above

Bindings are automatically compiled into raw SQL.

Accessing Input Parameters
```php
$start = $this->getInput('start_date');
```

Cache Control

You can override cache table source:
```php
public function getCacheDatabaseSource(): ?array
{
    return [
        'connection' => 'manticore',
        'table' => 'custom_cache_table'
    ];
}
```