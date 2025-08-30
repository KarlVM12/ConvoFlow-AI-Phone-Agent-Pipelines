<?php
define('ROOT_DIR', realpath(__DIR__ . '/../..'));
require ROOT_DIR. '/vendor/autoload.php';
use Twilio\TwiML\VoiceResponse;

header('Content-Type: text/xml');
header("HTTP/1.1 200 OK");

$twilio_number_dialed = $_POST["to"];

// Use the $twilio_number_dialed to do a look up into the database to see what connector is attached to that phone number
// This connectorName must match the Twilio Console > Dialogflow CX Connector name
$connectorName = 'BillingSchedulingAgent'; // Ex: 'Dialogflow_CX_Test1'

$voiceResponse = new VoiceResponse();

// Can add a status callback to track intents, caller sentiment, etc.
$connect = $voiceResponse->connect();
  // 'action' => 'https://domain.com/voice-callback',
  // 'method' => 'POST',
//]);

$connect->virtualagent([
    'connectorName' => $connectorName,
  // 'statusCallback' => 'https://domain.com/agent-callback',
  // 'statusCallbackEvent' => ['intent', 'sentiment'],
]);

echo $voiceResponse;
