<?php

namespace DataModel\Database;

use DataModel\DataRecord;
use DataBasePDO\DataBasePDO;
use PDO;
use DateTime;
use AppHelper;
use stdClass;



class Users extends DataRecord
{

     public ?string $Id;
     public ?string $Email;
     public ?string $FullName;
     public ?bool $IsClient;
     public ?string $CompanyId;
     public ?string $Title;
     public ?string $Phone;
     public ?string $EmployeeNumber;
     public ?string $IconColor;
     public ?string $ProfileImage;
     public ?string $Mode;
     public ?bool $AppAccess;
     public ?bool $PlatformAccess;
     public ?bool $ResetPassword;
     public ?DateTime $NextMfa;
     public ?string $DisplayTimeZone;
     public ?bool $IsActive;
     public ?DateTime $DeletedAt;
     public ?DateTime $UpdatedAt;
     public ?DateTime $CreatedAt;
     public bool $NewRecord;


     function __construct(){
         parent::__construct();

         $this->Id = null;
         $this->Email = null;
         $this->FullName = null;
         $this->IsClient = false;
         $this->CompanyId = null;
         $this->Title = null;
         $this->Phone = null;
         $this->EmployeeNumber = null;
         $this->IconColor = null;
         $this->ProfileImage = null;
         $this->Mode = "light-theme";
         $this->AppAccess = false;
         $this->PlatformAccess = false;
         $this->ResetPassword = false;
         $this->NextMfa = null;
         $this->DisplayTimeZone = "America/New_York";
         $this->IsActive = true;
         $this->DeletedAt = null;
         $this->UpdatedAt = null;
         $this->CreatedAt = null;
         $this->NewRecord = true;


     }

    public static function Load($id_):string{
        $db = new DataBasePDO();
        $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $data = $db->GetRow("SELECT * FROM `database`.`users` WHERE "."`id` = ?;",$id_);
        return json_encode($data);
    }

    //is_new impacts the DB save ... if is_new is true the primary keys will be generated at time of save
    public function SetFromJson(string $json_data, bool $is_new = false):void{
        $this->NewRecord = $is_new;
        $holder = json_decode($json_data,true);
        if (count($holder) === 0) {
            $this->NewRecord = true;
        }

        $this->Id = array_key_exists('id', $holder) ? $holder['id'] : ( array_key_exists('Id', $holder) ? $holder['Id'] : null);
        $this->Email = array_key_exists('email', $holder) ? $holder['email'] : ( array_key_exists('Email', $holder) ? $holder['Email'] : null);
        $this->FullName = array_key_exists('full_name', $holder) ? $holder['full_name'] : ( array_key_exists('FullName', $holder) ? $holder['FullName'] : null);
        $this->IsClient = array_key_exists('is_client', $holder) ? ($holder['is_client']=='1' ? true:false) : ( array_key_exists('IsClient', $holder) ? ($holder['IsClient']=='1' ? true:false) : false);
        $this->CompanyId = array_key_exists('company_id', $holder) ? $holder['company_id'] : ( array_key_exists('CompanyId', $holder) ? $holder['CompanyId'] : null);
        $this->Title = array_key_exists('title', $holder) ? $holder['title'] : ( array_key_exists('Title', $holder) ? $holder['Title'] : null);
        $this->Phone = array_key_exists('phone', $holder) ? $holder['phone'] : ( array_key_exists('Phone', $holder) ? $holder['Phone'] : null);
        $this->EmployeeNumber = array_key_exists('employee_number', $holder) ? $holder['employee_number'] : ( array_key_exists('EmployeeNumber', $holder) ? $holder['EmployeeNumber'] : null);
        $this->IconColor = array_key_exists('icon_color', $holder) ? $holder['icon_color'] : ( array_key_exists('IconColor', $holder) ? $holder['IconColor'] : null);
        $this->ProfileImage = array_key_exists('profile_image', $holder) ? $holder['profile_image'] : ( array_key_exists('ProfileImage', $holder) ? $holder['ProfileImage'] : null);
        $this->Mode = array_key_exists('mode', $holder) ? $holder['mode'] : ( array_key_exists('Mode', $holder) ? $holder['Mode'] : "light-theme");
        $this->AppAccess = array_key_exists('app_access', $holder) ? ($holder['app_access']=='1' ? true:false) : ( array_key_exists('AppAccess', $holder) ? ($holder['AppAccess']=='1' ? true:false) : false);
        $this->PlatformAccess = array_key_exists('platform_access', $holder) ? ($holder['platform_access']=='1' ? true:false) : ( array_key_exists('PlatformAccess', $holder) ? ($holder['PlatformAccess']=='1' ? true:false) : false);
        $this->ResetPassword = array_key_exists('reset_password', $holder) ? ($holder['reset_password']=='1' ? true:false) : ( array_key_exists('ResetPassword', $holder) ? ($holder['ResetPassword']=='1' ? true:false) : false);

        $timeTest = array_key_exists('next_mfa', $holder) ? $holder['next_mfa'] : ( array_key_exists('NextMfa', $holder) ? $holder['NextMfa'] : null);
        if($timeTest != null && !empty($timeTest)){
            $this->NextMfa = DateTime::createFromFormat('Y-m-d H:i:s', $timeTest);
        }
        else{
            $this->NextMfa = null;
        }
        $this->DisplayTimeZone = array_key_exists('display_time_zone', $holder) ? $holder['display_time_zone'] : ( array_key_exists('DisplayTimeZone', $holder) ? $holder['DisplayTimeZone'] : "America/New_York");
        $this->IsActive = array_key_exists('is_active', $holder) ? ($holder['is_active']=='1' ? true:false) : ( array_key_exists('IsActive', $holder) ? ($holder['IsActive']=='1' ? true:false) : true);

        $timeTest = array_key_exists('deleted_at', $holder) ? $holder['deleted_at'] : ( array_key_exists('DeletedAt', $holder) ? $holder['DeletedAt'] : null);
        if($timeTest != null && !empty($timeTest)){
            $this->DeletedAt = DateTime::createFromFormat('Y-m-d H:i:s', $timeTest);
        }
        else{
            $this->DeletedAt = null;
        }

        $timeTest = array_key_exists('updated_at', $holder) ? $holder['updated_at'] : ( array_key_exists('UpdatedAt', $holder) ? $holder['UpdatedAt'] : null);
        if($timeTest != null && !empty($timeTest)){
            $this->UpdatedAt = DateTime::createFromFormat('Y-m-d H:i:s', $timeTest);
        }
        else{
            $this->UpdatedAt = null;
        }

        $timeTest = array_key_exists('created_at', $holder) ? $holder['created_at'] : ( array_key_exists('CreatedAt', $holder) ? $holder['CreatedAt'] : null);
        if($timeTest != null && !empty($timeTest)){
            $this->CreatedAt = DateTime::createFromFormat('Y-m-d H:i:s', $timeTest);
        }
        else{
            $this->CreatedAt = null;
        }

    }


     public function Save(bool $generateKey = true):void{

        //Doing a quick check to make sure the record is in the DB on updates...
        if(!$this->NewRecord){
            $db = new DataBasePDO();
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $recordTest = $db->GetRow("SELECT * FROM `database`.`users` WHERE `id` = ?;", $this->Id);
            if($recordTest == null || count($recordTest) == 0){
                $this->NewRecord = true;
            }
        }

        if($this->NewRecord){
            if($generateKey){
                $this->Id = AppHelper::Guid32();
            }
            $this->Insert();
        }
        else{
            $this->Update();
        }
     }

     private function Insert():int{
         $db = new DataBasePDO();
         $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
         $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

         $db->Insert("REPLACE INTO `database`.`users`
         (
         `id`,`email`,`full_name`,`is_client`,`company_id`,`title`,`phone`,`employee_number`,`icon_color`,`profile_image`,`mode`,`app_access`,`platform_access`,`reset_password`,`next_mfa`,`display_time_zone`,`is_active`,`deleted_at`,`updated_at`,`created_at`
         )
         VALUE
         (
         ?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,UTC_TIMESTAMP,UTC_TIMESTAMP
         )
         ",$this->Id,$this->Email,$this->FullName, ($this->IsClient  ? 1 : 0),$this->CompanyId,$this->Title,$this->Phone,$this->EmployeeNumber,$this->IconColor,$this->ProfileImage,$this->Mode, ($this->AppAccess  ? 1 : 0), ($this->PlatformAccess  ? 1 : 0), ($this->ResetPassword  ? 1 : 0),($this->NextMfa?->format('Y-m-d H:i:s')),$this->DisplayTimeZone, ($this->IsActive  ? 1 : 0),($this->DeletedAt?->format('Y-m-d H:i:s')));

         $this->NewRecord = false;
         if(1==0){
            return $db->lastInsertId();
         }

         return 0;
     }

     private function Update():void{
          $db = new DataBasePDO();
          $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
          $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

          $db->Insert("UPDATE `database`.`users` SET
          `email` = ?,`full_name` = ?,`is_client` = ?,`company_id` = ?,`title` = ?,`phone` = ?,`employee_number` = ?,`icon_color` = ?,`profile_image` = ?,`mode` = ?,`app_access` = ?,`platform_access` = ?,`reset_password` = ?,`next_mfa` = ?,`display_time_zone` = ?,`is_active` = ?,`deleted_at` = ?,`updated_at` = UTC_TIMESTAMP
          WHERE
          `id` = ?;
          ",$this->Email,$this->FullName, ($this->IsClient ? 1 : 0),$this->CompanyId,$this->Title,$this->Phone,$this->EmployeeNumber,$this->IconColor,$this->ProfileImage,$this->Mode, ($this->AppAccess ? 1 : 0), ($this->PlatformAccess ? 1 : 0), ($this->ResetPassword ? 1 : 0),($this->NextMfa?->format('Y-m-d H:i:s')),$this->DisplayTimeZone, ($this->IsActive ? 1 : 0),($this->DeletedAt?->format('Y-m-d H:i:s')), $this->Id);
     }

    //CAUTION!!!! when using this function pay attention to the KEY's and ID's as well as the NewRecord status
    //This is meant to be an easy way to quickly replace values into an object from a form post converted to json when doing an update
    public function CopyReplaceFromJson(string $json_data):void{
        $holder = json_decode($json_data,true);

        $this->Id = array_key_exists('id', $holder) ? $holder['id'] : ( array_key_exists('Id', $holder) ? $holder['Id'] : $this->Id);
        $this->Email = array_key_exists('email', $holder) ? $holder['email'] : ( array_key_exists('Email', $holder) ? $holder['Email'] : $this->Email);
        $this->FullName = array_key_exists('full_name', $holder) ? $holder['full_name'] : ( array_key_exists('FullName', $holder) ? $holder['FullName'] : $this->FullName);
        $this->IsClient = array_key_exists('is_client', $holder) ? ($holder['is_client']=='1' ? true:false) : ( array_key_exists('IsClient', $holder) ? ($holder['IsClient']=='1' ? true:false) : $this->IsClient);
        $this->CompanyId = array_key_exists('company_id', $holder) ? $holder['company_id'] : ( array_key_exists('CompanyId', $holder) ? $holder['CompanyId'] : $this->CompanyId);
        $this->Title = array_key_exists('title', $holder) ? $holder['title'] : ( array_key_exists('Title', $holder) ? $holder['Title'] : $this->Title);
        $this->Phone = array_key_exists('phone', $holder) ? $holder['phone'] : ( array_key_exists('Phone', $holder) ? $holder['Phone'] : $this->Phone);
        $this->EmployeeNumber = array_key_exists('employee_number', $holder) ? $holder['employee_number'] : ( array_key_exists('EmployeeNumber', $holder) ? $holder['EmployeeNumber'] : $this->EmployeeNumber);
        $this->IconColor = array_key_exists('icon_color', $holder) ? $holder['icon_color'] : ( array_key_exists('IconColor', $holder) ? $holder['IconColor'] : $this->IconColor);
        $this->ProfileImage = array_key_exists('profile_image', $holder) ? $holder['profile_image'] : ( array_key_exists('ProfileImage', $holder) ? $holder['ProfileImage'] : $this->ProfileImage);
        $this->Mode = array_key_exists('mode', $holder) ? $holder['mode'] : ( array_key_exists('Mode', $holder) ? $holder['Mode'] : $this->Mode);
        $this->AppAccess = array_key_exists('app_access', $holder) ? ($holder['app_access']=='1' ? true:false) : ( array_key_exists('AppAccess', $holder) ? ($holder['AppAccess']=='1' ? true:false) : $this->AppAccess);
        $this->PlatformAccess = array_key_exists('platform_access', $holder) ? ($holder['platform_access']=='1' ? true:false) : ( array_key_exists('PlatformAccess', $holder) ? ($holder['PlatformAccess']=='1' ? true:false) : $this->PlatformAccess);
        $this->ResetPassword = array_key_exists('reset_password', $holder) ? ($holder['reset_password']=='1' ? true:false) : ( array_key_exists('ResetPassword', $holder) ? ($holder['ResetPassword']=='1' ? true:false) : $this->ResetPassword);

        $timeTest = array_key_exists('next_mfa', $holder) ? $holder['next_mfa'] : ( array_key_exists('NextMfa', $holder) ? $holder['NextMfa'] : null);
        if($timeTest != null && !empty($timeTest)){
            $this->NextMfa = DateTime::createFromFormat('Y-m-d H:i:s', $timeTest);
        }
        else{
            $this->NextMfa = $this->NextMfa;
        }
        $this->DisplayTimeZone = array_key_exists('display_time_zone', $holder) ? $holder['display_time_zone'] : ( array_key_exists('DisplayTimeZone', $holder) ? $holder['DisplayTimeZone'] : $this->DisplayTimeZone);
        $this->IsActive = array_key_exists('is_active', $holder) ? ($holder['is_active']=='1' ? true:false) : ( array_key_exists('IsActive', $holder) ? ($holder['IsActive']=='1' ? true:false) : $this->IsActive);

        $timeTest = array_key_exists('deleted_at', $holder) ? $holder['deleted_at'] : ( array_key_exists('DeletedAt', $holder) ? $holder['DeletedAt'] : null);
        if($timeTest != null && !empty($timeTest)){
            $this->DeletedAt = DateTime::createFromFormat('Y-m-d H:i:s', $timeTest);
        }
        else{
            $this->DeletedAt = $this->DeletedAt;
        }

        $timeTest = array_key_exists('updated_at', $holder) ? $holder['updated_at'] : ( array_key_exists('UpdatedAt', $holder) ? $holder['UpdatedAt'] : null);
        if($timeTest != null && !empty($timeTest)){
            $this->UpdatedAt = DateTime::createFromFormat('Y-m-d H:i:s', $timeTest);
        }
        else{
            $this->UpdatedAt = $this->UpdatedAt;
        }

        $timeTest = array_key_exists('created_at', $holder) ? $holder['created_at'] : ( array_key_exists('CreatedAt', $holder) ? $holder['CreatedAt'] : null);
        if($timeTest != null && !empty($timeTest)){
            $this->CreatedAt = DateTime::createFromFormat('Y-m-d H:i:s', $timeTest);
        }
        else{
            $this->CreatedAt = $this->CreatedAt;
        }

    }




    public function ConvertToJsonString():string{
        $returnValue = array();
        $returnValue['id']= $this->Id;
        $returnValue['email']= $this->Email;
        $returnValue['full_name']= $this->FullName;
        $returnValue['is_client']= $this->IsClient;
        $returnValue['company_id']= $this->CompanyId;
        $returnValue['title']= $this->Title;
        $returnValue['phone']= $this->Phone;
        $returnValue['employee_number']= $this->EmployeeNumber;
        $returnValue['icon_color']= $this->IconColor;
        $returnValue['profile_image']= $this->ProfileImage;
        $returnValue['mode']= $this->Mode;
        $returnValue['app_access']= $this->AppAccess;
        $returnValue['platform_access']= $this->PlatformAccess;
        $returnValue['reset_password']= $this->ResetPassword;
        $returnValue['next_mfa']= $this->NextMfa?->format('Y-m-d H:i:s');
        $returnValue['display_time_zone']= $this->DisplayTimeZone;
        $returnValue['is_active']= $this->IsActive;
        $returnValue['deleted_at']= $this->DeletedAt?->format('Y-m-d H:i:s');
        $returnValue['updated_at']= $this->UpdatedAt?->format('Y-m-d H:i:s');
        $returnValue['created_at']= $this->CreatedAt?->format('Y-m-d H:i:s');

        return json_encode($returnValue);
    }

    public function StaticLoadHelper(array $arg):string{
        return Users::Load($arg[0]);
    }
}
