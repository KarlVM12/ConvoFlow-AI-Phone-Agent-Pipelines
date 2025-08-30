<?php

$mapping = array(
    'Session'=> ROOT_DIR.'/System/Session.php',

    //APP
    'Application\Manager' => ROOT_DIR . '/Application/Manager.php',
    'Application\Template' => ROOT_DIR . '/Application/Template.php',
    'Application\Controller' => ROOT_DIR . '/Application/Controller.php',
    // 'Application\Platform\AppManager' => ROOT_DIR . '/Application/Platform/AppManager.php',

    //------- CONTROLLERS
    // 'Application\Platform\AuthorizedUser' => ROOT_DIR .'/Application/Platform/AuthorizedUser.php',
    // 'Application\Platform\Controllers\LoginController' => ROOT_DIR . '/Application/Platform/Controllers/LoginController.php',
    // 'Application\Platform\Controllers\UserController' => ROOT_DIR . '/Application/Platform/Controllers/UserController.php',
    // 'Application\Platform\Controllers\AdminController' => ROOT_DIR . '/Application/Platform/Controllers/AdminController.php',

    //-------DATA MODELS--------------
    'DataModel\Database\Users'=> ROOT_DIR.'/System/DataModel/Database/Users.php',

    //HELPERS....
    'AppHelper'=> ROOT_DIR.'/System/Helpers/AppHelper.php',
    'DateTimeHelper'=> ROOT_DIR.'/System/Helpers/DateTimeHelper.php',
    'PickerHelper'=> ROOT_DIR.'/System/Helpers/PickerHelper.php',
    'DataTableRows'=> ROOT_DIR.'/System/Helpers/DataTableRows.php',
    'DataModel\DataRecord'=> ROOT_DIR.'/System/DataModel/DataRecord.php',
    'DateRangeManager' => ROOT_DIR . '/System/DateRangeManager.php',

    //SYSTEM
    'DataBasePDO\DataBasePDO' => ROOT_DIR . '/System/DataBasePDO.php',
    'EmailPHP' => ROOT_DIR . '/System/EmailPHP.php');

spl_autoload_register(function ($class) use ($mapping) {
    if (isset($mapping[$class])) {
        //echo($class."<br>");
        require $mapping[$class];
    }
}, true);

if (DEV_MODE_LOCAL)
{
    // if using local Plugins folders instead of composer
}
else
{
    require ROOT_DIR . '/vendor/autoload.php';
}



