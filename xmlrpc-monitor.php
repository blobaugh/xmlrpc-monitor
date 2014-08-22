<?php
/*
Plugin Name: XMLRPC Monitor
Plugin URI: http://github.com/blobaugh/xmlrpc-monitor
Description: Logs all activity on the XMLRPC interface
Version: 0.6
Author: Ben Lobaugh
Author URI: http://ben.lobaugh.net
*/

require_once( 'class.xmlrpc-incoming.php' );

add_action( 'init', function() {
	register_post_type( 'xmlrpc_request' );
	xmlrpc_incoming_log::get_instance();
});
/*
 * Setup the subpage that will list all the calls.
 *
 * This subpage exists under the Tools menu
 */
add_action( 'admin_menu', function( $hook ) {
	$hook = add_management_page( __( 'XMLRPC Requests' ), __( 'XMLRPC Requests' ), 'manage_options', 'xmlrpc_requests', 'xmlrpc_admin_page' );
});

add_action( 'plugins_loaded', 'xmlrpc_maybe_export' );

function xmlrpc_admin_page() {

	xmlrpc_maybe_empty_logs();

	require_once( 'class.xmlrpc-request-list-table.php' );
	$lt = new xmlrpc_Request_List_Table();
	echo '<div class="wrap"><h2>' . __( 'XMLRPC Requests' ) . '</h2>';
	echo '<form method="post">';
/*	echo '<p>';
	submit_button( __( 'Export to CSV' ), 'secondary', 'xmlrpc_export', false );
	echo '&nbsp;';
	submit_button( __( 'Empty log' ), 'secondary', 'xmlrpc_empty_log', false );
	echo '</p>';
*/	$lt->prepare_items();
	$lt->display();
	echo '</form></div>';
}

function xmlrpc_maybe_export() {
	if( !isset( $_POST['xmlrpc_export'] ) ) {
		return;
	}
	
	header('Content-Type: application/csv');
	header('Content-Disposition: inline; filename="xmlrpc_export.csv"');
	header("Pragma: no-cache");
	header("Expires: 0");

	// Get data
	$args = array(
		'post_type' => 'xmlrpc_request',
		'post_status' => 'incoming',
		'posts_per_page' => -1
	);
	$requests = get_posts( $args ); 

	$data = array();
	foreach( $requests AS $p ) {
		$item = json_decode( $p->post_content );
		$out = fopen( "php://output", 'w' );
		$data = array(
			ucfirst( $p->post_status ),
			$item->type,
			$item->url,
			date_i18n( get_option('date_format') . ' ' . get_option('time_format'), $item->time),
			$item->duration,
		);
		fputcsv( $out, $data );
	}
	fclose( $out );
	exit();
}


function xmlrpc_maybe_empty_logs() {
	if( !isset( $_POST['jrc_empty_log'] ) ) {
		return;
	}

	// Get data
	$args = array(
		'post_type' => 'xmlrpc_request',
		'post_status' => array( 'incoming', 'outgoing' ),
		'posts_per_page' => -1
	);
	$requests = get_posts( $args ); 

	foreach( $requests AS $p ) {
		$post = wp_delete_post( $p->ID, true );
		error_log( print_r( $post, true ) );
		error_log( "Deleting: " . $p->ID );
	}
}
