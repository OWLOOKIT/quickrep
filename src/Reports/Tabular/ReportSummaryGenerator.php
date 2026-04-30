<?php

namespace Owlookit\Quickrep\Reports\Tabular;

use Owlookit\Quickrep\Interfaces\GeneratorInterface;
use Owlookit\Quickrep\Services\PreparedSourceStatusResolver;

class ReportSummaryGenerator extends ReportGenerator implements GeneratorInterface
{
    public function toJson()
    {
        $preparedSourceStatus = app(PreparedSourceStatusResolver::class)
            ->resolveForReport($this->cache->getReport());

        return [
            'Report_Name' => $this->cache->getReport()->GetReportName(),
            'Report_Name_I18n' => $this->cache->getReport()->GetReportNameI18n(),
            'Report_Description' => $this->cache->getReport()->GetReportDescription(),
            'Report_Description_I18n' => $this->cache->getReport()->GetReportDescriptionI18n(),
            'selected-data-option' => $this->cache->getReport()->getParameter('data-option'),
            'columns' => $this->runSummary(),

            'prepared_source' => $preparedSourceStatus?->toArray(),

            'cache_meta_generated_this_request' => $this->cache->getGeneratedThisRequest(),
            'cache_meta_last_generated' => $this->cache->getLastGenerated(),
            'cache_meta_expire_time' => $this->cache->getExpireTime(),
            'cache_meta_cache_enabled' => $this->cache->getReport()->isCacheEnabled(),
        ];
    }

    public function runSummary()
    {
        return $this->getHeader(true);
    }
}