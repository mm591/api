#!/usr/bin/php
<?php
require_once('path.inc');
require_once('get_host_info.inc');
require_once('rabbitMQLib.inc');

function foodInfo($request)
{
	try
	{
		$req = $request['brandName'];
		$url = "https://api.fda.gov/food/enforcement.json?search=product_description=".$req."";	
		$result = file_get_contents($url);
		
		$data = json_decode($result, true);
		
		$country = $data['results'][0]['country'];
		$prodDes = $data['results'][0]['product_description'];
		$reasonForRecall = $data['results'][0]['reason_for_recall'];

		$stmt = array();
		$stmt['country'] = $country;
		$stmt['prodDes'] = $prodDes;
		$stmt['reason'] = $reasonForRecall;
		return $stmt;

		
	}
	catch (Exception $e)
	{
		$client = new rabbitMQClient("testRabbitMQ.ini", "testServer");
		$request = array();
		$request["type"] = "log";
		$request["message"] = $e->getMessage();
		$client->publish($request);
		echo ("\nException: ". $e->getMessage());
	}
}

function requestProcessor($request)
{
	echo "\nreceived request".PHP_EOL;
	if(!isset($request['type']))
	{
		return "ERROR: Unsupported Message Type";
	} 
	switch ($request['type'])
	{
		 case "api":
			echo "Returned Result for: " . $request['brandName'];
		 return foodInfo($request);
	}

	
   return array("returnCode" => '0', 'message'=>"Server received request and processed");


}


$server = new rabbitMQServer("apiRabbitMQ.ini","apiServer");
$server->process_requests('requestProcessor');
exit();

?>
