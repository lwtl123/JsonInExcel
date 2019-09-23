<?php

require 'vendor/autoload.php';
require '/Users/ll/Desktop/jsoninexcel/dompdf/lib/Cpdf.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Writer\Pdf;
use Dompdf\Dompdf;
use function Sodium\add;

$fileName = "jsonexport-08.json";
if (!file_exists($fileName)) {
    echo "Datei nicht vorhanden.";
    exit;
}
$dz=fopen($fileName,"r");
if(!$dz) {
    echo "Datei konnte nicht geöffnet werden.";
    exit;
}

$data= (file_get_contents($fileName));
$data=(json_decode($data,true));

$timeData=array();

for ($i=0;$i<count($data);$i++){
    $day= date_format(new DateTime($data[$i]["Started"]),'d.m.y');
    if (!isset($timeData[$day])){
        $timeData[$day]["workTime"] = 0;
        $timeData[$day]["Name"]="";
        $timeData[$day]["StartTime"] = 0;
        $timeData[$day]["EndTime"] = 0;
        $timeData[$day]["Pause"]= 0;
    }
    $timeData[$day]["workTime"] = $timeData[$day]["workTime"] + $data[$i]["TimeSpentSeconds"];
    //Name auf Mehrfachnennung und Kommas prüfen und speichern
    $pos = strpos($timeData[$day]["Name"], $data[$i]["IssueKey"]);
    if ($timeData[$day]["Name"] != ""){
        $timeData[$day]["Name"] = $timeData[$day]["Name"].", ";
    }
    if ($pos === false) {
        $timeData[$day]["Name"] = $timeData[$day]["Name"].$data[$i]["IssueKey"];
    }
    //starttime
    if (($timeData[$day]["StartTime"] < date_format(new DateTime($data[$i]["Started"]),'H:i:s')) && (($timeData[$day]["StartTime"] != null))){
        $timeData[$day]["StartTime"];
    } else {$timeData[$day]["StartTime"]= date_format(new DateTime($data[$i]["Started"]),'H:i:s');}
    //endtime
    $date = (new DateTime($timeData[$day]["StartTime"]));
    $endTime= date_add($date,date_interval_create_from_date_string((string)($timeData[$day]["workTime"])." seconds"));
    $endTime = date_format($endTime, 'H:i:s');
    $timeData[$day]["EndTime"]= $endTime;
    $mitternacht= new DateTime("23:59:59");
    $c=date_format(date_sub($mitternacht,date_interval_create_from_date_string((string)($timeData[$day]["workTime"])." seconds")),'H:i:s');
    if ($c < $timeData[$day]["StartTime"]) {
        $timeData[$day]["StartTime"]=$c;
        $endTime= date_format(new DateTime("23:59:59"),'H:i:s');
        $timeData[$day]["EndTime"]=$endTime;//wichtig für pausenzeit
    }
    //Pausenzeiten
    $timeData[$day]["Pause"]= "00:00";
    if ($timeData[$day]["workTime"] >= 21600){
        $plusHourEndtime= date_format(
            date_add(new DateTime($endTime),date_interval_create_from_date_string(
                (3600)." seconds")
            ),'H:i:s');
        $plusHourStarttime=date_format(
            date_sub(new DateTime($timeData[$day]["StartTime"]),date_interval_create_from_date_string(
                    (3600)." seconds")
            ),'H:i:s');
        if (($plusHourEndtime < $mitternacht) && ($plusHourEndtime > date_format(new DateTime("00:59:59"),'H:i:s'))) {
            $timeData[$day]["EndTime"]= $plusHourEndtime;
        }elseif(($plusHourStarttime > date_format(new DateTime("00:00:00"),'H:i:s'))){
            $timeData[$day]["StartTime"]=$plusHourStarttime;
        }
        $timeData[$day]["Pause"]= "01:00";
    }
}

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE); //Querformat

$sheet = $sheet->setCellValue('A1','Aktion:')->setCellValue('B1',null)
    ->mergeCells('B1:D1')->mergeCells('E1:G1')->mergeCells('H1:K1')
    ->mergeCells('B2:D2')->mergeCells('E2:G2')->mergeCells('H2:K2')
    ->mergeCells('B3:D3')->mergeCells('E3:G3')->mergeCells('H3:K3')
    ->setCellValue('E1','Tätigkeit:')->setCellValue('H1','Softwareentwicklung')
    ->setCellValue('A2','Bestell-Nr.:')->mergeCells('B2:D2')
    ->setCellValue('B2','4501535029')
    ->setCellValue('E2','PLG-Ansprechpartner:')->setCellValue('H4',null)
    ->setCellValue('A3','Datum:')->setCellValue('B3',null)
    ->setCellValue('E3','Auftragnehmer:')->setCellValue('H3','BI Business Intelligence GmbH')

    ->setCellValue('A6','Datum')->mergeCells('A6:A7')
    ->setCellValue('B6','Name')->mergeCells('B6:B7')
    ->setCellValue('C6','Uhrzeit')->mergeCells('C6:D6')
    ->setCellValue('C7','von')->setCellValue('D7','bis')
    ->setCellValue('E6',"Pausen-\rzeit")->mergeCells('E6:E7')
    ->setCellValue('F6','Kennzeichnung Verrechnungssatz')->mergeCells('F6:J6')
    ->setCellValue('K6','Ges. Arbeitszeit')->mergeCells('K6:K7');

$startCell=8;
$starCellPDF = $startCell;
$gesWorkTime=0;
for ($startCell; $startCell<count($timeData)+8; $startCell++){ //count($timeData)+ Startvalue of $startCell, befüllt die Tabelle
    $day = key($timeData);
    $workTime=(string)$timeData[$day]["workTime"]/60/60;
    $gesWorkTime+=$workTime;
    $timeData[$day]["StartTime"] = date_format(new DateTime($timeData[$day]["StartTime"]),'H:i');
    $timeData[$day]["EndTime"] = date_format(new DateTime($timeData[$day]["EndTime"]),'H:i');

    $sheet->setCellValue('A'.$startCell, key($timeData))->setCellValue('C'.$startCell,$timeData[$day]["StartTime"])
    ->setCellValue('D'.$startCell,$timeData[$day]["EndTime"])->setCellValue('E'.$startCell,$timeData[$day]["Pause"])
    ->setCellValue('B'.$startCell,$timeData[$day]["Name"])
    ->setCellValue('K'.$startCell,$workTime);

    //automatischer Zeilenumbruch in der Column B
    $lineBreak=32;
    $index = strlen($sheet->getCellByColumnAndRow(2,$startCell));
    $rowHeight=14;
    $sheet->getRowDimension($startCell)->setRowHeight($rowHeight);
    for($rowHeight;$index>$lineBreak;){
        $rowHeight+=14;
        $sheet->getRowDimension($startCell)->setRowHeight($rowHeight);
        $sheet = $sheet->setCellValue('B'.$startCell,(substr_replace($sheet->getCellByColumnAndRow(2,$startCell),"\r",$lineBreak,0)));
        $lineBreak+=32;
    }
    next($timeData);
}

$sheet=$sheet->setCellValue('D'.$startCell,'Gesamt')->mergeCells('D'.$startCell.':E'.$startCell)
    ->setCellValue('F18',"0:00")->setCellValue('G18','0:00')->setCellValue('H18','0:00')->setCellValue('I18','0:00')
    ->setCellValue('J18',"0:00")->setCellValue('K'.$startCell,$gesWorkTime)->setCellValue('A'.($startCell+2),'Name/ausführende Firma/Datum/Unterschrift')
    ->mergeCells('A'.($startCell+2).':B'.($startCell+2))->setCellValue('A'.($startCell+5),'Name/Abteilungskürzel/Datum/Unterschrift Bearbeiter')
    ->mergeCells('A'.($startCell+5).':C'.($startCell+5))->setCellValue('G'.($startCell+5),'Name/Abteilungskürzel/Datum/Unterschrift Leiter C')
    ->mergeCells('G'.($startCell+5).':K'.($startCell+5))->setCellValue('A'.($startCell+6)," ");

//Gestaltung und Anordnung
$sheet->getStyle('A6:K7')->getFont()->setBold(true);
$sheet->getStyle('A6'.':K'.$startCell)->getAlignment()->setHorizontal('center');
$sheet->getStyle('A6'.':K'.$startCell)->getAlignment()->setVertical('center');
$sheet->getStyle('B2')->getAlignment()->setHorizontal('left');
$sheet->getStyle('B8'.':B'.$startCell)->getAlignment()->setHorizontal('left');
$sheet->getStyle('B1'.':B'.$startCell)->getAlignment()->setVertical('center');

//Borders
$sheet->getStyle('A1'.':K'.($startCell+6))->getBorders()->getInside()->setBorderStyle('dotted');
$sheet->getStyle('A1'.':K'.($startCell+6))->getBorders()->getOutline()->setBorderStyle('thin');
$sheet->getStyle('A6'.':K'.($startCell))->getBorders()->getAllBorders()->setBorderStyle('thin');
$sheet->getStyle('A'.$startCell.':C'.$startCell)->getBorders()->getInside()->setBorderStyle('dotted');
$sheet->getStyle('D'.$startCell.':J'.$startCell)->getBorders()->getOutline()->setBorderStyle('medium');
$sheet->getStyle('D'.$startCell.':J'.$startCell)->getBorders()->getInside()->setBorderStyle('thin');
$sheet->getStyle('K'.$startCell)->getBorders()->getOutline()->setBorderStyle('medium');
$sheet->getStyle('K'.$startCell)->getBorders()->getInside()->setBorderStyle('thin');

//Säulenbreite
$sheet->getColumnDimension('A')->setWidth(9.75);
$sheet->getColumnDimension('B')->setWidth(31.5);
$sheet->getColumnDimension('E')->setWidth(7.5);
$sheet->getColumnDimension('K')->setWidth(14.20);

//Schriftart und Größe ändern
/*
$sheet->getStyle('A1'.':K'.($startCell+6))->getFont()->setName('times new roman');
$sheet->getStyle('A1'.':K'.($startCell+6))->getFont()->setSize(9);
*/
$month=date_format(new DateTime($data[0]["Started"]),'M');
$writer = new Xlsx($spreadsheet);
$writer->save($month.'. Arbeitszeit.xlsx');

//Formatierung für PDF
for ($starCellPDF; $starCellPDF<count($timeData)+8; $starCellPDF++){
    $value = (str_replace(array("\r\n","\n\r", "\r", "\n"),'',$sheet->getCell('B'.$starCellPDF)));
    $sheet = $sheet->setCellValue('B'.$starCellPDF,$value);
    next($timeData);
}
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('C')->setWidth(0);
$sheet->getColumnDimension('D')->setWidth(0);
$spreadsheet->getActiveSheet()->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

$pdf = new \PhpOffice\PhpSpreadsheet\Writer\Pdf\DOMPDF($spreadsheet);
$pdf->save($month.'. Arbeitszeit.pdf');

?>

