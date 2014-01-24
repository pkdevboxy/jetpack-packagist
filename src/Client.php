<?php
/**
 * This file is part of the Packagist API JetPack
 * Copyright 2014 DreamFactory Software, Inc. <support@dreamfactory.com>
 *
 * DSP Packagist API <http://github.com/dreamfactorysoftware/jetpack-packagist>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
namespace DreamFactory\JetPack\Packagist;

use Kisma\Core\Utility\Curl;
use Kisma\Core\Utility\Option;

/**
 * Client
 * A general-purpose library for access to Packagist
 */
class Client extends Curl
{
	//*************************************************************************
	//	Constants
	//*************************************************************************

	/**
	 * @type string
	 */
	const DEFAULT_ENDPOINT_BASE = 'https://packagist.org';
	/**
	 * @type string
	 */
	const DEFAULT_USER_AGENT = 'DreamFactory/1.0 (Linux; x64; +http://www.dreamfactory.com) JetPack_Packagist_Client/1.0';

	//*************************************************************************
	//	Members
	//*************************************************************************

	/**
	 * @var array
	 */
	protected $_endpoints = array(
		'notify'       => '/downloads/%package%',
		'notify-batch' => '/downloads',
		'package'      => '/p/%package%.json',
		'packages'     => '/packages/list.json',
		'ping'         => '/packages.json',
		'search'       => '/search.json',
	);

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Retrieves a single of package
	 *
	 * @param string $packageName
	 *
	 * @return bool|mixed|\stdClass
	 */
	public function getPackage( $packageName )
	{
		return static::_apiRequest(
			str_replace( '%package%', $packageName, $this->_endpoints['package'] )
		);
	}

	/**
	 * Retrieves a list of all packages
	 *
	 * @return bool|mixed|\stdClass
	 */
	public function getPackages()
	{
		return static::_apiRequest( $this->_endpoints['packages'] );
	}

	/**
	 * @param string $query
	 * @param array  $tags
	 *
	 * @return bool|mixed|\stdClass
	 */
	public function search( $query, array $tags = array() )
	{
		return static::_apiRequest(
			$this->_endpoints['search'],
			array( 'q' => $query, 'tags' => $tags )
		);
	}

	/**
	 * @param string $packageName
	 *
	 * @return bool|mixed|\stdClass
	 */
	public function notify( $packageName )
	{
		return static::_apiRequest(
			str_replace( '%package%', $packageName, $this->_endpoints['notify'] ),
			null,
			null,
			static::Post
		);
	}

	/**
	 * @param array $packages
	 *
	 * @return bool|mixed|\stdClass
	 */
	public function notifyBatch( array $packages )
	{
		$_packages = null;

		foreach ( $packages as $_package )
		{
			$_packages = array(
				'name'    => Option::get( $_package, 'name' ),
				'version' => Option::Get( $_package, 'version', '*' ),
			);
		}

		return static::_apiRequest(
			$this->_endpoints['notify-batch'],
			$_packages,
			array(
				CURLOPT_HTTPHEADER => array( 'Content-Type: application/json' ),
			),
			static::Post
		);
	}

	/**
	 * @param string $resource
	 * @param array  $payload
	 * @param array  $curlOptions
	 * @param string $method
	 *
	 * @return bool|mixed|\stdClass
	 */
	protected function _apiRequest( $resource, $payload = array(), $curlOptions = array(), $method = self::Get )
	{
		$_payload = Option::clean( $payload );

		$_url = static::DEFAULT_ENDPOINT_BASE . '/' . ltrim( $resource, '/' );

		if ( !empty( $_payload ) && static::Get != $method )
		{
			$_url = static::buildUrl( $_url, $_payload );
			$_payload = array();
		}

		return static::request(
			$method,
			$_url,
			$_payload,
			array_merge(
				array(
					CURLOPT_USERAGENT => Option::server( 'HTTP_USER_AGENT', static::DEFAULT_USER_AGENT ),
				),
				Option::clean( $curlOptions )
			)
		);
	}

	/**
	 * Retrieves the metadata of Packagist and sets the endpoints accordingly
	 *
	 * @return bool|mixed|null|\stdClass
	 * @throws \RuntimeException
	 */
	public function ping()
	{
		$_data = static::_apiRequest( $this->_endpoints['ping'] );

		if ( empty( $_data ) )
		{
			if ( false === $_data )
			{
				throw new \RuntimeException( 'Cannot connect to requested service url' );
			}

			return null;
		}

		foreach ( $_data as $_key => $_value )
		{
			if ( is_string( $_value ) && array_key_exists( $_key, $this->_endpoints ) )
			{
				$this->_endpoints[$_key] = $_value;
			}
		}

		return $_data;
	}
}
