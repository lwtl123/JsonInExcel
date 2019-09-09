<?php

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

for ($i=0;$i<count($data);$i++){
    $day= date_format(new DateTime($data[$i]["Started"]),'Y.m.d');
    $timeData[$day]["workTime"] = $timeData[$day]["workTime"] + $data[$i]["TimeSpentSeconds"];
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
        //echo "$i:  " . $c . " ";
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
        $fStarttime=date_format(
            date_sub(new DateTime($timeData[$day]["StartTime"]),date_interval_create_from_date_string(
                    (3600)." seconds")
            ),'H:i:s');
        if (($plusHourEndtime < $mitternacht) && ($plusHourEndtime > date_format(new DateTime("00:59:59"),'H:i:s'))) {
            $timeData[$day]["EndTime"]= $plusHourEndtime;
        }elseif(($fStarttime > date_format(new DateTime("00:00:00"),'H:i:s'))){
            $timeData[$day]["StartTime"]=$fStarttime;
        }
        else{ // was wenn 0:20 begonnen dann pause und dann 23 stunden weitergemacht wie mache ich das dann mit den pausen? endtime minus eine stunde?
            echo "hgvfcgwdhkjdks";
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

?>

