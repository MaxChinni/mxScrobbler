<?php
require('mxScrobbler.class.php');
require('config-local.inc.php');

if ($argc !== 4) {
	printf("\nUsage:\n");
	printf("  %s <datetime> <artist> <track>\n\n", basename(__FILE__));
	exit(1);
}

$scrobbler = new mxScrobbler();
//$scrobbler->debug = TRUE;
$scrobbler->setApiKey($apikey);
$scrobbler->setSharedSecret($sharedSecret);
$scrobbler->setUsername($username)->setPassword($password);
$scrobbler->setResponseFormat('');

/*
 * Authentication
 */
$scrobbler->mobileAuth();

/*
 * scrobble
 */
$ts = $argv[1];
$artist = $argv[2];
$track = $argv[3];
$response = $scrobbler->scrobble($artist, $track, $ts);
print_r($response);

echo "\n";
?>
