<?php

enum SessionType
{
    case Development;
    case Production;
}

class Session
{
    public static function Start(SessionType $mode):void{
        if ($mode == SessionType::Development)
        {
            ini_set('display_errors', '1');
            ini_set('display_startup_errors', '1');
            error_reporting(E_ALL ^ E_WARNING ^ E_DEPRECATED);
        }
        else
        {
            ini_set('display_errors', '0');
            ini_set('display_startup_errors', '0');
            error_reporting(0);
        }
        //ini_set("session.cookie_secure", 1);
        ini_set("session.cookie_httponly", 1);
        ignore_user_abort(1); // run script in background even if user closes browser
        set_time_limit(1800); // run it for 30 minutes

        if (session_status() == PHP_SESSION_NONE)
        {
            session_start();
        }

        //echo("in session<br>");
    }
}