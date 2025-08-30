<?php

class AppHelper
{
    public static function FilteredTables():array{
        return [''];
    }
    public static function Guid():string{
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                .substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
                .chr(125);// "}"
            return $uuid;
        }
    }

    public static function Guid32(bool $lowerCase = true):string {
        $guid = str_replace("{", "", AppHelper::Guid());
        $guid = str_replace("}", "", $guid);
        $guid = str_replace("-", "", $guid);
        if($lowerCase == true){
            return strtolower($guid);
        }
        return $guid;
    }

    public static function Token(int $bitLength=32, bool $lowerCase = true): string
    {
        $token = bin2hex(openssl_random_pseudo_bytes($bitLength));
        if($lowerCase == true){
            return strtolower($token);
        }
        return strtoupper( $token);
    }


    public static function ReplaceTemplate(string $template, array $replace):string{
        $output = $template;
        foreach ($replace as $key => $value) {
            $tagToReplace = "[@$key]";
            $holder = $value;
            if ($holder == null) {
                $holder = "";
            }
            $output = str_replace($tagToReplace, $holder, $output);
        }

        return $output;
    }

    public static function PrettyPrint($item){
        echo("<pre>");

        print_r($item);

        echo("<br><br>");
    }

    public static function ReNameDataField(string $field):string{
        $names = explode("_", $field);
        $return = "";
        foreach($names as $key => $value) {
            $return .= ucfirst($value);
        }
        return $return;
    }
    public static function GetJsonModelFromPayLoad($schema,$table,$PayLoad,&$IsNew):string{

        $modelSearch = "model-".$schema."-".$table;
        $fieldPrefix = $schema."-".$table."-";
        $IsNew = false;
        $where = "";

        $arrayValues = array();
        foreach($PayLoad as $key=>$value){
            if($key==$modelSearch){
                if($value == "new"){
                    $IsNew = true;
                }
            }
            if($key=="where-".$schema."-".$table){
                $where = $value;
            }
        }

        //find fields...
        foreach($PayLoad as $key=>$value){
            if(str_contains($key, $fieldPrefix)){
                $newKey = explode("-",$key);
                if($newKey[3]=="date" || $newKey[3]=="time"){
                    if($newKey[3]=="date"){
                        $arrayValues[$newKey[2]] = $value. " ".$arrayValues[$newKey[2]];
                        $arrayValues[$newKey[2]] = rtrim($arrayValues[$newKey[2]]);
                    }
                    else{
                        if(!empty($value)) {
                            $arrayValues[$newKey[2]] = $arrayValues[$newKey[2]] . " " . $value . ":00";
                            $arrayValues[$newKey[2]] = ltrim($arrayValues[$newKey[2]]);
                        }
                    }

                }
                else{
                    $arrayValues[$newKey[2]] = $value;
                }
            }
        }



        $strClassName = 'DataModel\\'.AppHelper::ReNameDataField($schema).'\\'.AppHelper::ReNameDataField($table);


        $strModelObj = new ($strClassName)();
        if($where!= "" && !$IsNew){
            $newQ = explode(",",str_replace("'", "", $where));
            //this sets it to now new ...
            $strModelObj->SetFromJson( $strModelObj->StaticLoadHelper($newQ));


            //----- for audit
            $orgArray = json_decode( $strModelObj->StaticLoadHelper($newQ),true);
            $change = array();
            $org = array();
            foreach($arrayValues as $iK=>$iV){
                if($orgArray[$iK]!=$arrayValues[$iK]){
                    $change[$iK] = $arrayValues[$iK];
                    $org[$iK] = $orgArray[$iK];
                }
            }

            //echo("<pre>");


            //-----
        }
        $strModelObj->CopyReplaceFromJson(json_encode($arrayValues));

        return $strModelObj->ConvertToJsonString();
    }



}
