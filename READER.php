<?php

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use function Sodium\add;

if (!file_exists("jsonexport-08.json")) {
    echo "Datei nicht vorhanden.";
    exit;
}
$dz=fopen("jsonexport-08.json","r");
if(!$dz) {
    echo "Datei konnte nicht geöffnet werden.";
    exit;
}

$data= (file_get_contents("jsonexport-08.json"));
$data=(json_decode($data,true));

$timeData=array();

for ($i=0;$i<count($data);$i++){ //ohne das hier kommt ne notiz: PHP Notice:  Undefined index: 2019.08.05 in /Users/ll/Desktop/jsonInExcel/reader.php on line 30
//PHP Notice:  Undefined index: StartTime in /Users/ll/Desktop/jsonInExcel/reader.php on line 33
//PHP Notice:  Undefined index: StartTime in /Users/ll/Desktop/jsonInExcel/reader.php on line 33
//PHP Notice:  Undefined index: 2019.08.06 in /Users/ll/Desktop/jsonInExcel/reader.php on line 30
//PHP Notice:  Undefined index: StartTime in /Users/ll/Desktop/jsonInExcel/reader.php on line 33

$day= date_format(new DateTime($data[$i]["Started"]),'Y.m.d');
    $timeData[$day]["workTime"] = 0;
    $timeData[$day]["Name"]="";
    $timeData[$day]["StartTime"] = 0;
    $timeData[$day]["EndTime"] = 0;
    $timeData[$day]["Pause"]= 0;
}

for ($i=0;$i<count($data);$i++){
    $day= date_format(new DateTime($data[$i]["Started"]),'Y.m.d');
    $timeData[$day]["workTime"] = $timeData[$day]["workTime"] + $data[$i]["TimeSpentSeconds"];

    if ($timeData[$day]["Name"] != $data[$i]["IssueKey"]){//mehrfachnennung möglich
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

    /*Pausenzeiten*/
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

    /*Test ob es den Tag überzieht und ändert in dem Fall die Startzeit auf die Mitternacht - worktime
    $mitternacht= new DateTime("23:59:59");
    $c=date_format(date_sub($mitternacht,date_interval_create_from_date_string((string)($timeData[$day]["workTime"])." seconds")),'H:i:s');
    if ($c < $timeData[$day]["StartTime"]) {
        //echo "$i:  " . $c . " ";
        $timeData[$day]["StartTime"]=$c;
        $timeData[$day]["EndTime"]= date_format(new DateTime("23:59:59"),'H:i:s');
    }*/
}
var_dump($timeData);

$spreadsheet = new Spreadsheet();
$startZelle=8;
$sheet = $spreadsheet->getActiveSheet();//->($timeData[$day]["Name"],null,'B'.$startZelle);

$sheet = $sheet->setCellValue('A1','Aktion:')->setCellValue('B1',null)
    ->mergeCells('B1:D1')->mergeCells('E1:G1')->mergeCells('H1:K1')
    ->mergeCells('B2:D2')->mergeCells('E2:G2')->mergeCells('H2:K2')
    ->mergeCells('B3:D3')->mergeCells('E3:G3')->mergeCells('H3:K3')
    ->setCellValue('E1','Tätigkeit:')->setCellValue('H1','Softwareentwicklung')
    ->setCellValue('A2','Bestell-Nr.:')->mergeCells('B2:D2')
    ->setCellValue('B2','4501535029')
    ->setCellValue('E2','PLG-Ansprechpartner')->setCellValue('H4',null)
    ->setCellValue('A3','Datum:')->setCellValue('B3',null)
    ->setCellValue('E3','Auftragnehmer:')->setCellValue('H3','BI Business Intelligence GmbH');

$sheet = $sheet->setCellValue('B7','von')
    ->setCellValue('C7','bis')
    ->setCellValue('A6','Datum')->mergeCells('A6:A7')
    ->setCellValue('B6','Name')->mergeCells('B6:B7')
    ->setCellValue('C6','Uhrzeit')->mergeCells('C6:D6')
    ->setCellValue('E6','Pausenzeit')->mergeCells('E6:E7')
    ->setCellValue('F6','Kennzeichnung Verrechnungssatz')->mergeCells('F6:J6');

$sheet = $sheet->setCellValue('K6','Ges. Arbeitszeit')->mergeCells('K6:K7');


$gesWorkTime=0;

for ($i=0;$i<count($timeData);$i++){
    $startZelle= (string)$startZelle;
    $koordinate='A'.$startZelle;
    $day = key($timeData);
    $workTime=(string)$timeData[$day]["workTime"]/60/60;
    $gesWorkTime+=$workTime;

    $sheet->setCellValue($koordinate, key($timeData))->setCellValue('C'.$startZelle,$timeData[$day]["StartTime"])
    ->setCellValue('D'.$startZelle,$timeData[$day]["EndTime"])->setCellValue('E'.$startZelle,$timeData[$day]["Pause"])
    ->setCellValue('B'.$startZelle,$timeData[$day]["Name"])->setCellValue('K'.$startZelle,$workTime);
    next($timeData);
    $startZelle++;
}

$sheet=$sheet->setCellValue('D'.$startZelle,'Gesamt')->mergeCells('D'.$startZelle.':E'.$startZelle)
    ->setCellValue('K'.$startZelle,$gesWorkTime)->setCellValue('A'.($startZelle+2),'Name/ausführende Firma/Datum/Unterschrift')
    ->mergeCells('A'.($startZelle+2).':B'.($startZelle+2))->setCellValue('A'.($startZelle+4),'Name/Abteilungskürzel/Datum/Unterschrift Bearbeiter')
    ->mergeCells('A'.($startZelle+4).':B'.($startZelle+4))->setCellValue('G'.($startZelle+4),'Name/Abteilungskürzel/Datum/Unterschrift Leiter C')
    ->mergeCells('G'.($startZelle+4).':J'.($startZelle+4));


$writer = new Xlsx($spreadsheet);
$writer->save('inexcel2.xlsx');

/*->setCellValue('A4',null)->setCellValue('B4',null)->setCellValue('C4',null)
    ->setCellValue('D4',null)->setCellValue('E4',null)->setCellValue('F4',null)
    ->setCellValue('G4',null)->setCellValue('H4',null)->setCellValue('I4',null)
    ->setCellValue('J4',null)->setCellValue('K4',null)
    ->setCellValue('A5',null)->setCellValue('B5',null)->setCellValue('C5',null)
    ->setCellValue('D5',null)->setCellValue('E5',null)->setCellValue('F5',null)
    ->setCellValue('G5',null)->setCellValue('H5',null)->setCellValue('I5',null)
    ->setCellValue('J5',null)->setCellValue('K5',null);

https://phpspreadsheet.readthedocs.io/en/latest/topics/recipes/#mergeunmerge-cells
https://phpspreadsheet.readthedocs.io/en/latest/
https://trello.com/b/Qg2Ojn3A/ptb


*/
?>

