<?php
require('mxScrobbler.class.php');
require('config-local.inc.php');

/*
 * Config
 */
$testDebug = TRUE;
$testAuthentication = TRUE;
$testScrobble = TRUE;
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

/*
 * Scrobble
 */
if ($testScrobble) {
	$scrobbler->scrobble('The Rolling Stones', 'Wild Horses', '2011-01-28 12:00:00');
}

/*
 * artist.getSimilar
 */
//$artist = 'The Rolling Stones';
//$similar = $scrobbler->artistGetSimilar($artist);
//print_r($similar);

echo "\n";
?>
