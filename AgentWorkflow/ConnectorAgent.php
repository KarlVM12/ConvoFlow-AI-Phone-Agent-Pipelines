<?php

define("ROOT_DIR", realpath(__DIR__ . "/.."));
require ROOT_DIR. "/vendor/autoload.php";
include_once ROOT_DIR . "/System/Configuration.php";
include_once ROOT_DIR . "/System/DataBasePDO.php";
include_once ROOT_DIR . "/System/Helpers/AppHelper.php";
include_once ROOT_DIR . "/System/EmailPHP.php";
include_once ROOT_DIR . "/Application/Template.php";

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
use Google\Cloud\Dialogflow\Cx\V3\EntityType;
use Twilio\Rest\Client;
use Twilio\Rest\Client as TwilioClient;

use DataBasePDO\DataBasePDO;
use Google\Cloud\Dialogflow\Cx\V3\Client\EntityTypesClient;
use Google\Cloud\Dialogflow\Cx\V3\Client\WebhooksClient;
use Google\Cloud\Dialogflow\Cx\V3\CreateEntityTypeRequest;
use Google\Cloud\Dialogflow\Cx\V3\CreateWebhookRequest;
use Google\Cloud\Dialogflow\Cx\V3\Form;
use Google\Cloud\Dialogflow\Cx\V3\Form\Parameter as FormParameter;
use Google\Cloud\Dialogflow\Cx\V3\Form\Parameter\FillBehavior;
use Google\Cloud\Dialogflow\Cx\V3\Fulfillment\SetParameterAction;
use Google\Cloud\Dialogflow\Cx\V3\Webhook;
use Google\Cloud\Dialogflow\Cx\V3\Webhook\GenericWebService;
use Google\Protobuf\Value;
use PDO;

// POC: not final, this is messy, just want everything working in one class
class ConnectorAgent
{
  private DataBasePDO $db;
  private string $DatabaseSchema;
  private $gcloud_token;
  
  private string $gcloud_project_id = "<gcloud_project_id>"; // GCloud project id, should not change
  private string $gcloud_location = "us-west1"; // ideally should not change
  private string $twilio_sid = "<twilio_SID>"; // Main Twilio SID
  private string $twilio_token = "<twilio_token>"; // Main Twilio Token
  private string $twilio_phone_number_sid = "<twilio_phone_number_sid>"; // should be unique and created for each new agent
  private string $twilio_phone_number = ""; // should be unique and created for each new agent
  private string $twilio_webhook_url = "https://www.domain.com/api/voice_agent.php"; // Has to be a TCP FQDN like https://domain.com/endpoint.php
  private string $twilioAvailableAddOnSid_DialogflowCXConnector = "<twilio_dialogCX_SID>"; // sid of marketplace add on for Dialog CX in Twilio, constant
  public string $connector_name; // Must match Twilio Connector, so also use for gcloud agent
  private string $dialogflow_endpoint;
  private string $welcome_event_name = "DEFAULT_WELCOME"; // Name of the first event passed to Dialog CX Agent from the webhook sent from the Twilio number
  private string $gcloud_agentName;

  public function __construct()
  {
    $this->dbSetup();
    $this->gcloudSetup();
  }
  
  // using mysql database hosted on RDS 
  private function dbSetup()
  {
      $this->db = new DataBasePDO();
      $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_OBJ);
      $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

      $this->DatabaseSchema = MasterSchema;
  }
  
  // GCloud creds should be stored and retrieved from config, temporarily putting everything in one class
  private function gcloudSetup()
  {
    $this->dialogflow_endpoint = $this->gcloud_location . "-dialogflow.googleapis.com:443";

    putenv("GOOGLE_APPLICATION_CREDENTIALS=" . ROOT_DIR . "/gcloud-key.json");
    $creds = new ServiceAccountCredentials( 
      ["https://www.googleapis.com/auth/cloud-platform"], 
      ROOT_DIR. "/gcloud-key.json",
      null,
      null
    );

    $this->gcloud_token = $creds->fetchAuthToken()["access_token"];
  }

  // future: probably pass in client_id and save to database as client_id to connector_name mapped to twilio phone number
  public function createAgent(string $connector_name_, string $agent_description_ = "", array $pages_config = [], string $welcome_event_name_ = "DEFAULT_WELCOME")
  {
    if (empty($connector_name_))
    {
      $this->connector_name = "Agent_" . AppHelper::Guid32();
    }
    else
    {
      $this->connector_name = $connector_name_;
    }

    $this->welcome_event_name = $welcome_event_name_;

    $agentsClient = new AgentsClient([ "apiEndpoint" => $this->dialogflow_endpoint ]);
    $intentsClient = new IntentsClient([ "apiEndpoint" => $this->dialogflow_endpoint ]); 
    $pagesClient = new PagesClient([ "apiEndpoint" => $this->dialogflow_endpoint ]);
    $flowsClient = new FlowsClient([ "apiEndpoint" => $this->dialogflow_endpoint ]);
    $webhooksClient = new WebhooksClient([ "apiEndpoint" => $this->dialogflow_endpoint ]);
    $entityTypesClient = new EntityTypesClient([ "apiEndpoint" => $this->dialogflow_endpoint ]);

    $parent = "projects/".$this->gcloud_project_id."/locations/" . $this->gcloud_location;
    $agentsClient->locationName($this->gcloud_project_id, $this->gcloud_location);
    
    // Create agent in Dialog CX
    $agent = new Agent([
        "display_name" => $this->connector_name,
        "default_language_code" => "en",
        "time_zone" => "America/New_York",
        "description" => $agent_description_,
    ]);

    $request = new CreateAgentRequest([
      "parent" => $parent,
      "agent" => $agent
    ]);

    try
    {
      $agentsClientResponse = $agentsClient->createAgent($request);
      $agent_id = explode("/", $agentsClientResponse->getName())[5]; # id is at the end of the name
      $this->gcloud_agentName = $agentsClientResponse->getName();
      //printf("Agent created: %s %s\n", $agent_id, $agentsClientResponse->getName());
    }
    catch (Exception $e)
    {
      printf("Agent Creation Failed: %s\n", $e->getMessage());
    }

    // Define the default flow ID for Dialogflow CX
    $default_flow_id = "00000000-0000-0000-0000-000000000000"; // This is the ID for the "Default Start Flow"
    $default_flow_name = $agentsClientResponse->getName() . "/flows/" . $default_flow_id;

    try
    {
      $defaultFlow = null;
      $listFlowRequest = new ListFlowsRequest();
      $listFlowRequest->setParent($this->gcloud_agentName);
      foreach ($flowsClient->listFlows($listFlowRequest) as $flow)
      {
        if ($flow->getDisplayName() === "Default Start Flow") {
          $defaultFlow = $flow;
          break;
        } 
      }

      if (!$defaultFlow)
      {
        throw new Exception("Couldn't find the Default Start Flow on agent " . $agent_id);
      }

      // Create pages, intents, transitions, and fulfillments from config
      $pagesFullName = [];
      $pagesIntentFulfillments = [];
      foreach ($pages_config as $key => $cfg) 
      {
        // have to create page here, not get page
        $createPageRequest = new CreatePageRequest([
          'parent' => $defaultFlow->getName(),
          'page' => new Page(["display_name" => $cfg['name']]) 
        ]);
        $pageObj = $pagesClient->createPage($createPageRequest);

        $fullName = $pageObj->getName();
        $pagesFullName[$cfg['name']] = $fullName;

        $greet = $cfg['greetings'][0]; //$greet = $cfg['greetings'][array_rand($cfg['greetings'])];
        $pageObj->setEntryFulfillment(new Fulfillment([
          'messages' => [new ResponseMessage(['text' => new Text(['text' => [$greet]])])]
        ]));
        
        $routes = [];
        $formParams = [];
        $formParamNames = []; // for making sure if you have two intents in a page on the chance use the same named input parameter that it won't cause an error down the line for a duplicate form param name
        foreach ($cfg['intents'] as $iname => $intent_data) 
        {
          $intent = [];
          if (!empty($intent_data['intent_needed']) && $intent_data['intent_needed'] != false)
          {
            $tps = array_map(fn($p) => new TrainingPhrase(['parts' => [new Part(['text' => $p])],'repeat_count'=>1]), $intent_data['training_phrases']);
            $intent = $intentsClient->createIntent(new CreateIntentRequest([
              'parent' => "$parent/agents/$agent_id",
              'intent' => new Intent([
                'display_name' => $iname,
                'training_phrases' => $tps,
                'parameters' => []
              ])
            ]));
          }

          // check adding a webhook vs static message for fulfillment 
          $fulfill = [];
          if (!empty($cfg['webhooks']))
          {
            $webhook_data = $cfg['webhooks'][$iname];

            // Build the form to make the parameter to grab from the user mandatory
            $request_body = [];
            if (!empty($webhook_data['input_parameters']))
            {
              foreach ($webhook_data['input_parameters'] as $input) 
              {
                if (!in_array($input['name'], $formParamNames)) 
                {
                  $formParamNames[] = $input['name'];
                  $formParams[] = new FormParameter([
                    'display_name' => $input['name'],
                    'entity_type' => "projects/-/locations/-/agents/-/entityTypes/sys.any",//$createdEntityType->getName(), //"$parent/agents/$agent_id/entityTypes/sys.string", // instead of worrying about types like @sys.number, just easier
                    'required' => true,
                    'fill_behavior' => new FillBehavior([
                      'initial_prompt_fulfillment' => new Fulfillment([
                        'messages' => [new ResponseMessage(['text' => new Text(['text' => [$input['prompt']]])])]
                      ])
                    ])
                  ]);
                }
                
                // add it as part of the payload 
                $request_body[$input['name']] = '${session.params.' . $input['name'] . '}';
              }
            }
            
            // then we can create the webhook
            $webhookTag = $cfg['name'] . '_webhook_' . AppHelper::Guid32();
            $webhook = new Webhook([
              'display_name' => $webhookTag,
              'generic_web_service' => new GenericWebService([
                'uri' => $webhook_data['endpoint'],
                'http_method' => $webhook_data['http_method'],
                // 'timeout' => ['seconds' => 5],
                'request_body' => json_encode($request_body),
                'request_headers' => ['Content-Type' => 'application/json'],
              ])
            ]);

            $createWebhookRequest = new CreateWebhookRequest([
              'parent' => "$parent/agents/$agent_id",
              'webhook' => $webhook
            ]);

            $webhookResponse = $webhooksClient->createWebhook($createWebhookRequest);
            $webhookName = $webhookResponse->getName();
            
            $fulfill = new Fulfillment([
              'webhook' => $webhookName,
              'tag' => $webhookTag,
              // set_parameter_actions is more for static session vars to carry over, not dynamic
              // 'set_parameter_actions' => [
              //   new SetParameterAction([
              //     'parameter' => $webhook_data['response_parameter_name'],
              //     'value' => new Value([ 'string_value' => ('$.data.' . $webhook_data['response_parameter_name']) ]) // endpoint should return { "data" : {"parameter_name" : value}}
              //   ])
              // ],
              'messages' => [
                new ResponseMessage(['text' => new Text(['text' => [$intent_data['response']]])]) // response message will contain something like "Response ${session.params.parameter_name}"
              ]
            ]);
            
          } 
          else
          {
            $fulfill = new Fulfillment([
              'messages' => [new ResponseMessage(['text' => new Text(['text' => [$intent_data['response']]])])] // we might want an array of responses we can randomize
            ]);
          }

          $pagesIntentFulfillments[$cfg['name']][$iname] = [
            'intent' => $intent,
            'condition' => $intent_data['condition'] ?? '',
            'fulfillment' => $fulfill,
            'target_page' => $intent_data['target_page'] 
          ];
           
        }

        if (!empty($formParams))
        {
          $pageObj->setForm(new Form([
            'parameters' => $formParams,
          ]));
        }

         
        $pagesClient->updatePage((new UpdatePageRequest())->setPage($pageObj));
      }

      // now that pages are created, can move forward with adding transitions
      foreach ($pages_config as $key => $cfg)
      {
        $pageObj = $pagesClient->getPage(new GetPageRequest(['name' => $pagesFullName[$cfg['name']]]));
        $routes = [];

        foreach ($pagesIntentFulfillments[$cfg['name']] as $transition)
        {
          $route = new TransitionRoute();
          $target_page = $defaultFlow->getName() . '/pages/END_SESSION';
          if (!empty($transition['target_page']))
          {
            $target_page = $pagesFullName[$transition['target_page']];
          }
          
          if (!empty($transition['intent']))
          {
            $route->setIntent($transition['intent']->getName());
          }

          if (!empty($transition['condition']))
          {
            $route->setCondition($transition['condition']);
          }

          $route->setTriggerFulfillment($transition['fulfillment']);
          $route->setTargetPage($target_page);
          $routes[] = $route;
        }
        
        $pageObj->setTransitionRoutes($routes);
        // print_r($pageObj);
        $pagesClient->updatePage((new UpdatePageRequest())->setPage($pageObj));
      }

      // add welcome event handler and default transition to first page
      $firstPageFull = $pagesFullName["ROOT"];
      $handler = new EventHandler([
        "event" => $this->welcome_event_name,
        "target_page" => $firstPageFull
      ]);
      $currentHandlers = $defaultFlow->getEventHandlers();
      $currentHandlers[] = $handler;
      $defaultFlow->setEventHandlers($currentHandlers);

      $transitionRoute = new TransitionRoute([
        "condition" => "true",
        "target_page" => $firstPageFull
      ]);
      $currentRoutes = $defaultFlow->getTransitionRoutes();
      $currentRoutes[] = $transitionRoute;
      $defaultFlow->setTransitionRoutes($currentRoutes);

      $flowsClient->updateFlow(new UpdateFlowRequest(['flow' => $defaultFlow]));
      
      // update default Start Page "Default Welcome Intent" to have a true condition always to next page ROOT
      $defaultWelcomeIntent = $intentsClient->getIntent(new GetIntentRequest([ "name" => $this->gcloud_agentName . "/intents/00000000-0000-0000-0000-000000000000" ]));
      $defaultWelcomeIntent->setTrainingPhrases([]);
      $intentsClient->updateIntent(new UpdateIntentRequest([ "intent" => $defaultWelcomeIntent ]));
  
      $transitionRoute = new TransitionRoute([
        "intent" => $defaultWelcomeIntent->getName(),
        "condition" => "true",
        "trigger_fulfillment" => null,
        "target_page" => $firstPageFull
      ]);
  
      $currentRoutes = $defaultFlow->getTransitionRoutes();
      $currentRoutes[] = $transitionRoute;
      unset($currentRoutes[0]); // get rid of the default transition route Dialogflow automatically makes
      $defaultFlow->setTransitionRoutes($currentRoutes);
  
      $updateFlow = new UpdateFlowRequest(["flow" => $defaultFlow]);
      $flowsClient->updateFlow($updateFlow);
    } 
    catch (Exception $e) 
    {
      printf("<br/>Failed to create page: %s\n", $e->getMessage());
      echo $e->getTraceAsString();
    } 
    finally 
    { 
      $pagesClient->close();
      $flowsClient->close(); 
      $intentsClient->close();
      $agentsClient->close();
      $webhooksClient->close(); 
    }
    
    try
    { 
      // future: probably pass in client_id into phone number function to associate per client
      $this->assignAgentPhoneNumber();
      $this->gcloudAgentTwilioIntegration();
    }
    catch (Exception $e)
    {
      printf("Failed at phone number or Twilio integration: %s", $e->getMessage());
      echo $e->getTraceAsString();
    }
  }
  
  private function newPhoneNumber()
  {
    // figure out twilio"s api for new phone number   
    // store the created sid in $this->twilio_phone_number_sid 

    // If we want to set a price limit 
    //$maxPrice = 5.00;
    //$maxUnit = 'USD';
    $twilio = new TwilioClient($this->twilio_sid, $this->twilio_token);
    $availableNumbers = $twilio->availablePhoneNumbers('US')->local->read([], 20);
    $newNumber = $twilio->incomingPhoneNumbers->create([
      'phoneNumber' => $availableNumbers[0]->phoneNumber,
      // 'friendlyName' => $this->connector_name
    ]);
    
    $this->twilio_phone_number = $newNumber->phoneNumber; 
    $this->twilio_phone_number_sid = $newNumber->sid;
  }
  
  // future: pass in client_id as parameter 
  private function assignAgentPhoneNumber()
  {
    $this->newPhoneNumber();

    $twilio = new TwilioClient($this->twilio_sid, $this->twilio_token);
    $number = $twilio->incomingPhoneNumbers($this->twilio_phone_number_sid)->update([
        "voiceUrl" => $this->twilio_webhook_url,
        "voiceMethod" => "POST",
    ]);

    // TODO: save number to database with client_id and connector_name
  }
  
  private function gcloudAgentTwilioIntegration()
  {
    // Connecting Twilio Integration to Google Dialog CX Agent
    $assistClient = new GuzzleHttp\Client([
        "base_uri"=> "https://" . $this->gcloud_location . "-dialogflow.googleapis.com/v2beta1/", // TODO: need to use a non-beta endpoint
        "headers" => [ 
          "Authorization" => "Bearer " . $this->gcloud_token,
          "Content-Type" => "application/json"
        ]
    ]);

    $body = [
      "displayName" => $this->connector_name." Profile",
      "enableVirtualAgent" => true,
      "automatedAgentConfig" => [
        "agent" => $this->gcloud_agentName
      ]
    ];

    $resp = $assistClient->post(
        "projects/".$this->gcloud_project_id."/locations/".$this->gcloud_location."/conversationProfiles",
        [ "json" => $body ]
    );

    $profile = json_decode((string)$resp->getBody(), true);
    $convProfile = $profile["name"];
    $convProfileId = explode("/", $convProfile)[5];

    // Install Twilio Virtual Agent Connector
    $twilio = new TwilioClient($this->twilio_sid, $this->twilio_token);
    $configuration = [
      "projectId" => $this->gcloud_project_id,
      "language" => "en-us",
      "voiceName" => "en-US-Standard-H",
      "agentLocation" => $this->gcloud_location,
      "welcomeIntent" => $this->welcome_event_name,
      "credentialObject" => [ "conversationProfileId" => $convProfileId ]
    ];

    $connector = $twilio->preview->marketplace->installedAddOns->create(
      $this->twilioAvailableAddOnSid_DialogflowCXConnector, 
      true, 
      [
        "UniqueName" => $this->connector_name,
        "Configuration" => json_encode($configuration),
      ]
    );

    // if we want to save this for some reason
    //$twilio_connector_sid = $connector->sid;
  }
}

?>
