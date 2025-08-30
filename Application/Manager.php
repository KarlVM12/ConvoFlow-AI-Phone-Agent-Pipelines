<?php

namespace Application;


use DataBasePDO\DataBasePDO;
use PDO;


class Manager
{
    protected string $HtmlString;
    protected string $AppDirectory;
    protected DataBasePDO $db;
    protected string $PageCommand;
    protected array $PageParams;
    protected string $Route;
    protected string $FullRoute;
    protected string $HtmlPathing;
    protected array $PagePayLoad;
    protected string $DatabaseSchema;

    protected string $CurrentRouthPath;
    protected ?string $ReturnRoute;

    function __construct(){
        $this->HtmlString = "";
        $this->HtmlPathing = HtmlAssetPath;
        $this->ReturnRoute = null;
    }

    protected function InitDatabase($companyId = ""):void{
        $this->db = new DataBasePDO();
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->DatabaseSchema  = MasterSchema;
    }

    protected function GetRoute():void{
        $this->PageCommand = "";
        $this->PageParams = [];

        // ROUTE SETUP
        $index = 0;
        $_preParam = explode("?",$_GET['route']);
        $_Route = explode("/",$_preParam[0]);

        $this->FullRoute = $_preParam[0];

        $_Route = array_values(array_filter($_Route));


        if(!$this->SetCommand($_Route[$index],$index)){
            $this->PageCommand = "default";
        }

        if(!$this->SetCommandParams($_Route,$index)){
            $this->PageParams[] = "";
        }

        //$this->Route = $_GET['route'];

    }

    ///USE GET BREADCRUMB ROUTE TO GET NEW ROUTE AND TO POP FROM ARRAY
    protected function GetBreadCrumbRoute():string{

        if(!isset($_SESSION["BreadCrumb"])) {
            return "";
        }

        $BreadCrumb = json_decode($_SESSION["BreadCrumb"],true);
        array_pop($BreadCrumb);
        $_SESSION["BreadCrumb"] = json_encode($BreadCrumb);

        if(count($BreadCrumb) < 1){
            return "";
        }



        return $BreadCrumb[count($BreadCrumb)-1];
    }

    ///USE SET BREADCRUMB ROUTE TO PUSH TO ARRAY
    protected  function SetBreadCrumbRoute(string $Route = ""):void{
        if($Route == ""){
            $Route = $this->FullRoute;
        }
        if(!isset($_SESSION["BreadCrumb"])){
            $holder = [];
            $_SESSION["BreadCrumb"] = json_encode($holder);
        }
        $BreadCrumb = json_decode($_SESSION["BreadCrumb"],true);
        if($BreadCrumb[count($BreadCrumb)-1] != $Route) {
            $BreadCrumb[] = $Route;
        }
        if(count($BreadCrumb) > 10){
            array_shift($BreadCrumb);
        }
        $_SESSION["BreadCrumb"] = json_encode($BreadCrumb);

    }

    protected function SetCommand($value, &$index):bool{
        if(!isset($value))
            return false;

        $this->PageCommand  =  $value;
        $index++;
        return true;
    }

    protected function SetCommandParams($arrayOfValues, &$index):bool{
        $this->Route = "";

        for ($x = $index; $x <= count($arrayOfValues); $x++) {
            $this->PageParams[] = $arrayOfValues[$x];
            if(strlen($arrayOfValues[$x])>0){
                $this->Route.="/".$arrayOfValues[$x];
            }
        }

        $this->PageParams = array_values(array_filter($this->PageParams));

        if(count($this->PageParams) < 1){
            unset($this->PageParams);
            return false;
        }
        $index++;
        return true;
    }

    protected function GetData():void
    {
        $get = $_GET;
        unset($get['route']);
        $this->PagePayLoad =array_merge(empty($_POST) ? array() : $_POST, (array) json_decode(file_get_contents('php://input'), true), $get);
    }

    public function Start():void{

    }

    public function Draw():void{
        $this->Start();
        $this->Output();
    }

    public function Output():void{
        echo($this->HtmlString);
    }


}