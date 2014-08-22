<?php

/**
 * Logs all incoming calls to xmlrpc 
 */

class xmlrpc_incoming_log {

	/**
	 * Start of request in microtime()
	 *
	 * @var float
	 */
	private $timer_start;

	/**
	 * End of request in microtime()
	 *
	 * @var float
	 */
	private $timer_end;

	/**
	 * Holds a reference to the only running object
	 *
	 * @var xmlrpc_incoming_log
	 */
	private static $instance = null;

	private function __construct() {

		if ( !defined( 'XMLRPC_REQUEST' ) || !XMLRPC_REQUEST ) {
			return;
		}

		$this->log_request();
	}	

	/**
	 * Gets access to the object
	 *
	 * @since 0.6
	 * @return xmlrpc_incoming_log
	 */
	public static function get_instance() {
		if( !is_null( self::$instance ) ) {
			return self::$instance;
		}

		self::$instance = new xmlrpc_incoming_log();

		return self::$instance;
	}

	/**
	 * Handles logging all requests
	 *
	 * @since 0.6
	 */
	public function log_request() {
			$url = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );
			
			$data = array(
				'post_title'  => time(),
				'post_type'	=> 'xmlrpc_request',
				'post_status' => 'incoming',
				'post_content' => json_encode( array(
					'type' => $this->get_method(),
					'url' => $url,
					'duration' => '-1',
					'time' 	=> time()
				) )
			);
			$post_id = wp_insert_post( $data );
	}


	/**
	 * Return the method the external client is making to the
	 * local WordPress site
	 *
	 * @since 0.6
	 * @return string
	 */
	private function get_method() {
		/*
		 * Figure out which xmlrpc method is being called.
		 * This is surprisingly difficult and not exposed by
		 * WP core, however the following are the steps
		 * core takes to determing the method and duplicating
		 * them seems to work!
		 */
		require_once( ABSPATH . WPINC . '/class-IXR.php' );	
		global $HTTP_RAW_POST_DATA;
		$ixr_message = new IXR_Message( $HTTP_RAW_POST_DATA );
		$ixr_message->parse();
		return $ixr_message->methodName;
	}

} // end class
