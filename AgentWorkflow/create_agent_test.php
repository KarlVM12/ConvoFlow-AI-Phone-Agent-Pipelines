<?php 

define("ROOT_DIR", realpath(__DIR__ . "/.."));
require ROOT_DIR. "/vendor/autoload.php";
include_once ROOT_DIR . "/System/Configuration.php";
include_once ROOT_DIR . "/System/DataBasePDO.php";
include_once ROOT_DIR . "/System/Helpers/AppHelper.php";
include_once ROOT_DIR . "/System/EmailPHP.php";
include_once ROOT_DIR . "/Application/Template.php";
include_once ROOT_DIR . "/worker/ConnectorAgent.php";

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Cloud\Dialogflow\Cx\V3\Agent;
use Google\Cloud\Dialogflow\Cx\V3\Intent;
use Google\Cloud\Dialogflow\Cx\V3\Intent\Parameter;
use Google\Cloud\Dialogflow\Cx\V3\ResponseMessage\Text;
use Google\Cloud\Dialogflow\Cx\V3\Intent\TrainingPhrase;
use Google\Cloud\Dialogflow\Cx\V3\Intent\TrainingPhrase\Part;
use Google\Cloud\Dialogflow\Cx\V3\Client\AgentsClient;
use Google\Cloud\Dialogflow\Cx\V3\Client\EnvironmentsClient;
use Google\Cloud\Dialogflow\Cx\V3\Client\IntentsClient;
use Google\Cloud\Dialogflow\Cx\V3\Client\PagesClient;
use Google\Cloud\Dialogflow\Cx\V3\CreateAgentRequest;
use Google\Cloud\Dialogflow\Cx\V3\CreateIntentRequest;
use Google\Cloud\Dialogflow\Cx\V3\Fulfillment;
use Google\Cloud\Dialogflow\Cx\V3\ResponseMessage;
use Google\Cloud\Dialogflow\Cx\V3\Page;
use Google\Cloud\Dialogflow\Cx\V3\TransitionRoute;
use Google\Cloud\Dialogflow\Cx\V3\GetPageRequest;
use Google\Cloud\Dialogflow\Cx\V3\UpdatePageRequest;
use Google\Cloud\Dialogflow\Cx\V3\Client\FlowsClient;
use Google\Cloud\Dialogflow\Cx\V3\CreatePageRequest;
use Google\Cloud\Dialogflow\Cx\V3\DeployFlowRequest;
use Google\Cloud\Dialogflow\Cx\V3\EventHandler;
use Google\Cloud\Dialogflow\Cx\V3\GetFlowRequest;
use Google\Cloud\Dialogflow\Cx\V3\Flow;
use Google\Cloud\Dialogflow\Cx\V3\Fulfillment\ConditionalCases;
use Google\Cloud\Dialogflow\Cx\V3\GetIntentRequest;
use Google\Cloud\Dialogflow\Cx\V3\ListEnvironmentsRequest;
use Google\Cloud\Dialogflow\Cx\V3\ListFlowsRequest;
use Google\Cloud\Dialogflow\Cx\V3\ListPagesRequest;
use Google\Cloud\Dialogflow\Cx\V3\ResponseMessage\EndInteraction;
use Google\Cloud\Dialogflow\Cx\V3\UpdateFlowRequest;
use Google\Cloud\Dialogflow\Cx\V3\UpdateIntentRequest;
use Google\Cloud\Dialogflow\Cx\V3\Webhook\GenericWebService\HttpMethod;
use Twilio\Rest\Client;
use Twilio\Rest\Client as TwilioClient;
use ConnectorAgent;

echo "<pre>";

try
{
  // Agent that can get a user's Billing information or Schedule a New Appointment
  $connector_name = "BillingSchedulingAgent";
  $agent_description = "Multi-step flow with scheduling and billing";
  $welcome_event_name = "MultiWelcome";

  $first_response = 'Routing you to the correct department';
  $first_intent_names_and_phrases = [
    'To_Scheduling' => [
      'training_phrases' => ['Take me to Scheduling', 'Schedule Appointment'],
      'response' => $first_response,
      'target_page' => 'SchedulingPage',
      'intent_needed' => true
    ],
    'To_Billing'  => [
      'training_phrases' => ['Take me to Billing', 'Billing'],
      'response' => $first_response,
      'target_page' => 'BillingPage',
      'intent_needed' => true
    ]
  ];

  $first_greeting = ['Hello, would you like to go to Billing or Scheduling?'];
  
  // so we don't want our webhook to be triggered on an intent, we want it to always be true, so how should we trigger the condition with no input param? and intent_needed false
  $schedulingPageIntents = [
    'Get_Available_Appointments' => [
      'condition' => "true", // when there is no input param or anything to send as input to the webhook endpoint, the condition being true will grab from that endpoint right away without problem
      'intent_needed' => false,
      'response' => 'Next appointments available are $session.params.next_appts',
      'target_page' => ''
    ]
  ];
  // Intent name should always match a webhook name and vice versa, even when we dont' want an intent
  $schedulingPageWebhooks = [
    'Get_Available_Appointments' => [
      'endpoint' => 'https://domain.com/api/next-appointments.php',
      'input_parameters' => [], 
      'response_parameter_name' => 'next_appts', 
      'http_method' => HttpMethod::GET
    ]
  ];
  
  // so we don't want our webhook to be triggered on an intent, we want it to always be triggered until the accoutnNumber is said, hence condition = sessions.params.{input_name} and intent_needed false
  $billingPageIntents = [
    'Retrieve_Bill' => [
      'condition' => '$session.params.accountNumber != null',
      'intent_needed' => false,
      'response' => 'Your current bill is $session.params.bill',
      'target_page' => ''
    ]
  ];
  // future: determine api method, i.e. if it requires sending customer data to get bill -> POST
  $billingPageWebhooks = [
    'Retrieve_Bill' => [
      'endpoint' => 'https://domain.com/api/billing-info.php', 
      'input_parameters' => [
        [
          'name' => 'accountNumber', 
          'prompt' => 'What is your accountNumber?']
        ], 
      'response_parameter_name' => 'bill', 
      'http_method' => HttpMethod::POST
    ]
  ];

  // transitions field it for intents
  $pages_config = [
    [
      'name'        => 'ROOT',
      'intents'     => $first_intent_names_and_phrases,
      'greetings'   => $first_greeting,
      // 'responses'   => $first_response, // moved to inside each intent
      // 'transitions' => ['To_Scheduling'=>'SchedulingPage','To_Billing'=>'BillingPage'] // moved to inside each intent
    ],
    [
      'name'    => 'SchedulingPage',
      'intents' => $schedulingPageIntents, 
      'greetings' => ['Looking up appointments...'],
      // 'responses' => [],
      'webhooks'   => $schedulingPageWebhooks,
      // 'transitions' => []
    ],
    [
      'name'    => 'BillingPage',
      'intents' => $billingPageIntents, 
      'greetings' => ['Checking billing info...'],
      // 'responses' => [],
      'webhooks'   => $billingPageWebhooks,
      // 'transitions' => []
    ]
  ];

  $Agent = new ConnectorAgent();
  $Agent->createAgent($connector_name, $agent_description, $pages_config, $welcome_event_name);

  // Simple Hours Agent Example
  // $RestaurantAgent = new ConnectorAgent();
  //
  // $connector_name = "RestaurantAgent";
  // $agent_description = "Agent who tells the hours of a our Restaurant";
  // $intent_name = "RestaurantHours";
  // $intent_phrases = [
  //   "What are your hours?",
  //   "When are you open?",
  //   "Are you open today?",
  //   "Yes",  // so if they just answer in an affirmative, also tell the hours
  //   "Sure" 
  // ]; 
  // $greeting = "Hello! Welcome to our Restaurant! Would you like to hear our hours?";
  // $agent_response = "We’re open from 9am to 5pm Monday through Friday. Closed Saturday & Sunday. Thanks for calling. Goodbye!";
  // $welcome_event_name = "RestaurantHours";
  //
  // $pages_config = [
  //   ['name'=>'RestaurantHours','intents'=>['RestaurantHours'=>$intent_phrases],'greetings'=>[$greeting],'responses'=>[$agent_response]],
  // ];
  // $RestaurantAgent->createAgent($connector_name, $agent_description, $pages_config, $welcome_event_name);

}
catch (Exception $e)
{
  echo "<br/>".$e->getMessage()."<br/>"; 
}

?>
