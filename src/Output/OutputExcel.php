<?php
namespace JsonInExcel\Output;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class OutputExcel
{
    public function getExcelSheet($spreadsheet,$fileName){
        $writer = new Xlsx($spreadsheet);
        $writer->save($fileName . '.xlsx');
    }
}

