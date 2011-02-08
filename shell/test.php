<?php
require(dirname(__FILE__).'/../mxScrobbler.class.php');
require(dirname(__FILE__).'/../config-local.inc.php');

/*
 * Config
 */
$testDebug = FALSE;
$testAuthentication = FALSE;
$testScrobble = FALSE;
$responseFormat = 'json';

/*
 * Init
 */
$scrobbler = new mxScrobbler();
$scrobbler->debug = $testDebug;
$scrobbler->setApiKey($apikey);
$scrobbler->setSharedSecret($sharedSecret);
$scrobbler->setResponseFormat($responseFormat);

/*
 * Authentication
 */
if ($testAuthentication) {
	$scrobbler->setUsername($username)->setPassword($password);
	$scrobbler->mobileAuth();
}

//$response = $scrobbler->callMethod('artist.getSimilar', Array('artist' => 'Blondie', 'limit' => 2));
//$params = Array(
//	'artist' => 'The Rolling Stones', 
//	'track' => 'Wild Horses', 
//	'timestamp' => 1296212400
//);
//$response = $scrobbler->callMethod('track.scrobble', $params);
//$response = $scrobbler->callMethod('artist.search', Array('artist' => 'Blondie'));
//$response = $scrobbler->callMethod('track.getInfo', Array('artist' => 'Blondie', 'track' => 'Heart of glass'));
//$response = $scrobbler->callMethod('track.search', Array('track' => 'Heart of glass'));
//$params = Array(
//	'artist' => 'Ligabue', 
//	'album' => 'Ligabue',
//	'autocorrect' => 1
//);
//$params = Array(
//	'mbid' => '6853210f-8ed4-4b4c-8bd1-81d43ba33422'
//);
//$response = $scrobbler->callMethod('album.getInfo', $params);
//$response = $scrobbler->callMethod('artist.getCorrection', Array('artist' => 'Rolling stones'));
$response = $scrobbler->callMethod('artist.getEvents', Array('artist' => 'Rolling stones', 'autocorrect' => 1));

print_r($response);
echo "\n";
?>
