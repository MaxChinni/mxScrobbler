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
	private $apikey;
	private $sharedSecret;
	private $username;
	private $password;
	private $sessionKey;
	private $responseFormat;
	private $useProxy;
	private $proxyAuth;
	private $proxyUserPassword;
	private $proxyUrl;
	private $proxyPort;
	private $methods;

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
		// artist
		$this->createMethod('artist.getSimilar', 'artist|mbid; ?autocorrect; ?limit');
		$this->createMethod('artist.search', 'artist; ?limit; ?page');
		// track
		$this->createMethod('track.scrobble', 'track; timestamp; artist; ?album; ?albumArtist; ?context; ?streamId; ?trackNumber; ?mbid; ?duration', TRUE, TRUE, TRUE);
		$this->createMethod('track.getInfo', '(artist, track)|mbid; ?autocorrect; ?username');
		$this->createMethod('track.getSimilar', '(artist, track)|mbid; ?autocorrect; ?limit');
		$this->createMethod('track.search', '?limit; ?page; track; ?artist');
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
	
	private function createMethod($name, $params='', $authRequired=FALSE, $signatureRequired=TRUE, $sessionRequired=FALSE) {
		$this->methods[$name] = Array(
			'params' => $params,
			'auth' => $authRequired,
			'signature' => $signatureRequired,
			'session' => $sessionRequired
		);
		return($this);
	}

	private function explodeMethodDefinedParams($methodName) {
		$piece = explode(';', $this->methods[$methodName]['params']);
		foreach($piece AS $n => $value) {
			$piece[$n] = trim($value);
		}
		return($piece);
	}

	private function verifyMethod($name, $params) {
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
	private function getSignature($method, $params) {
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

	private function signParams($method, &$params) {
		$params['api_key'] = $this->apikey;
		$params['api_sig'] = $this->getSignature($method, $params);
		return($this);
	}

	private function lastfmCall($method, $params) {
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

	private function parseResponse($response) {
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

	private function parseXml($xmlString) {
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
