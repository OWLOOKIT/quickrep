<?php

namespace Owlookit\Quickrep\Http\Controllers;

use Carbon\Carbon;
use DB;
use Owlookit\Quickrep\Http\Requests\QuickrepRequest;
use Owlookit\Quickrep\Models\DatabaseCache;
use Owlookit\Quickrep\Reports\Tabular\ReportGenerator;
use Owlookit\Quickrep\Reports\Tabular\ReportSummaryGenerator;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooter;
use PhpOffice\PhpSpreadsheet\Worksheet\HeaderFooterDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TabularApiController extends AbstractApiController
{
    public function index(QuickrepRequest $request)
    {
        $report = $request->buildReport();
        $cache = new DatabaseCache($report, quickrep_cache_db());
        $generator = new ReportGenerator($cache);
        return $generator->toJson();
    }

    public function summary(QuickrepRequest $request)
    {
        $report = $request->buildReport();
        // Wrap the report in cache
        $cache = new DatabaseCache($report, quickrep_cache_db());
        $generator = new ReportSummaryGenerator($cache);
        return $generator->toJson();
    }

    /**
     * Generate the download for the targeted report. This relies on the cached version of the ReportJSON
     * @param QuickrepRequest $request Tabular request for report
     * @return CSV download
     *
     */
    public function download(QuickrepRequest $request)
    {
        // Type can be either 'csv' or 'excel' and we default to excel (shouldn't have to)
        $fileType = $request->get('download_file_type', 'excel');
        $report = $request->buildReport();
        $connectionName = quickrep_cache_db();
        $cache = new DatabaseCache($report, $connectionName);
        $summaryGenerator = new ReportSummaryGenerator($cache);
        $header = $summaryGenerator->runSummary();
        $lang = $report->getInput('lang') ?? config('app.locale');
        $header = array_map(function ($element) use ($lang) {
            // Replace spaces with '_' in the header
            $title = $element['title_I18n'][(string)$lang] ?: $element['title'];
            return preg_replace('/\s+/', '_', $title);
        }, $header);
        $reportGenerator = new ReportGenerator($cache);
        $collection = $reportGenerator->getCollection();

        // @TODO: refactor types
        $reportDescription = $report->GetReportDescriptionI18n()[(string)$lang];

        // File name download should include MD5 from the contents of getCode #48
        $reportName = (strlen($report->GetReportName()) > 150) ? substr(
            $report->GetReportName(),
            0,
            150
        ) : $report->GetReportName();

        if ($report->getCode()) {
            $filename = $reportName . '-' . $report->getCode();
        } else {
            $filename = $reportName;
        }

        if ($fileType === 'csv') {
            $filename .= '.csv';
            return $this->csvResponse($filename, $reportDescription, $header, $collection);
        } else {
            $filename .= '.xlsx';
            return $this->excelResponse($filename, $reportDescription, $header, $collection);
        }
    }

    protected function csvResponse($filename, $reportDescription, $header, $collection)
    {
        $response = new StreamedResponse(function () use ($header, $collection) {
            // Open output stream
            $handle = fopen('php://output', 'w');

            // Add CSV headers
            fputcsv($handle, $header);

            // Get all users
            foreach ($collection as $value) {
                // Add a new row with data
                fputcsv($handle, json_decode(json_encode($value), true));
            }

            // Close the output stream
            fclose($handle);
        }, 200, [
            'Content-Description' => 'File Transfer',
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . urlencode($filename) . '"',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public'
        ]);

        return $response;
    }

    protected function excelResponse($filename, $reportDescription, $header, $collection)
    {
        $response = new StreamedResponse(function () use ($filename, $reportDescription, $header, $collection) {
            $spreadsheet = new Spreadsheet();

            $spreadsheet->getProperties()
                ->setCreator(config("app.name"))
                ->setLastModifiedBy(config("app.name"))
                ->setTitle(preg_replace('/\\.[^.\\s]{3,4}$/', '', $filename))
                ->setSubject(config("app.name") . " Report document")
                ->setDescription(
                    "Report document generated for Office 2007 XLSX."
                )
                ->setKeywords(config("app.name") . " report")
                ->setCategory(config("app.name") . " report");

            $sheet = $spreadsheet->getActiveSheet();

            $sheet->getSheetView()->setZoomScale(120);
            $sheet->getTabColor()->setRGB('b2ebf2');

            $sheet->getPageSetup()->setFitToWidth(1);
            $sheet->getPageSetup()
                ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE);
            $sheet->getPageSetup()
                ->setPaperSize(PageSetup::PAPERSIZE_A4);

            $drawing = new HeaderFooterDrawing();
            $drawing->setName('logo');
            $drawing->setPath('./storage/assets/logo.png');
            $drawing->setHeight(32);
            $sheet->getHeaderFooter()->addImage(
                $drawing,
                HeaderFooter::IMAGE_HEADER_RIGHT
            );

            $sheet->getHeaderFooter()
                ->setOddHeader('&C&H&"Verdana,Trebuchet"&14' . $reportDescription . '&R&G');
            $sheet->getHeaderFooter()
                ->setOddFooter(
                    '&L&B' . $spreadsheet->getProperties()->getTitle() . ' | ' . Carbon::now() . '&RСтр. &P из &N'
                );

            $sheet->setTitle(
                (strlen($spreadsheet->getProperties()->getTitle()) > 20) ? substr(
                        $spreadsheet->getProperties()->getTitle(),
                        0,
                        20
                    ) . '...' : $spreadsheet->getProperties()->getTitle()
            );

            $styleArray = [
                'font' => [
                    'bold' => true,
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
                'borders' => [
                    'top' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                    'bottom' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                    'left' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                    'right' => [
                        'borderStyle' => Border::BORDER_THIN,
                    ],
                ],
                'fill' => [
                    'fillType' => Fill::FILL_GRADIENT_LINEAR,
                    'rotation' => 90,
                    'startColor' => [
                        'argb' => 'b2ebf2ff',
                    ],
                    'endColor' => [
                        'argb' => '008fc1ff',
                    ],
                ],
            ];
            $sheet->getStyle([1, 1, count($header), 1])->applyFromArray($styleArray);

            for ($i = 0, $l = count($header); $i < $l; $i++) {
                $sheet->setCellValue([$i + 1, 1], $header[$i]);
                $sheet->getColumnDimensionByColumn($i + 1)->setAutoSize(true);
            }

            for ($i = 0, $l = count($collection); $i < $l; $i++) { // row $i
                $j = 0;
                foreach ($collection[$i] as $k => $v) { // column $j
                    $sheet->setCellValue([$j + 1, ($i + 1 + 1)], trim(strip_tags($v)));
                    $j++;
                }
            }

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Description' => 'File Transfer',
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . urlencode($filename) . '"',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate',
            'Pragma' => 'public'
        ]);

        return $response;
    }
}
