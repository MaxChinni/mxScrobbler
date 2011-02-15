<?php
/**
 * mxScrobbler
 *
 * Copyright 2011 Massimiliano Chinni
 *
 * This file is part of mxScrobbler.
 *
 * mxScrobbler is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later version.
 *
 * mxScrobbler is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with mxScrobbler. If not, see http://www.gnu.org/licenses/.
 *
 *
 * @author Massimiliano Chinni <m.chinni@gmail.com>
 *
 */

class mxScrobbler {

	const API_ROOT = 'http://ws.audioscrobbler.com/2.0/';
	const ENCODING = 'UTF-8';

	public $debug;
	protected $apikey;
	protected $sharedSecret;
	protected $username;
	protected $password;
	protected $sessionKey;
	protected $responseFormat;
	protected $useProxy;
	protected $proxyAuth;
	protected $proxyUserPassword;
	protected $proxyUrl;
	protected $proxyPort;
	protected $methods;

	function __construct() {
		$this->useProxy = FALSE;
		$this->proxyUrl = NULL;
		$this->proxyPort = 8080;
		$this->proxyType = CURLOPT_PROXYTYPE;
		$this->proxyAuth = CURLAUTH_BASIC;
		$this->proxyUserPassword = '';

		$this->responseFormat = 'json';
		$this->sessionKey = NULL;

		/*
		 * method definitions
		 */
		// auth
		$this->createMethod('auth.getMobileSession', 'username; authToken');
		//
		//$this->createMethod('album.addTags', NULL);
		//$this->createMethod('album.getBuylinks', NULL);
		$this->createMethod('album.getInfo', '(artist, album)|mbid; ?lang; ?autocorrect; ?username');
		//$this->createMethod('album.getShouts', NULL);
		$this->createMethod('album.getTags', '(artist, album)|mbid; ?autocorrect', TRUE, TRUE, TRUE);
		//$this->createMethod('album.getTopTags', NULL);
		//$this->createMethod('album.removeTag', NULL);
		$this->createMethod('album.search', '?limit; ?page; album');
		//$this->createMethod('album.share', NULL);
		//$this->createMethod('artist.addTags', NULL);
		$this->createMethod('artist.getCorrection', 'artist');
		$this->createMethod('artist.getEvents', 'artist|mbid; autocorrect');
		//$this->createMethod('artist.getImages', NULL);
		$this->createMethod('artist.getInfo', '(artist|mbid); ?lang; ?autocorrect; ?username');
		//$this->createMethod('artist.getPastEvents', NULL);
		//$this->createMethod('artist.getPodcast', NULL);
		//$this->createMethod('artist.getShouts', NULL);
		$this->createMethod('artist.getSimilar', 'artist|mbid; ?autocorrect; ?limit');
		//$this->createMethod('artist.getTags', NULL);
		//$this->createMethod('artist.getTopAlbums', NULL);
		//$this->createMethod('artist.getTopFans', NULL);
		//$this->createMethod('artist.getTopTags', NULL);
		//$this->createMethod('artist.getTopTracks', NULL);
		//$this->createMethod('artist.removeTag', NULL);
		$this->createMethod('artist.search', 'artist; ?limit; ?page');
		//$this->createMethod('artist.share', NULL);
		//$this->createMethod('artist.shout', NULL);
		//$this->createMethod('auth.getMobileSession', NULL);
		//$this->createMethod('auth.getSession', NULL);
		//$this->createMethod('auth.getToken', NULL);
		//$this->createMethod('chart.getHypedArtists', NULL);
		//$this->createMethod('chart.getHypedTracks', NULL);
		//$this->createMethod('chart.getLovedTracks', NULL);
		//$this->createMethod('chart.getTopArtists', NULL);
		//$this->createMethod('chart.getTopTags', NULL);
		//$this->createMethod('chart.getTopTracks', NULL);
		//$this->createMethod('event.attend', NULL);
		//$this->createMethod('event.getAttendees', NULL);
		//$this->createMethod('event.getInfo', NULL);
		//$this->createMethod('event.getShouts', NULL);
		//$this->createMethod('event.share', NULL);
		//$this->createMethod('event.shout', NULL);
		//$this->createMethod('geo.getEvents', NULL);
		//$this->createMethod('geo.getMetroArtistChart', NULL);
		//$this->createMethod('geo.getMetroHypeArtistChart', NULL);
		//$this->createMethod('geo.getMetroHypeTrackChart', NULL);
		//$this->createMethod('geo.getMetroTrackChart', NULL);
		//$this->createMethod('geo.getMetroUniqueArtistChart', NULL);
		//$this->createMethod('geo.getMetroUniqueTrackChart', NULL);
		//$this->createMethod('geo.getMetroWeeklyChartlist', NULL);
		//$this->createMethod('geo.getMetros', NULL);
		//$this->createMethod('geo.getTopArtists', NULL);
		//$this->createMethod('geo.getTopTracks', NULL);
		//$this->createMethod('group.getHype', NULL);
		//$this->createMethod('group.getMembers', NULL);
		//$this->createMethod('group.getWeeklyAlbumChart', NULL);
		//$this->createMethod('group.getWeeklyArtistChart', NULL);
		//$this->createMethod('group.getWeeklyChartList', NULL);
		//$this->createMethod('group.getWeeklyTrackChart', NULL);
		//$this->createMethod('library.addAlbum', NULL);
		//$this->createMethod('library.addArtist', NULL);
		//$this->createMethod('library.addTrack', NULL);
		//$this->createMethod('library.getAlbums', NULL);
		//$this->createMethod('library.getArtists', NULL);
		//$this->createMethod('library.getTracks', NULL);
		//$this->createMethod('playlist.addTrack', NULL);
		//$this->createMethod('playlist.create', NULL);
		//$this->createMethod('playlist.fetch', NULL);
		//$this->createMethod('radio.getPlaylist', NULL);
		//$this->createMethod('radio.search', NULL);
		//$this->createMethod('radio.tune', NULL);
		//$this->createMethod('tag.getInfo', NULL);
		//$this->createMethod('tag.getSimilar', NULL);
		//$this->createMethod('tag.getTopAlbums', NULL);
		//$this->createMethod('tag.getTopArtists', NULL);
		//$this->createMethod('tag.getTopTags', NULL);
		//$this->createMethod('tag.getTopTracks', NULL);
		//$this->createMethod('tag.getWeeklyArtistChart', NULL);
		//$this->createMethod('tag.getWeeklyChartList', NULL);
		//$this->createMethod('tag.search', NULL);
		//$this->createMethod('tasteometer.compare', NULL);
		//$this->createMethod('tasteometer.compareGroup', NULL);
		//$this->createMethod('track.addTags', NULL);
		//$this->createMethod('track.ban', NULL);
		//$this->createMethod('track.getBuylinks', NULL);
		//$this->createMethod('track.getCorrection', NULL);
		//$this->createMethod('track.getFingerprintMetadata', NULL);
		$this->createMethod('track.getInfo', '(artist, track)|mbid; ?autocorrect; ?username');
		//$this->createMethod('track.getShouts', NULL);
		$this->createMethod('track.getSimilar', '(artist, track)|mbid; ?autocorrect; ?limit');
		//$this->createMethod('track.getTags', NULL);
		//$this->createMethod('track.getTopFans', NULL);
		//$this->createMethod('track.getTopTags', NULL);
		//$this->createMethod('track.love', NULL);
		//$this->createMethod('track.removeTag', NULL);
		$this->createMethod('track.scrobble', 'track; timestamp; artist; ?album; ?albumArtist; ?context; ?streamId; ?trackNumber; ?mbid; ?duration', TRUE, TRUE, TRUE);
		$this->createMethod('track.search', '?limit; ?page; track; ?artist');
		//$this->createMethod('track.share', NULL);
		//$this->createMethod('track.unban', NULL);
		//$this->createMethod('track.unlove', NULL);
		//$this->createMethod('track.updateNowPlaying', NULL);
		//$this->createMethod('user.getArtistTracks', NULL);
		//$this->createMethod('user.getBannedTracks', NULL);
		//$this->createMethod('user.getEvents', NULL);
		//$this->createMethod('user.getFriends', NULL);
		//$this->createMethod('user.getInfo', NULL);
		//$this->createMethod('user.getLovedTracks', NULL);
		//$this->createMethod('user.getNeighbours', NULL);
		//$this->createMethod('user.getNewReleases', NULL);
		//$this->createMethod('user.getPastEvents', NULL);
		//$this->createMethod('user.getPersonalTags', NULL);
		//$this->createMethod('user.getPlaylists', NULL);
		//$this->createMethod('user.getRecentStations', NULL);
		//$this->createMethod('user.getRecentTracks', NULL);
		//$this->createMethod('user.getRecommendedArtists', NULL);
		//$this->createMethod('user.getRecommendedEvents', NULL);
		//$this->createMethod('user.getShouts', NULL);
		//$this->createMethod('user.getTopAlbums', NULL);
		//$this->createMethod('user.getTopArtists', NULL);
		//$this->createMethod('user.getTopTags', NULL);
		//$this->createMethod('user.getTopTracks', NULL);
		//$this->createMethod('user.getWeeklyAlbumChart', NULL);
		//$this->createMethod('user.getWeeklyArtistChart', NULL);
		//$this->createMethod('user.getWeeklyChartList', NULL);
		//$this->createMethod('user.getWeeklyTrackChart', NULL);
		//$this->createMethod('user.shout', NULL);
		//$this->createMethod('venue.getEvents', NULL);
		//$this->createMethod('venue.getPastEvents', NULL);
		//$this->createMethod('venue.search', NULL);
	}

	function setApiKey($apikey) {
		$this->apikey = $apikey;
		return($this);
	}

	function setSharedSecret($sharedSecret) {
		$this->sharedSecret = $sharedSecret;
		return($this);
	}

	function setUsername($username) {
		$this->username = $username;
		return($this);
	}

	function setPassword($password) {
		$this->password = $password;
		return($this);
	}

	function setCredentials($username, $password) {
		$this->setUsername($username);
		$this->setPassword($password);
	}

	function setResponseFormat($responseFormat) {
		$this->responseFormat = '';
		// TODO support for xml
		if ($responseFormat !== 'json') {
			throw new mxScrobblerException('Now '.__CLASS__.' only supports json');
		}
		if (!in_array($responseFormat, Array('xml', 'json'))) {
			throw new mxScrobblerException("Error \"$responseFormat\" is not xml or json");
		} else {
			$this->responseFormat = $responseFormat;
		}
		return($this);
	}
	
	protected function createMethod($name, $params='', $authRequired=FALSE, $signatureRequired=TRUE, $sessionRequired=FALSE) {
		$this->methods[$name] = Array(
			'params' => $params,
			'auth' => $authRequired,
			'signature' => $signatureRequired,
			'session' => $sessionRequired
		);
		return($this);
	}

	protected function explodeMethodDefinedParams($methodName) {
		$piece = explode(';', $this->methods[$methodName]['params']);
		foreach($piece AS $n => $value) {
			$piece[$n] = trim($value);
		}
		return($piece);
	}

	protected function verifyMethod($name, $params) {
		if (!isset($this->methods[$name])) {
			throw new mxScrobblerException("$name is an unknown method");
		}
		$definedParams = $this->explodeMethodDefinedParams($name);
		$knownParams = Array();

		foreach($definedParams as $p) {
			if (strpos($p, '|') !== FALSE) {
				// check or
				$pieces = explode('|', $p);
				$condition = Array();
				foreach($pieces AS $n => $piece) {
					$piece = trim(strtr($piece, '()', '  '));
					$condition[$piece] = TRUE;
					foreach(explode(',', $piece) AS $n1 => $p1) {
						$p1 = trim($p1);
						if (!isset($params[$p1])) {
							$condition[$piece] = FALSE;
						} else {
							// $p1 already checked, remove
							unset($params[$p1]);
						}
					}
				}
				$totalVerify = NULL;
				foreach($condition AS $n => $cond) {
					if ($totalVerify === NULL) {
						$totalVerify = $cond;
					} else {
						$totalVerify = $totalVerify || ($cond === TRUE);
					}
				}
				if (!$totalVerify) {
					throw new mxScrobblerException("Method $name must have \"$p\"");
				}
			} else {
				// check optional / required
				if ($p[0] != '?') {
					$knownParams[] = $p;
					if (!isset($params[$p])) {
						throw new mxScrobblerException("Param \"$p\" is required for method $name");
					}
				} else {
					$knownParams[] = substr($p, 1, strlen($p)-1);
				}
			}
		}

		// check unknown
		foreach($params as $name => $value) {
			if (!in_array($name, $knownParams)) {
				throw new mxScrobblerException("Unknown param \"$name\"");
			}
		}

		return(TRUE);
	}

	function callMethod($methodName, $params) {
		$p = implode(', ', $params);
		try {
			$this->verifyMethod($methodName, $params);
		} 
		catch(mxScrobblerException $e) {
			echo __CLASS__.'->'.__FUNCTION__. '(): '.$e->errorMessage()."\n";
			return(TRUE);
		}
		if ($this->debug) {
			echo "Method is ok, run $methodName($p)\n";
		}
		$response = $this->lastfmCall($methodName, $params);
		return($response);
	}

	function scrobble($artist, $track, $date = NULL) {
		date_default_timezone_set('UTC');
		$method = 'track.scrobble';
		$timestamp = date('U');

		if ($date !== NULL) {
			$timestamp = date('U', strtotime($date));
		}
		$params = Array(
			'track' => $track,
			'timestamp' => $timestamp,
			'artist' => $artist,
		);
		$response = $this->lastfmCall($method, $params);
		return($response);
	}
	protected function getSignature($method, $params) {
		$auth_sig = '';

		$params['method'] = $method;
		ksort($params);
		foreach($params as $name => $value) {
			$auth_sig .= $name.$value;
		}
		$auth_sig .= $this->sharedSecret;
		$auth_sig = md5($auth_sig);

		return($auth_sig);
	}

	protected function signParams($method, &$params) {
		$params['api_key'] = $this->apikey;
		$params['api_sig'] = $this->getSignature($method, $params);
		return($this);
	}

	protected function lastfmCall($method, $params) {
		/*
		 * example:
		 * http://ws.audioscrobbler.com/2.0/?method=artist.getSimilar&api_key=abcd...
		 */
		$params['method'] = $method;
		$params['api_key'] = $this->apikey;

		/*
		 * check auth, session, signature
		 */
		if ($this->methods[$method]['auth']) {
			// check authentication
			if (!isset($this->sessionKey)) {
				throw new mxScrobblerException("Authentication required for $method");
			}
		}
		if ($this->methods[$method]['session']) {
			if (!isset($this->sessionKey)) {
				throw new mxScrobblerException("Session required for $method");
			}
			// add session param
			$params['sk'] = $this->sessionKey;
		}
		if ($this->methods[$method]['signature']) {
			// sign params
			$this->signParams($method, $params);
		}

		if ($this->responseFormat !== '') {
			$params['format'] = $this->responseFormat;
		}

		/*
		 * curl setup
		 */
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $this::API_ROOT);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, __CLASS__);
		if ($this->useProxy) {
			curl_setopt($ch, CURLOPT_PROXY, $this->proxyUrl);
			curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxyPort);
			curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxyType);
			curl_setopt($ch, CURLOPT_PROXYAUTH, $this->proxyAuth);
			curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyUserPassword);
		}
		curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

		/*
		 * curl exec
		 */
		if (($response = curl_exec($ch)) === FALSE) {
			$this->errno = NULL;
			$this->errMsg = curl_error($ch);
			throw new mxScrobblerException("Error $this->errno: $this->errMsg");
		} else {
			if ($this->debug) {
				print_r($response);
				echo "\n";
			}
			$response = $this->parseResponse($response);
		}
		curl_close($ch);
		return($response);
	}

	protected function parseResponse($response) {
		switch ($this->responseFormat) {
			case 'json':
				$response = json_decode($response, TRUE);
				if (isset($response['error'])) {
					$this->errno = $response['error'];
					$this->errMsg = $response['message'];
					throw new mxScrobblerException("Error $this->errno: $this->errMsg");
				}
				break;
			case 'xml':
				$response = $this->parseXml($response);
				break;
			default:
				throw new mxScrobblerException("Error unsupported responseFormat: \"$this->responseFormat\"");
				break;
		}
		return($response);
	}

	protected function parseXml($xmlString) {
    $xml_values = Array();
    $parser = xml_parser_create('');
    if (!$parser)
			return false;
    xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
    xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
    xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
    xml_parse_into_struct($parser, trim($xmlString), $xml_values);
    xml_parser_free($parser);
    if (!$xml_values)
			return array();
    $xml_array = array();
    $last_tag_ar =& $xml_array;
    $parents = array();
    $last_counter_in_tag = array(1=>0);
    foreach ($xml_values as $data) {
			switch($data['type']) {
				case 'open':
					$last_counter_in_tag[$data['level']+1] = 0;
					$new_tag = array('name' => $data['tag']);
					if(isset($data['attributes']))
						$new_tag['attributes'] = $data['attributes'];
					if(isset($data['value']) && trim($data['value']))
						$new_tag['value'] = trim($data['value']);
					$last_tag_ar[$last_counter_in_tag[$data['level']]] = $new_tag;
					$parents[$data['level']] =& $last_tag_ar;
					$last_tag_ar =& $last_tag_ar[$last_counter_in_tag[$data['level']]++];
					break;
				case 'complete':
					$new_tag = array('name' => $data['tag']);
					if(isset($data['attributes']))
						$new_tag['attributes'] = $data['attributes'];
					if(isset($data['value']) && trim($data['value']))
						$new_tag['value'] = trim($data['value']);

					$last_count = count($last_tag_ar)-1;
					$last_tag_ar[$last_counter_in_tag[$data['level']]++] = $new_tag;
					break;
				case 'close':
					$last_tag_ar =& $parents[$data['level']];
					break;
				default:
					break;
			};
    }
    return $xml_array;
	}

	function getValueByPath($__xml_tree, $__tag_path) {
		$tmp_arr =& $__xml_tree;
		$tag_path = explode('/', $__tag_path);
		foreach($tag_path as $tag_name) {
			$res = false;
			foreach($tmp_arr as $key => $node) {
				if(is_int($key) && $node['name'] == $tag_name) {
					$tmp_arr = $node;
					$res = true;
					break;
				}
			}
			if(!$res)
				return false;
		}
		return $tmp_arr;
	}

	function mobileAuth() {
		$method = 'auth.getMobileSession';
		$authToken = md5($this->username.md5($this->password));

		$params = Array(
			'username' => $this->username,
			'authToken' => $authToken
		);
		$response = $this->lastfmCall($method, $params);
		$this->sessionKey = $response['session']['key'];

		return(TRUE);
	}

}

class mxScrobblerException extends Exception {
	public function errorMessage() {
		$errorMsg = 'Error on line '.$this->getLine();
		$errorMsg .= ' in '.basename($this->getFile()).': ';
		$errorMsg .= $this->getMessage();
		return($errorMsg);
	}
}

?>
