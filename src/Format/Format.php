<?php
namespace JsonInExcel\Format;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class Format
{
    public function setSheetFormat($timeData)
    {

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE); //Querformat

        $sheet = $sheet->setCellValue('A1', 'Aktion:')->setCellValue('B1', null)
            ->mergeCells('B1:D1')->mergeCells('E1:G1')->mergeCells('H1:K1')
            ->mergeCells('B2:D2')->mergeCells('E2:G2')->mergeCells('H2:K2')
            ->mergeCells('B3:D3')->mergeCells('E3:G3')->mergeCells('H3:K3')
            ->setCellValue('E1', 'Tätigkeit:')->setCellValue('H1', 'Softwareentwicklung')
            ->setCellValue('A2', 'Bestell-Nr.:')->mergeCells('B2:D2')->setCellValue('B2', '4501535029')
            ->setCellValue('E2', 'PLG-Ansprechpartner:')->setCellValue('A4', ' ')->setCellValue('A5', ' ')
            ->setCellValue('A3', 'Datum:')->setCellValue('B3', null)
            ->setCellValue('E3', 'Auftragnehmer:')->setCellValue('H3', 'BI Business Intelligence GmbH')
            //Tabellenüberschrift
            ->setCellValue('A6', 'Datum')->mergeCells('A6:A7')
            ->setCellValue('B6', 'Name')->mergeCells('B6:B7')
            ->setCellValue('C6', 'Uhrzeit')->mergeCells('C6:D6')
            ->setCellValue('C7', 'von')->setCellValue('D7', 'bis')
            ->setCellValue('E6', "Pausen-\rzeit")->mergeCells('E6:E7')
            ->setCellValue('F6', 'Kennzeichnung Verrechnungssatz')->mergeCells('F6:J6')
            ->setCellValue('K6', 'Ges. Arbeitszeit')->mergeCells('K6:K7');

        $startCell = 8;
        $gesWorkTime = 0;

        foreach ($timeData as $day){
            $workTime = (string)$day["workTime"] / 60 / 60;
            $gesWorkTime += $workTime;
            $day["StartTime"] = date_format(new \DateTime($day["StartTime"]), 'H:i');
            $day["EndTime"] = date_format(new \DateTime($day["EndTime"]), 'H:i');

            $sheet->setCellValue('A' . $startCell, key($timeData))->setCellValue('C' . $startCell, $day["StartTime"])
                ->setCellValue('D' . $startCell, $day["EndTime"])->setCellValue('E' . $startCell, $day["Pause"])
                ->setCellValue('B' . $startCell, $this->formatName($day["Name"]))
                ->setCellValue('K' . $startCell, $workTime);

            //automatischer Zeilenumbruch in der Column B
            $lineBreak = 32;
            $index = strlen($sheet->getCellByColumnAndRow(2, $startCell));
            $rowHeight = 14;
            $sheet->getRowDimension($startCell)->setRowHeight($rowHeight);

            for ($rowHeight; $index > $lineBreak;) {
                $rowHeight += 14;
                $sheet->getRowDimension($startCell)->setRowHeight($rowHeight);
                $sheet = $sheet->setCellValue('B' . $startCell, (substr_replace($sheet->getCellByColumnAndRow(2, $startCell), "\r", $lineBreak, 0)));
                $lineBreak += 32;
            }
            $startCell++;
        }

        $sheet = $sheet->setCellValue('D' . $startCell, 'Gesamt')->mergeCells('D' . $startCell . ':E' . $startCell)
            ->setCellValue('F18', "0:00")->setCellValue('G18', '0:00')->setCellValue('H18', '0:00')->setCellValue('I18', '0:00')
            ->setCellValue('J18', "0:00")->setCellValue('K' . $startCell, $gesWorkTime)->setCellValue('A' . ($startCell + 1), '    ')
            ->setCellValue('A' . ($startCell + 3), ' ')->setCellValue('A' . ($startCell + 4), ' ')->setCellValue('A' . ($startCell + 2), 'Name/ausführende Firma/Datum/Unterschrift')
            ->mergeCells('A' . ($startCell + 2) . ':B' . ($startCell + 2))->setCellValue('A' . ($startCell + 5), 'Name/Abteilungskürzel/Datum/Unterschrift Bearbeiter')
            ->mergeCells('A' . ($startCell + 5) . ':C' . ($startCell + 5))->setCellValue('G' . ($startCell + 5), 'Name/Abteilungskürzel/Datum/Unterschrift Leiter C')
            ->mergeCells('G' . ($startCell + 5) . ':K' . ($startCell + 5))->setCellValue('A' . ($startCell + 6), " ");

        //Gestaltung und Anordnung
        $sheet->getStyle('A6:K7')->getFont()->setBold(true);
        $sheet->getStyle('A6' . ':K' . $startCell)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('A6' . ':K' . $startCell)->getAlignment()->setVertical('center');
        $sheet->getStyle('B2')->getAlignment()->setHorizontal('left');
        $sheet->getStyle('B8' . ':B' . $startCell)->getAlignment()->setHorizontal('left');
        $sheet->getStyle('B1' . ':B' . $startCell)->getAlignment()->setVertical('center');

        //Borders
        $sheet->getStyle('A1' . ':K' . ($startCell + 6))->getBorders()->getInside()->setBorderStyle('dotted');
        $sheet->getStyle('A1' . ':K' . ($startCell + 6))->getBorders()->getOutline()->setBorderStyle('thin');
        $sheet->getStyle('A6' . ':K' . ($startCell))->getBorders()->getAllBorders()->setBorderStyle('thin');
        $sheet->getStyle('A' . $startCell . ':C' . $startCell)->getBorders()->getInside()->setBorderStyle('dotted');
        $sheet->getStyle('D' . $startCell . ':J' . $startCell)->getBorders()->getOutline()->setBorderStyle('medium');
        $sheet->getStyle('D' . $startCell . ':J' . $startCell)->getBorders()->getInside()->setBorderStyle('thin');
        $sheet->getStyle('K' . $startCell)->getBorders()->getOutline()->setBorderStyle('medium');
        $sheet->getStyle('K' . $startCell)->getBorders()->getInside()->setBorderStyle('thin');

        //Säulenbreite
        $this->setColumnWidths($sheet);

        return $spreadsheet;
    }

    protected function setColumnWidths(Worksheet $sheet) {
        $sheet->getColumnDimension('A')->setWidth(9.75);
        $sheet->getColumnDimension('B')->setWidth(31.5);
        $sheet->getColumnDimension('E')->setWidth(7.5);
        $sheet->getColumnDimension('K')->setWidth(14.20);
    }

    protected function formatName(string $name): string {
        return $name;
    }
}