<?php
/**
 * Packagist.php
 *
 * @copyright Copyright (c) 2014 DreamFactory Software, Inc.
 * @link      DreamFactory Software, Inc. <http://www.dreamfactory.com>
 * @package   custom-trigger
 * @filesource
 */
namespace DreamFactory\JetPack\Packagist;

use Kisma\Core\Utility\Curl;

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

	//*************************************************************************
	//	Methods
	//*************************************************************************

	/**
	 * Retrieves a list of packages
	 */
	public function getPackages()
	{
		return static::get( static::DEFAULT_ENDPOINT_BASE . '/packages.json' );
	}

	public function trackDownload( $packageName )
	{
		return static::post( static::DEFAULT_ENDPOINT_BASE . '/downloads/' . $packageName )
	}

	/**
	 * @param array $packages
	 *
	 * @return bool|mixed|\stdClass
	 */
	public function trackDownloads( array $packages )
	{
		$_packages = null;

		foreach ( $packages as $_package )
		{
			$_packages = array(
				'name'    => Option::get( $_package, 'name' ),
				'version' => Option::Get( $_package, 'version', '*' ),
			);
		}

		return static::post(
			static::DEFAULT_ENDPOINT_BASE . '/downloads/',
			$_packages,
			array( CURLOPT_HTTPHEADER => array( 'Content-Type: application/json' ) )
		);
	}
}
