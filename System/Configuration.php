<?php
$local = false;

// For workers accepting requests from SQS
foreach (getallheaders() as $name => $value) {
    if($name == "Host" && ($value =="localhost") && !isset($_SERVER["HTTP_X_AWS_SQSD_QUEUE"])){
        $local = true;
    }
}

if (!defined('DefaultTimeZoneDisplay')) define('DefaultTimeZoneDisplay', 'America/New_York');
if (!defined('DefaultDateTimeFormat')) define('DefaultDateTimeFormat', 'Y-m-d H:i');


if (!defined('EMAIL_LINK_DOMAIN')) define('EMAIL_LINK_DOMAIN', "https://domain.com/");
if (!defined('DOMAIN_LINK')) define('DOMAIN_LINK', "https://domain.com/");

if (!defined('AppApiKey')) define('AppApiKey', '<APP_API_KEY>');
if (!defined('AppVersion')) define('AppVersion', '1.0');
if (!defined('ApiVersion')) define('ApiVersion', '1.0');

//-----------------------------
// CRITICAL: make sure this is setup Per client and Per  dev vs Pro
if (!defined('awsAccessKey')) define('awsAccessKey', '<AWS_ACCESS_KEY>');
if (!defined('awsSecretKey')) define('awsSecretKey', '<AWS_SECRET_KEY>');
if (!defined('awsRegion')) define('awsRegion', 'us-east-1');
if (!defined('S3_BUCKET')) define('S3_BUCKET', '<S3_BUCKET_NAME>');



//------------- EMAIL SETUP
if (!defined('awsEmailUsername')) define('awsEmailUsername', '<SES_SMTP_EMAIL_USERNAME>');
if (!defined('awsEmailPassword')) define('awsEmailPassword', '<SES_SMTP_EMAIL_PASSWORD>');
if (!defined('awsEmailZone')) define('awsEmailZone', 'tls://email-smtp.us-east-1.amazonaws.com');
if (!defined('SYSTEM_EMAIL')) define('SYSTEM_EMAIL', 'no-reply@domain.com');
if (!defined('SYSTEM_EMAIL_NAME')) define('SYSTEM_EMAIL_NAME', 'System Name: no-reply@domain.com');
if (!defined('SYSTEM_EMAIL_NO_REPLY')) define('SYSTEM_EMAIL_NO_REPLY', 'no-reply@domain.com');

if (!defined('PASSWORD_RESET')) define('PASSWORD_RESET', 'email_pin'); //email_token_link
if (!defined('MFA')) define('MFA', false); //email_token


if (!defined('MultiTenant')) define('MultiTenant', false);
if (!defined('MasterSchema')) define('MasterSchema', 'database');

if($local) {
    if (!defined('HtmlAssetPath')) define('HtmlAssetPath', "/project/public/");
    if (!defined('DEV_MODE_LOCAL')) define('DEV_MODE_LOCAL', true);
    if (!defined('DOMAIN_LINK')) define('DOMAIN_LINK', "http://localhost/");
}
else{
    if (!defined('HtmlAssetPath')) define('HtmlAssetPath', "/");
    if (!defined('DEV_MODE_LOCAL')) define('DEV_MODE_LOCAL', false);
}

//DATABASE SCHEMA SETUP
if($local) {
    if (!defined('SQL_SERVER_NAME')) define('SQL_SERVER_NAME', 'localhost');
    if (!defined('SQL_DB_USERID')) define('SQL_DB_USERID', 'root');
    if (!defined('SQL_DB_PASSWORD')) define('SQL_DB_PASSWORD', 'root');
    if (!defined('SQL_DATABASE_NAME')) define('SQL_DATABASE_NAME', MasterSchema);
    if (!defined('SQL_DB_PORT')) define('SQL_DB_PORT', '3306');
}
else{
    if (!defined('SQL_SERVER_NAME')) define('SQL_SERVER_NAME', '<AWS_RDS_SERVER_NAME>');
    if (!defined('SQL_DB_USERID')) define('SQL_DB_USERID', '<DB_USERID>');
    if (!defined('SQL_DB_PASSWORD')) define('SQL_DB_PASSWORD', '<DB_PASSWORD>');
    if (!defined('SQL_DATABASE_NAME')) define('SQL_DATABASE_NAME', MasterSchema);
    if (!defined('SQL_DB_PORT')) define('SQL_DB_PORT', '3306');
}

$sql_details = array(
    'user' => SQL_DB_USERID,
    'pass' => SQL_DB_PASSWORD,
    'db' => SQL_DATABASE_NAME,
    'host' => SQL_SERVER_NAME
);

if (!defined('SQLSET')) define('SQLSET', $sql_details);

