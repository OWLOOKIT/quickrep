<?php

declare(strict_types=1);

namespace Owlookit\Quickrep\Interfaces;

interface PreparedSourceReport
{
    /**
     * Stable registry key.
     * Usually the report class basename: AdminUsersExternalAuthBySchoolReport.
     */
    public function preparedSourceKey(): string;

    /**
     * Source connection where report reads prepared data.
     * Example: appstats.
     */
    public function preparedSourceConnection(): string;

    /**
     * Full source table/view name.
     * Example: application.users_external_auth_by_school_report.
     */
    public function preparedSourceName(): string;

    /**
     * Expected update interval.
     * Example: 1800 for every 30 minutes.
     */
    public function preparedSourceExpectedFreshnessSeconds(): int;

    /**
     * When source should be considered stale.
     * Example: 5400 for 90 minutes.
     */
    public function preparedSourceStaleAfterSeconds(): int;
}