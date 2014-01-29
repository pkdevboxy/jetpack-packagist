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

/**
 * ClientTest
 */

use DreamFactory\Oasys\Oasys;
use DreamFactory\Oasys\Stores\FileSystem;
use Kisma\Core\Interfaces\HttpMethod;
use Kisma\Core\Utility\Log;

/**
 * ClientTest
 * Tests the methods in the Oasys class
 */
class ClientTest extends \PHPUnit_Framework_TestCase implements HttpMethod
{
	protected function setUp()
	{
		Log::setDefaultLog( __DIR__ . '/log/error.log' );

		Oasys::setStore( new FileSystem( __FILE__ ) );

		parent::setUp();
	}

	/**
	 * @covers Client
	 */
	public function testGetPackage()
	{
		$_packageName = 'dreamfactory/dsp-core';

		return static::_apiRequest( $this->_getEndpoint( 'package', $_packageName ) );
	}

	/**
	 * Retrieves a list of all packages
	 *
	 * @return bool|mixed|\stdClass
	 */
	public function getPackages()
	{
		return static::_apiRequest( $this->_getEndpoint( 'packages' ) );
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
			$this->_getEndpoint( 'search' ),
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
			$this->_getEndpoint( 'notify', $packageName ),
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
			$this->_getEndpoint( 'notify-batch' ),
			$_packages,
			array(
				CURLOPT_HTTPHEADER => array( 'Content-Type: application/json' ),
			),
			static::Post
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
		$_data = static::_apiRequest( $this->_getEndpoint( 'ping' ) );

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
	 * @param string       $which
	 * @param string|array $packageName
	 * @param string       $hash
	 *
	 * @throws \InvalidArgumentException
	 * @return string|array
	 */
	protected function _getEndpoint( $which, $packageName = null, $hash = null )
	{
		if ( null === $which )
		{
			return $this->_endpoints;
		}

		if ( null === ( $_endpoint = Option::get( $this->_endpoints, $which ) ) )
		{
			throw new \InvalidArgumentException( 'The endpoint type of "' . $which . '" is invalid.' );
		}

		//	Replace macros if necessary
		if ( false !== stripos( '{{package}}', $_endpoint ) || false !== stripos( '{{hash}}', $_endpoint ) )
		{
			if ( empty( $packageName ) || ( !is_string( $packageName ) && !is_array( $packageName ) ) )
			{
				if ( is_string( $packageName ) )
				{
					$packageName = array( '{{package}}' => $$packageName, '{{hash}}' => $hash );
				}
			}
		}

		return str_ireplace( array_keys( $packageName ), array_values( $packageName ), $_endpoint );
	}
}
