<?php

namespace JsonInExcel\Output;
class OutputPdf
{
    public function getPdfSheet($spreadsheet,$fileName){
        $pdf = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\DOMPDF($spreadsheet);
        $pdf->save($fileName . '.pdf');
    }
}
