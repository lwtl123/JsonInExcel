<?php
namespace JsonInExcel;
class Transform
{
    //public $timeData = array();

    public function transformData($data){
        $timeData = array();
        if (isset($data)) {
            for ($i = 0; $i < count($data); $i++) {
                $day = date_format(new \DateTime($data[$i]["Started"]), 'd.m.y');
                if (!isset($timeData[$day])) {
                    $timeData[$day]["workTime"] = 0;
                    $timeData[$day]["Name"] = "";
                    $timeData[$day]["StartTime"] = 0;
                    $timeData[$day]["EndTime"] = 0;
                    $timeData[$day]["Pause"] = 0;
                }
                $timeData[$day]["workTime"] = $timeData[$day]["workTime"] + $data[$i]["TimeSpentSeconds"];
                //Name auf Mehrfachnennung und Kommas prüfen und speichern
                $pos = strpos($timeData[$day]["Name"], $data[$i]["IssueKey"]);
                if ($timeData[$day]["Name"] != "") {
                    $timeData[$day]["Name"] = $timeData[$day]["Name"] . ", ";
                }
                if ($pos === false) {
                    $timeData[$day]["Name"] = $timeData[$day]["Name"] . $data[$i]["IssueKey"];
                }
                //starttime
                if (($timeData[$day]["StartTime"] < date_format(new \DateTime($data[$i]["Started"]), 'H:i:s')) && (($timeData[$day]["StartTime"] != null))) {
                    $timeData[$day]["StartTime"];
                } else {
                    $timeData[$day]["StartTime"] = date_format(new \DateTime($data[$i]["Started"]), 'H:i:s');
                }
                //endtime
                $date = (new \DateTime($timeData[$day]["StartTime"]));
                $endTime = date_add($date, date_interval_create_from_date_string((string)($timeData[$day]["workTime"]) . " seconds"));
                $endTime = date_format($endTime, 'H:i:s');
                $timeData[$day]["EndTime"] = $endTime;
                $mitternacht = new \DateTime("23:59:59");
                $c = date_format(date_sub($mitternacht, date_interval_create_from_date_string((string)($timeData[$day]["workTime"]) . " seconds")), 'H:i:s');
                if ($c < $timeData[$day]["StartTime"]) {
                    $timeData[$day]["StartTime"] = $c;
                    $endTime = date_format(new \DateTime("23:59:59"), 'H:i:s');
                    $timeData[$day]["EndTime"] = $endTime;//wichtig für pausenzeit
                }
                //Pausenzeiten
                $timeData[$day]["Pause"] = "00:00";
                if ($timeData[$day]["workTime"] >= 21600) {
                    $plusHourEndtime = date_format(
                        date_add(new \DateTime($endTime), date_interval_create_from_date_string(
                                (3600) . " seconds")
                        ), 'H:i:s');
                    $plusHourStarttime = date_format(
                        date_sub(new \DateTime($timeData[$day]["StartTime"]), date_interval_create_from_date_string(
                                (3600) . " seconds")
                        ), 'H:i:s');
                    if (($plusHourEndtime < $mitternacht) && ($plusHourEndtime > date_format(new \DateTime("00:59:59"), 'H:i:s'))) {
                        $timeData[$day]["EndTime"] = $plusHourEndtime;
                    } elseif (($plusHourStarttime > date_format(new \DateTime("00:00:00"), 'H:i:s'))) {
                        $timeData[$day]["StartTime"] = $plusHourStarttime;
                    }
                    $timeData[$day]["Pause"] = "01:00";
                }
            }
        }
        return $timeData;

    }

    public function getMonth($data){
        $month = date_format(new \DateTime($data[0]["Started"]), 'M');
        return $month;
    }
}

