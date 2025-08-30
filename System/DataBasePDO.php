<?php

namespace DataBasePDO;

use PDO;


class DataBasePDO extends PDO
{
    const PARAM_host = SQL_SERVER_NAME;
    const PARAM_port = SQL_DB_PORT;
    const PARAM_db_name = SQL_DATABASE_NAME;
    const PARAM_user = SQL_DB_USERID;
    const PARAM_db_pass = SQL_DB_PASSWORD;

    public function __construct($options=null){
        parent::__construct('mysql:host='.DataBasePDO::PARAM_host.';port='.DataBasePDO::PARAM_port.';dbname='.DataBasePDO::PARAM_db_name,
            DataBasePDO::PARAM_user,
            DataBasePDO::PARAM_db_pass,$options);
    }

    public function queryX($query){ //secured query with prepare and execute
        $args = func_get_args();
        array_shift($args); //first element is not an argument but the query itself, should removed

        $response = parent::prepare($query);
        $response->execute($args);
        return $response;
    }

    public function InsertOnDuplicate($query,$args):bool{
        $response = parent::prepare($query);
        return $response->execute($args);
    }

    public function InsertWithArgs($query,$args):bool{
        try {
            $response = parent::prepare($query);
            $response->execute($args);
        } catch (PDOException $e) {
            return false;
        }
        return true;
    }
    public function UpdateWithArgs($query,$args):bool{
        try {
            $response = parent::prepare($query);
            $response->execute($args);
        } catch (PDOException $e) {
            return false;
        }
        return true;
    }
    public function GetValueWithArgs($query,$args):string{
        $response = parent::prepare($query);
        $response->execute($args);
        $returnValue = "";
        while ($o = $response->fetch())
        {
            $DataSet = (Array)$o;
            $returnValue = reset($DataSet);
            //print_r($DataSet);
        }
        return $returnValue;
    }

    public function GetRowParam($query,$args):array{
        $response = parent::prepare($query);
        $response->execute($args);

        $returnValue = [];
        while ($o = $response->fetch())
        {
            $returnValue = (Array)$o;
            //print_r($DataSet);
        }
        return $returnValue;
    }


    public function Insert($query):void{ //secured query with prepare and execute
        $args = func_get_args();
        array_shift($args); //first element is not an argument but the query itself, should removed
        $response = parent::prepare($query);
        $response->execute($args);
    }

    public function Update($query):void{ //secured query with prepare and execute
        $args = func_get_args();
        array_shift($args); //first element is not an argument but the query itself, should removed
        $response = parent::prepare($query);
        $response->execute($args);
    }


    public function GetValue($query):string{
        $args = func_get_args();
        array_shift($args); //first element is not an argument but the query itself, should removed
        $response = parent::prepare($query);
        $response->execute($args);
        $returnValue = "";
        while ($o = $response->fetch())
        {
            $DataSet = (Array)$o;
            $returnValue = reset($DataSet);
            //print_r($DataSet);
        }
        return $returnValue;
    }
    public function GetRowObject($query):object{
        $args = func_get_args();
        array_shift($args); //first element is not an argument but the query itself, should removed

        $response = parent::prepare($query);
        $response->execute($args);

        $returnValue = new stdClass();
        while ($o = $response->fetch())
        {
            $returnValue = $o;
            //print_r($DataSet);
        }
        return $returnValue;
    }

    public function GetRow($query):array{
        $args = func_get_args();
        array_shift($args); //first element is not an argument but the query itself, should removed

        $response = parent::prepare($query);
        $response->execute($args);

        $returnValue = [];
        while ($o = $response->fetch())
        {
            $returnValue = (Array)$o;
            //print_r($DataSet);
        }
        return $returnValue;
    }



    public function GetRecordMap($query):array{
        $args = func_get_args();
        array_shift($args); //first element is not an argument but the query itself, should removed

        $response = parent::prepare($query);
        $response->execute($args);

        $returnValue = [];
        while ($o = $response->fetch())
        {
            $returnValue[] = (Array)$o;
            //print_r($DataSet);
        }
        return $returnValue;
    }

}
