<?php

/**
 * A Tropo SMS script that queries the ScraperWiki API for flight details.
 */

// Constants used to access Scraperwiki API.
define("SCRAPERWIKI_API_URL", "http://api.scraperwiki.com/api/1.0/datastore/sqlite");
define("SCRAPERWIKI_FORMAT", "jsondict");
define("SCRAPERWIKI_NAME", "table1");
define("SCRAPERWIKI_QUERY", "select%20*%20from%20%60swdata%60%20where%20%60flight_num%60%20%3D%20%22[[flight_num]]%22%20and%20date%20%3D%20%22[[date]]%22%20and%20%60flight_type%60%20%3D%20%22[[direction]]%22");

// Function to fetch JSON for a specific flight from Scrapewiki.
function getFlightInfo($flight_num, $date, $direction) {
	
	$direction = (strtolower($direction) == "d") ? "SEA" : "AIR";
	$query = str_replace(array("[[flight_num]]", "[[date]]", "[[direction]]"), array($flight_num, $date, $direction), SCRAPERWIKI_QUERY);
	$url = SCRAPERWIKI_API_URL."?format=".SCRAPERWIKI_FORMAT."&name=".SCRAPERWIKI_NAME."&query=".$query;
	return json_decode(file_get_contents($url));

}

// Format response based on channel.
function formatResponse($direction, $flight_info, $channel) {
	
	// Determine if the flight is an arrival or departure.
	$leaveorarrive = (strtolower($direction) == "d") ? "updated by" : "updated by";
	$gate = (strtolower($direction) == "d") ? "  " : "  ";
	
	// Format the flight number for the channel used.
	$flight_num = $channel == "VOICE" ? implode(" ", str_split($flight_info->flight_num)) : $flight_info->flight_num;
	
	// Properly case destination an remarks.
	$destination = ucwords(strtolower($flight_info->destination));
	$remarks = ucwords(strtolower($flight_info->remarks));
	
	// Build response to user.
	$say = $flight_info->airline . " Tracking " . $flight_num . " $leaveorarrive " . $destination . " at " . $flight_info->time . "$gate  " . $flight_info->gate . " " . $remarks;
	return $say;
}

// Set the date.
$date = date("m.d.y");

if($currentCall->channel == "VOICE") {
	say("Thank you for calling the zigsa tracking.");
	$flight = ask("Please say or enter your tracking number.", array("choices" => "[1-24 DIGITS]", "attempts" => 3, "timeout" => 5));
	$flight_type = ask("Is your cargo coming by air or by sea?", array("choices" => "air, sea", "attempts" => 3, "timeout" => 5));
	
	$flight_num = $flight->value;
	$direction = $flight_type->value;
}
else {
	// Get flight number entered by user.
	$message = explode(" ", $currentCall->initialText);
	$flight_num = $message[0];
	$direction = $message[1];
}

try {
	$flight_info = getFlightInfo($flight_num, $date, $direction);
	if(count($flight_info) == 0) {
		say("No information found for tracking $flight_num on $date.");
	}
	else {
		$say = formatResponse($direction, $flight_info[0], $currentCall->channel);
		say($say);	
	}
}

catch (Exception $ex) {
	say("Sorry, could not look up tracking info. Please try again later.");
}

?>
