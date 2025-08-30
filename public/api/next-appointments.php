<?php
header('Content-Type: application/json');
header("HTTP/1.1 200 OK");

// Read and parse the incoming JSON request
$input = json_decode(file_get_contents('php://input'), true);

// Log to debug
file_put_contents('/tmp/dialogflow_input.log', print_r($input, true));


// Return the parameter in expected format
$response = [
  "sessionInfo" => [
    "parameters" => [
      "next_appts" => "August 20th, 2025"
    ]
  ],
  // if you want to send back a custom response. For example, if it too overdue, you can send back a response saying a late fee will be applied. 
  // This will be said after the Agent Response specified in webhook on agent creation.
  // "fulfillment_response" => [
  //   "messages" => [
  //     [
  //       "text" => [
  //         "text" => ["Your current bill is \$session.params.bill"]
  //       ]
  //     ]
  //   ]
  // ]
];

echo json_encode($response);

