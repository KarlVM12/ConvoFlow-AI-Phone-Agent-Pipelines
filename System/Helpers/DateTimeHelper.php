<?php

use DateTime;
use DateTimeZone;

class DateTimeHelper
{
    public static function UtcToLocalDisplay(DateTime $dateTime):string{
        // create a $dt object with the UTC timezone
        $timeZone = DefaultTimeZoneDisplay;
        if(isset($_SESSION['DisplayTimeZone'])){
            $timeZone = $_SESSION['DisplayTimeZone'];
        }

        $utc = new DateTime($dateTime->format("Y-m-d H:i:s"), new DateTimeZone('UTC'));
        $local_date_time = $utc->setTimezone(new DateTimeZone($timeZone));
        return $local_date_time->format(DefaultDateTimeFormat);


    }

    public static function UtcToLocalFormat(DateTime $dateTime,string $format):string{
        // create a $dt object with the UTC timezone
        $timeZone = DefaultTimeZoneDisplay;
        if(isset($_SESSION['DisplayTimeZone'])){
            $timeZone = $_SESSION['DisplayTimeZone'];
        }
        $utc = new DateTime($dateTime->format("Y-m-d H:i:s"), new DateTimeZone('UTC'));
        $local_date_time = $utc->setTimezone(new DateTimeZone($timeZone));
        return $local_date_time->format($format);
    }

    public static function DateToString(DateTime $dateTime):string{
        $utc = new DateTime($dateTime->format("Y-m-d H:i:s"), new DateTimeZone('UTC'));
        return $utc->format("Y-m-d");
    }

    public static function ToUtcSql(DateTime $dateTime):string{
        // create a $dt object with the UTC timezone
        $timeZone = DefaultTimeZoneDisplay;
        if(isset($_SESSION['DisplayTimeZone'])){
            $timeZone = $_SESSION['DisplayTimeZone'];
        }
        $utc = new DateTime($dateTime->format("Y-m-d H:i:s"), new DateTimeZone($timeZone));
        $local_date_time = $utc->setTimezone(new DateTimeZone('UTC'));
        return $local_date_time->format("Y-m-d H:i:s");
    }

    public static function StringToUtcSql(string $dateTime):string{
        // create a $dt object with the UTC timezone
        $timeZone = DefaultTimeZoneDisplay;
        if(isset($_SESSION['DisplayTimeZone'])){
            $timeZone = $_SESSION['DisplayTimeZone'];
        }
        $utc = new DateTime($dateTime, new DateTimeZone($timeZone));
        $local_date_time = $utc->setTimezone(new DateTimeZone('UTC'));
        return $local_date_time->format("Y-m-d H:i:s");
    }
    public static function StringToDateSql(string $dateTime):string{
        // create a $dt object with the UTC timezone
        $timeZone = DefaultTimeZoneDisplay;
        $utc = new DateTime($dateTime, new DateTimeZone($timeZone));
        return $utc->format("Y-m-d");
    }
}