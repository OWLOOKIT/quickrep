<?php

namespace Owlookit\Quickrep\Reports\Tabular;

use Owlookit\Quickrep\Interfaces\CacheInterface;
use Owlookit\Quickrep\Interfaces\GeneratorInterface;
use Owlookit\Quickrep\Models\QuickrepReport;
use Owlookit\Quickrep\Exceptions\InvalidDatabaseTableException;
use Owlookit\Quickrep\Exceptions\InvalidHeaderFormatException;
use Owlookit\Quickrep\Exceptions\InvalidHeaderTagException;
use Owlookit\Quickrep\Exceptions\UnexpectedHeaderException;
use Owlookit\Quickrep\Exceptions\UnexpectedMapRowException;
use \DB;

class ReportSummaryGenerator extends ReportGenerator implements GeneratorInterface
{

    public function toJson()
    {
        return [
            'Report_Name' => $this->cache->getReport()->GetReportName(),
            'Report_Name_I18n' => $this->cache->getReport()->GetReportNameI18n(),
            'Report_Description' => $this->cache->getReport()->GetReportDescription(),
            'Report_Description_I18n' => $this->cache->getReport()->GetReportDescriptionI18n(),
            'selected-data-option' => $this->cache->getReport()->getParameter( 'data-option' ),
            'columns' => $this->runSummary(),
            'cache_meta_generated_this_request' => $this->cache->getGeneratedThisRequest(),
            'cache_meta_last_generated' => $this->cache->getLastGenerated(),
            'cache_meta_expire_time' => $this->cache->getExpireTime(),
            'cache_meta_cache_enabled' => $this->cache->getReport()->isCacheEnabled()
        ];
    }

    public function runSummary()
    {
        return $this->getHeader(true);
    }
}
