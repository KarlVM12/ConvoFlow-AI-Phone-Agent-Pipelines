<?php

namespace Application;

use DataBasePDO\DataBasePDO;
use PDO;

class Controller
{
    protected DataBasePDO $db;
    protected Template $PageTemplate;

    function __construct(){
        $this->InitDatabase();
    }

    protected function InitDatabase($companyId = ""):void{
        $this->db = new DataBasePDO();
        $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    protected function NewTemplate(string $template):void{
        $this->PageTemplate = new Template($template);
        $this->SetDefaults();
    }

    protected function SetDefaults():void{

    }
}