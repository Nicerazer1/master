<?php
/**
 * Cuttly API shortener class.
 *
 * @package UtmDotCodes
 */

namespace UtmDotCodes;

/**
 * Class Cuttly.
 */
class Cuttly implements \UtmDotCodes\Shorten {

	const API_URL = 'https://cutt.ly/api/api.php';

	/**
	 * API credentials for Cuttly API. 
	 *
	 * @var string|null The API key for the shortener.
	 */
	private $api_key;

	/**
	 * Response from API.
	 *
	 * @var object|null The response object from the shortener.
	 */
	private $response;

	/**
	 * Error message.
	 *
	 * @var object|null Error object with code and message properties.
	 */
	private $error_code;

	/**
	 * Cuttly constructor.
	 *
	 * @param string $api_key Credentials for API.
	 */
	public function __construct( $api_key ) {
    // API Key will be retrieved from utm.codes settings.
		$this->api_key = $api_key;
	}

	/**
	 * See interface for docblock.
	 *
	 * @inheritDoc
	 *
	 * @param array  $data See interface.
	 * @param string $query_string See interface.
	 *
	 * @return void
	 */
	public function shorten( $data, $query_string ) {
		if ( isset( $data['meta_input'] ) ) {
			$data = $data['meta_input'];
		}
    
		if ( '' !== $this->api_key ) {
      // get response data from cuttly service
			$response = wp_remote_get(
				self::API_URL,
				// Selective overrides of WP_Http() defaults.
				array(
					'headers'     => array(
						'Authorization' => $this->api_key, 
						'Content-Type'  => 'application/json',
					),
					'short' => wp_json_encode( array( 'short' => $data['utmdclink_url'] . $query_string ) ),
          // TO DO: pass short link name to Cuttly API service.
          // 'name' => 'SHORT_LINK_NAME'
				)
			);

      // Set error code from API response
      // Error message display is handled on get_error_message in utmdotcodes
			if ( isset( $response->errors ) ) {
				$this->error_code = 100;
			} else {
				$body          = json_decode( $response['body'] );
				$response_code = intval( $response['response']['code'] );

				if ( 200 === $response_code || 201 === $response_code ) {
					$response_url = '';

					if ( isset( $body->link ) ) {
						$response_url = $body->link;
					}

					if ( filter_var( $response_url, FILTER_VALIDATE_URL ) ) {
						$this->response = esc_url( wp_unslash( $body->link ) );
					}
				} elseif ( 403 === $response_code ) {
					$this->error_code = 403;
				} else {
					$this->error_code = 500;
				}
			}
		}
	}

	/**
	 * Get response from Cuttly API for the request.
	 *
	 * @inheritDoc
	 */
	public function get_response() {
		return $this->response;
	}

	/**
	 * Get error code/message returned by Cuttly API for the request.
	 *
	 * @inheritDoc
	 */
	public function get_error() {
		return $this->error_code;
	}
}
