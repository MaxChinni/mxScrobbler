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

	function __construct() {
		$this->useProxy = FALSE;
		$this->proxyUrl = NULL;
		$this->proxyPort = 8080;
		$this->proxyType = CURLOPT_PROXYTYPE;
		$this->proxyAuth = CURLAUTH_BASIC;
		$this->proxyUserPassword = '';

		$this->responseFormat = 'json';
		$this->sessionKey = NULL;
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
			throw new Exception('Now '.__CLASS__.' only supports json');
		}
		if (!in_array($responseFormat, Array('xml', 'json'))) {
			throw new Exception("Error \"$responseFormat\" is not xml or json");
		} else {
			$this->responseFormat = $responseFormat;
		}
		return($this);
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

	private function lastfmCall($method, $params, $authentication=FALSE) {
		/*
		 * example:
		 * http://ws.audioscrobbler.com/2.0/?method=artist.getSimilar&api_key=abcd...
		 */
		$params['method'] = $method;
		$params['api_key'] = $this->apikey;
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
			throw new Exception("Error $this->errno: $this->errMsg");
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
					throw new Exception("Error $this->errno: $this->errMsg");
				}
				break;
			case 'xml':
				$response = $this->parseXml($response);
				break;
			default:
				throw new Exception("Error unsupported responseFormat: \"$this->responseFormat\"");
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
		$authentication = FALSE;
		$authToken = md5($this->username.md5($this->password));

		$params = Array(
			'username' => $this->username,
			'authToken' => $authToken
		);
		$this->signParams($method, $params);

		$response = $this->lastfmCall($method, $params, $authentication);
		$this->sessionKey = $response['session']['key'];

		return(TRUE);
	}

	function artistGetSimilar($artist) {
		$method = 'artist.getSimilar';
		$params = Array(
			'limit' => 5,
			'artist' => $artist,
			'autocorrect' => 1,
			'mbid' => NULL
		);
		$response = $this->lastfmCall($method, $params);
		return($response);
	}

	function trackGetSimilar($artist, $track) {
		$method = 'track.getSimilar';
		$params = Array(
			'artist' => $artist,
			'track' => $track,
			'autocorrect' => 1,
			'limit' => 5,
			'mbid' => NULL
		);
		$response = $this->lastfmCall($method, $params);
		return($response);
	}

	function trackGetCorrection($artist, $track) {
		$method = 'track.getCorrection';
		$params = Array(
			'artist' => $artist,
			'track' => $track
		);
		$response = $this->lastfmCall($method, $params);
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
			'sk' => $this->sessionKey
		);
		$this->signParams($method, $params);

		$response = $this->lastfmCall($method, $params);
		return($response);
	}
}

?>
