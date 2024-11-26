<?php
/*
 * Plugin Name:        Coinsnap Bitcoin Paywall
 * Plugin URI:         https://coinsnap.io
 * Description:        A plugin for Paywall using Coinsnap and BTCPay.
 * Version:            1.0.0
 * Author:             Coinsnap
 * Author URI:         https://coinsnap.io/
 * Text Domain:        coinsnap-bitcoin-paywall
 * Domain Path:         /languages
 * Requires PHP:        7.4
 * Tested up to:        6.7.1
 * Requires at least:   6.2
 * License:             GPL2
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Network:             true
 */ 

defined( 'ABSPATH' ) || exit;
define( 'COINSNAP_REFERRAL_CODE', 'D72896' );
define( 'COINSNAP_VERSION', '1.0.0' );

register_activation_hook( __FILE__, "coinsnap_bitcoin_paywall_activate" );
register_uninstall_hook( __FILE__, 'coinsnap_bitcoin_paywall_uninstall' );

function coinsnap_bitcoin_paywall_activate(){
    global $wpdb;
    $table_name = $wpdb->prefix . 'coinsnap_bitcoin_paywall_access';

    $wpdb->query($wpdb->prepare("CREATE TABLE IF NOT EXISTS %i (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        session_id INT NOT NULL,
        access_expires DATETIME NOT NULL)",$table_name));
}

/**
 * Uninstall callback to clean up the database.
 */
function coinsnap_bitcoin_paywall_uninstall() {
	global $wpdb;

	// Get the table name
	$table_name = $wpdb->prefix . 'coinsnap_bitcoin_paywall_access';

	// Drop the table
	$wpdb->query( $wpdb->prepare("DROP TABLE IF EXISTS %i",$table_name) );
}

// Include the handler classes
require_once plugin_dir_path( __FILE__ ) . 'includes/class-coinsnap-bitcoin-paywall-btcpay-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-coinsnap-bitcoin-paywall-coinsnap-handler.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-coinsnap-bitcoin-paywall-scripts.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-coinsnap-bitcoin-paywall-shortcode.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-coinsnap-bitcoin-paywall-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-coinsnap-bitcoin-paywall-post-type.php';

class CoinsnapBticoinPaywall {
	public function __construct() {

		// Register AJAX handlers for payment initiation
		add_action( 'wp_ajax_coinsnap_create_invoice', [ $this, 'create_invoice' ] );
		add_action( 'wp_ajax_nopriv_coinsnap_create_invoice', [ $this, 'create_invoice' ] );

		// Restrict content
		add_filter( 'the_content', [ $this, 'restrict_page_content' ] );

		add_action( 'wp_ajax_check_invoice_status', [ $this, 'check_invoice_status' ] );
		add_action( 'wp_ajax_nopriv_check_invoice_status', [ $this, 'check_invoice_status' ] );

		add_action( 'wp_ajax_coinsnap_bitcoin_paywall_grant_access', [ $this, 'coinsnap_bitcoin_paywall_grant_access' ] );
		add_action( 'wp_ajax_nopriv_coinsnap_bitcoin_paywall_grant_access', [ $this, 'coinsnap_bitcoin_paywall_grant_access' ] );
	}

	public function check_invoice_status() {
		if ( null === filter_input(INPUT_POST,'invoice_id',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ) {
			wp_send_json_error( 'Invoice ID is required' );
		}

		$invoice_id = sanitize_text_field( filter_input(INPUT_POST,'invoice_id',FILTER_SANITIZE_FULL_SPECIAL_CHARS) );
		$provider   = get_option( 'coinsnap_bitcoin_paywall_options' )['provider'];

		$handler = $this->get_provider_handler( $provider );

		if ( ! $handler ) {
			wp_send_json_error( 'Invalid provider' );
		}

		$invoice = $handler->getInvoiceStatus( $invoice_id );

		if ( isset( $invoice['status'] ) ) {
			wp_send_json_success( [
				'status'      => $invoice['status'],
				'checkoutUrl' => $invoice['checkoutLink'] ?? null,
			] );
		} else {
			wp_send_json_error( [ 'status' => 'Pending', 'message' => 'Invoice is not settled' ] );
		}
	}

	public function create_invoice() {
		if ( empty( filter_input(INPUT_POST,'amount',FILTER_VALIDATE_FLOAT) ) || empty( filter_input(INPUT_POST,'currency',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ) ) {
			wp_send_json_error( [ 'message' => 'Invalid request parameters.' ] );
		}

		$provider    = get_option( 'coinsnap_bitcoin_paywall_options' )['provider'];
		$price       = sanitize_text_field( filter_input(INPUT_POST,'amount',FILTER_VALIDATE_FLOAT) );
		$currency    = sanitize_text_field( filter_input(INPUT_POST,'currency',FILTER_SANITIZE_FULL_SPECIAL_CHARS) );
		$redirectUrl = sanitize_text_field( filter_input(INPUT_POST,'currentPage',FILTER_SANITIZE_FULL_SPECIAL_CHARS) );

		$handler = $this->get_provider_handler( $provider );

		if ( ! $handler ) {
			wp_send_json_error( [ 'message' => 'Invalid provider' ] );
		}

		$invoice = $handler->createInvoice( $price, $currency, $redirectUrl );

		if ( $invoice && isset( $invoice['data']['checkoutLink'] ) ) {
                    
                    
                    
			$ids = [
				'invoice_id' => $invoice['data']['id'] ?? null,
				'post_id'    => filter_input(INPUT_POST,'postId',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? null,
			];

			setcookie( 'coinsnap_initiated_' . (filter_input(INPUT_POST,'postId',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? ''), wp_json_encode( $ids ), time() + 900, '/' );

			wp_send_json_success( [ 'invoice_url' => $invoice['data']['checkoutLink'] ] );
		} else {
			error_log( 'Invoice creation failed: ' . print_r( $invoice, true ) );
			wp_send_json_error( [ 'message' => 'Failed to create invoice' . $invoice["body"] ] );
		}
	}

	/**
	 * Get the appropriate handler based on the provider.
	 *
	 * @param string $provider
	 * @return object|null
	 */
	private function get_provider_handler( $provider ) {
		switch ( $provider ) {
			case 'btcpay':
				return new Coinsnap_Bitcoin_Paywall_BTCPayHandler(
					get_option( 'coinsnap_bitcoin_paywall_options' )['btcpay_store_id'],
					get_option( 'coinsnap_bitcoin_paywall_options' )['btcpay_api_key'],
					get_option( 'coinsnap_bitcoin_paywall_options' )['btcpay_url']
				);

			case 'coinsnap':
				return new Coinsnap_Bitcoin_Paywall_CoinsnapHandler(
					get_option( 'coinsnap_bitcoin_paywall_options' )['coinsnap_store_id'],
					get_option( 'coinsnap_bitcoin_paywall_options' )['coinsnap_api_key']
				);

			default:
				return null;
		}
	}

	public function coinsnap_bitcoin_paywall_has_access( $post_id, $session_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'coinsnap_bitcoin_paywall_access';

		$access = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM %i WHERE post_id = %d AND session_id = %s AND access_expires > NOW()",
			$table_name, $post_id, $session_id
		) );

		return $access !== null;
	}

	public function coinsnap_bitcoin_paywall_grant_access() {
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}
		// Get and use the session ID
		$session_id = session_id();
		// Debug incoming data
		error_log( print_r( $_POST, true ) );

		if ( empty( filter_input(INPUT_POST,'post_id',FILTER_SANITIZE_FULL_SPECIAL_CHARS) ) || empty( filter_input(INPUT_POST,'duration',FILTER_VALIDATE_INT) ) ) {
			wp_send_json_error( 'Missing required parameters' );
		}

		$post_id  = sanitize_text_field( filter_input(INPUT_POST,'post_id',FILTER_SANITIZE_FULL_SPECIAL_CHARS) );
		$duration = intval( filter_input(INPUT_POST,'duration',FILTER_VALIDATE_INT) );

		// Debug session_id
		error_log( 'Session ID: ' . $session_id );

		if ( ! $session_id ) {
			wp_send_json_error( 'Session not initialized' );
		}

		$access_expires = gmdate( 'Y-m-d H:i:s', time() + ( $duration * 3600 ) );

		global $wpdb;
		$table_name = $wpdb->prefix . 'coinsnap_bitcoin_paywall_access';

		$result = $wpdb->insert( $table_name, [
			'post_id'        => $post_id,
			'session_id'     => $session_id,
			'access_expires' => $access_expires,
		] );

		// Debug query execution
		if ( $result === false ) {
			error_log( 'Database Error: ' . $wpdb->last_error );
			wp_send_json_error( 'Database insertion failed' );
		}

		wp_send_json_success();
	}


	public function restrict_page_content( $content ) {
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}
		// Get and use the session ID
		$session_id = session_id();
		$post_id    = get_the_ID();

		if ( has_shortcode( $content, 'paywall_payment' ) && ! $this->coinsnap_bitcoin_paywall_has_access( $post_id, $session_id ) ) {
			$parts           = explode( '[paywall_payment', $content );
			$shortcode_parts = explode( ']', $parts[1], 2 );
			$shortcode       = '[paywall_payment' . $shortcode_parts[0] . ']';

			return $parts[0] . $shortcode;
		} elseif ( has_shortcode( $content, 'paywall_payment' ) && $this->coinsnap_bitcoin_paywall_has_access( $post_id, $session_id ) ) {
			$content = preg_replace( '/\[paywall_payment[^\]]*\]/', '', $content );

			return $content;
		}

		return $content;
	}
}

new CoinsnapBticoinPaywall();
