<?php
use PHPMailer\PHPMailer\PHPMailer;
use Application\Template;
use Aws\Sqs\SqsClient;

class EmailPHP
{
    protected string $fromAddress;
    private $sqsClient;

    function __construct() {
        $this->fromAddress = SYSTEM_EMAIL;

        $this->sqsClient = new SqsClient([
            'region' => 'us-east-1',
            'version' => 'latest',
            'credentials' => [
                'key'    => awsAccessKey,
                'secret' => awsSecretKey,
            ]
        ]);

    }
    function PinAccessCode($param):bool
    {

        $to = $param['to'];

        $page = new Template(ROOT_DIR.'/Template/Platform/email/email_pin_access.html');
        $page->set("fullName",$param['fullName']);
        $page->set("pin",$param['pin']);
        $page->set("pathing",'');

        $mail = new PHPMailer();
        $mail->IsSMTP(true); // SMTP
        $mail->SMTPAuth = true;  // SMTP authentication
        $mail->Mailer = "smtp";
        $mail->Host = awsEmailZone;
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "tls";

        $mail->Username = awsEmailUsername;  // SMTP  Username
        $mail->Password = awsEmailPassword;  // SMTP Password
        $mail->SetFrom(SYSTEM_EMAIL, SYSTEM_EMAIL_NAME);
        $mail->AddReplyTo(SYSTEM_EMAIL, SYSTEM_EMAIL_NO_REPLY);

        $mail->Subject = "Pin Access Code";



        $mail->MsgHTML($page->output());
        $mail->AddAddress($to, trim(strtolower($to)));

        if (!$mail->Send()){

            $resultSet['result'] = 1;
            $resultSet['message'] = "ERROR" . $mail->ErrorInfo;
            return false;
        }

        $resultSet['result'] = 0;
        $resultSet['message'] = "Success";

        return true;
    }

    function PinReset($param):bool
    {

        $to = $param['to'];

        $page = new Template(ROOT_DIR.'/Template/Platform/email/password-reset.html');
        $page->set("fullName",$param['fullName']);
        $page->set("pin",$param['pin']);

        $mail = new PHPMailer();
        $mail->IsSMTP(true);
        $mail->SMTPAuth = true;
        $mail->Mailer = "smtp";
        $mail->Host = awsEmailZone;
        $mail->Port = 587;
        $mail->SMTPAuth = true;
        $mail->SMTPSecure = "tls";

        $mail->Username = awsEmailUsername;
        $mail->Password = awsEmailPassword;
        $mail->SetFrom(SYSTEM_EMAIL, SYSTEM_EMAIL_NAME);
        $mail->AddReplyTo(SYSTEM_EMAIL, SYSTEM_EMAIL_NO_REPLY);

        $mail->Subject = "Pin Access";        

        $mail->MsgHTML($page->output());
        $mail->AddAddress($to, trim(strtolower($to)));

        if (!$mail->Send()){

            $resultSet['result'] = 1;
            $resultSet['message'] = "ERROR" . $mail->ErrorInfo;
            return false;
        }

        $resultSet['result'] = 0;
        $resultSet['message'] = "Success";
        return true;

    }
}

