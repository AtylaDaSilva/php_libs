<?php
    //Converts time in a HH:MM:SS format to seconds, minutes or hours as an integer value
    function convertTime($time = "00:00:00", $param) {
        if (!isset($time) &&
            $time == "00:00:00" &&
            !isset($param) &&
            $param != "TO_SECONDS" &&
            $param != "TO_MINUTES" &&
            $param != "TO_HOURS"
        ) return null;

        $t = explode(":", $time);
        $h = (int) $t[0];
        $m = (int) $t[1];
        $s = (int) $t[2];

        if ($param == "TO_SECONDS") {
            //Convert to seconds
            $res = ($h * 3600) + ($m * 60) + $s;
        }

        if ($param == "TO_MINUTES") {
            //Convert to minutes
            $res = ($h * 60) + $m + (floatval($s/60));
            
        }

        if ($param == "TO_HOURS") {
            //Convert to hours
            $res = $h + ($m / 60) + ($s / 3600);
        }

        return $res;
    }
?>