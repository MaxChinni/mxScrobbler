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
		$params['api_sig'] = $this->getSignature($method, $params);
		return($this);
	}

	private function lastfmCall($method, $params, $authentication=FALSE) {
		/*
		 * example:
		 * http://ws.audioscrobbler.com/2.0/?method=artist.getSimilar&api_key=abcd...
		 */
		$params['method'] = $method;
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
			$response = json_decode($response, TRUE);
			if (isset($response['error'])) {
				$this->errno = $response['error'];
				$this->errMsg = $response['message'];
				throw new Exception("Error $this->errno: $this->errMsg");
			}
		}
		curl_close($ch);
		return($response);
	}

	function mobileAuth() {
		$method = 'auth.getMobileSession';
		$authentication = FALSE;
		$authToken = md5($this->username.md5($this->password));

		$params = Array(
			'username' => $this->username,
			'authToken' => $authToken,
			'api_key' => $this->apikey,
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
			'mbid' => NULL,
			'api_key' => $this->apikey
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
			'api_key' => $this->apikey,
			'sk' => $this->sessionKey
		);
		$this->signParams($method, $params);

		$response = $this->lastfmCall($method, $params);
		return($response);
	}
}

?>
