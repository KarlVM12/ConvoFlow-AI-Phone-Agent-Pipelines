<?php

class DateRangeManager
{
    public DateTime $firstDateTime;
    public DateTime $lastDateTime;
    public DateInterval $interval;
    public DatePeriod $periods;
    public int $totalDays;

    public string $firstDateString;
    public string $lastDateString;

    function __construct($param_range_date_start, $param_range_date_end)
    {
        $this->firstDateString = $param_range_date_start;
        $this->lastDateString = $param_range_date_end;

        $this->firstDateTime = new DateTime($param_range_date_start);
        $this->lastDateTime = new DateTime($param_range_date_end." 23:59:59");

        $this->interval = DateInterval::createFromDateString('1 day');
        $this->periods = new DatePeriod($this->firstDateTime,$this->interval , $this->lastDateTime);
        $this->totalDays = $this->firstDateTime->diff($this->lastDateTime)->days;
    }

    public function GetDisplayRange():string{
        return $this->firstDateTime->format("Y-m-d")." to ".$this->lastDateTime->format("Y-m-d");
    }
}