<?php
require('mxScrobbler.class.php');
require('config-local.inc.php');

$scrobbler = new mxScrobbler();
$scrobbler->debug = FALSE;
$scrobbler->setApiKey($apikey);
$scrobbler->setSharedSecret($sharedSecret);
$scrobbler->setUsername($username)->setPassword($password);

/*
 * Authentication
 */
$scrobbler->mobileAuth();

/*
 * artist.getSimilar
 */
$artist = "Cake";
$similar = $scrobbler->artistGetSimilar($artist);
echo "I've found someone similar to $artist:\n";
foreach($similar['similarartists']['artist'] as $n => $data) {
	echo $data['name']."\n";
}

/*
 * scrobble
 */
//$scrobbler->scrobble('The Rolling Stones', 'Wild Horses', '2011-01-28 12:00:00');

echo "\n";
?>
