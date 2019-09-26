<?php

namespace JsonInExcel;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FormatPdf extends Format
{
    protected function formatName(string $name): string
    {
        return str_replace(['\r\n', '\n\r', '\r', '\n'], '', $name);
    }

    protected function setColumnWidths(Worksheet $sheet)
    {
        parent::setColumnWidths($sheet);
        $sheet->getColumnDimension('A')->setWidth(15);
        $sheet->getColumnDimension('C')->setWidth(0);
        $sheet->getColumnDimension('D')->setWidth(0);
        $sheet->getPageSetup()->setFitToPage(true);
    }
}