<?php
require('mxScrobbler.class.php');
require('config-local.inc.php');

/*
 * Config
 */
$testDebug = TRUE;
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

/*
 * Scrobble
 */
if ($testScrobble) {
	$scrobbler->scrobble('The Rolling Stones', 'Wild Horses', '2011-01-28 12:00:00');
}

/*
 * artist.getSimilar
 */
$artist = 'Blondie';
$track = 'one way or another';
//$similar = $scrobbler->artistGetSimilar($artist);
$similar = $scrobbler->trackGetSimilar($artist, $track);
//$similar = $scrobbler->trackGetCorrection($artist, $track);
print_r($similar);

echo "\n";
?>
