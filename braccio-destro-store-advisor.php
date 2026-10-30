<?php
/**
 * Plugin Name:       Braccio Destro Store Advisor
 * Description:       Connects your WooCommerce store to Braccio Destro with a read-only key: AI experts read orders, products and stock and tell you every morning what to do.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Andrea Motta
 * Author URI:        https://www.ceotrust.it
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       braccio-destro-store-advisor
 * Requires Plugins:  woocommerce
 * WC requires at least: 6.0
 * WC tested up to:     11.2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CEOTRUST_ENDPOINT', 'https://xmbgjjucxlmcgkxcjxwi.supabase.co/functions/v1/shop' );

/** Pagina "Braccio Destro" nel menu WooCommerce. */
add_action( 'admin_menu', function () {
	add_submenu_page( 'woocommerce', 'Braccio Destro', 'Braccio Destro', 'manage_woocommerce', 'braccio-destro', 'ceotrust_page' );
}, 99 );

/** Link "Collega" nella lista dei plugin. */
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=braccio-destro' ) ) . '">' . esc_html__( 'Connect', 'braccio-destro-store-advisor' ) . '</a>' );
	return $links;
} );

/** Disattivazione o disinstallazione: avviso Braccio Destro (che cancella i dati letti) e cancello la chiave di lettura. */
register_deactivation_hook( __FILE__, 'ceotrust_disconnect' );
register_uninstall_hook( __FILE__, 'ceotrust_uninstall' );

function ceotrust_uninstall() {
	ceotrust_disconnect();
	delete_option( 'ceotrust_conn' );
}

function ceotrust_page() {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'Permission denied.', 'braccio-destro-store-advisor' ) );
	}
	$notice = '';
	if ( isset( $_POST['ceotrust_connect'] ) && check_admin_referer( 'ceotrust' ) ) {
		$code = isset( $_POST['ceotrust_code'] ) ? sanitize_text_field( wp_unslash( $_POST['ceotrust_code'] ) ) : '';
		$res  = ceotrust_connect( $code );
		$notice = true === $res
			? '<div class="notice notice-success"><p>' . esc_html__( 'Store connected to Braccio Destro. Go back to www.ceotrust.it: your orders and products will be there within a few minutes.', 'braccio-destro-store-advisor' ) . '</p></div>'
			: '<div class="notice notice-error"><p>' . esc_html( $res ) . '</p></div>';
	} elseif ( isset( $_POST['ceotrust_disconnect'] ) && check_admin_referer( 'ceotrust' ) ) {
		ceotrust_disconnect();
		$notice = '<div class="notice notice-success"><p>' . esc_html__( 'Store disconnected: the read-only key has been deleted and Braccio Destro is deleting the data it read.', 'braccio-destro-store-advisor' ) . '</p></div>';
	}
	$conn = get_option( 'ceotrust_conn' );
	echo '<div class="wrap"><h1>Braccio Destro Store Advisor</h1>' . $notice; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above
	echo '<form method="post">';
	wp_nonce_field( 'ceotrust' );
	if ( ! class_exists( 'WooCommerce' ) ) {
		echo '<p>' . esc_html__( 'WooCommerce must be active to connect the store.', 'braccio-destro-store-advisor' ) . '</p>';
	} elseif ( is_array( $conn ) && ! empty( $conn['key_id'] ) ) {
		/* translators: %s: date and time the store was connected. */
		echo '<p>' . esc_html( sprintf( __( 'The store has been connected to Braccio Destro (read-only) since %s.', 'braccio-destro-store-advisor' ), $conn['at'] ) ) . '</p>';
		echo '<p>' . esc_html__( 'Braccio Destro reads orders, products and stock every night. It cannot change anything in the store.', 'braccio-destro-store-advisor' ) . '</p>';
		echo '<p><button type="submit" name="ceotrust_disconnect" class="button" onclick="return confirm(\'' . esc_js( __( 'Disconnect the store? Braccio Destro will delete the data it read.', 'braccio-destro-store-advisor' ) ) . '\')">' . esc_html__( 'Disconnect from Braccio Destro', 'braccio-destro-store-advisor' ) . '</button></p>';
	} else {
		echo '<p>' . esc_html__( '1. Get your connection code from Braccio Destro:', 'braccio-destro-store-advisor' ) . ' <a class="button" href="https://www.ceotrust.it/?vai=plugin&amp;p=woocommerce" target="_blank" rel="noopener">' . esc_html__( 'Open Braccio Destro and copy the code', 'braccio-destro-store-advisor' ) . '</a></p>';
		echo '<p>' . esc_html__( '2. Paste it below and click Connect. Braccio Destro receives a key that can only read orders, products and stock.', 'braccio-destro-store-advisor' ) . '</p>';
		echo '<p><input type="text" name="ceotrust_code" class="regular-text" style="text-transform:uppercase" maxlength="20" placeholder="K7Q2M9TX" required> ';
		echo '<button type="submit" name="ceotrust_connect" class="button button-primary">' . esc_html__( 'Connect', 'braccio-destro-store-advisor' ) . '</button></p>';
	}
	echo '</form><p><a href="https://www.ceotrust.it/privacy.html" target="_blank" rel="noopener">' . esc_html__( 'How Braccio Destro handles your data', 'braccio-destro-store-advisor' ) . '</a></p></div>';
}

/** Crea una chiave REST di WooCommerce in sola lettura e la manda a Braccio Destro con il codice. */
function ceotrust_connect( $code ) {
	global $wpdb;
	$code = strtoupper( preg_replace( '/[^A-Za-z0-9]/', '', (string) $code ) );
	if ( strlen( $code ) < 6 ) {
		return __( 'The code is incomplete: copy it again from Braccio Destro.', 'braccio-destro-store-advisor' );
	}
	if ( ! function_exists( 'wc_api_hash' ) || ! function_exists( 'wc_rand_hash' ) ) {
		return __( 'WooCommerce must be active to connect the store.', 'braccio-destro-store-advisor' );
	}
	if ( '' === (string) get_option( 'permalink_structure' ) ) {
		return __( 'With “Plain” permalinks WooCommerce does not allow external reads. Go to Settings → Permalinks, choose “Post name” (or any other option), save and try again.', 'braccio-destro-store-advisor' );
	}
	ceotrust_disconnect();
	$ck = 'ck_' . wc_rand_hash();
	$cs = 'cs_' . wc_rand_hash();
	$ok = $wpdb->insert(
		$wpdb->prefix . 'woocommerce_api_keys',
		array(
			'user_id'         => get_current_user_id(),
			'description'     => 'Braccio Destro (read-only)',
			'permissions'     => 'read',
			'consumer_key'    => wc_api_hash( $ck ),
			'consumer_secret' => $cs,
			'truncated_key'   => substr( $ck, -7 ),
		),
		array( '%d', '%s', '%s', '%s', '%s', '%s' )
	);
	if ( ! $ok ) {
		return __( 'Could not create the WooCommerce read-only key. Please try again.', 'braccio-destro-store-advisor' );
	}
	$key_id = (int) $wpdb->insert_id;
	$res    = ceotrust_post(
		array(
			'connect_code' => $code,
			'platform'     => 'woocommerce',
			'store_url'    => untrailingslashit( home_url() ),
			'key'          => $ck,
			'secret'       => $cs,
			'version'      => defined( 'WC_VERSION' ) ? WC_VERSION : '',
		)
	);
	if ( ! is_array( $res ) || empty( $res['ok'] ) ) {
		$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ), array( '%d' ) );
		return is_array( $res ) && ! empty( $res['error'] ) ? (string) $res['error'] : __( 'Braccio Destro is not responding. Please try again in a few minutes.', 'braccio-destro-store-advisor' );
	}
	// la chiave in chiaro resta solo qui, per poter avvisare Braccio Destro quando si scollega
	update_option( 'ceotrust_conn', array( 'key_id' => $key_id, 'ck' => $ck, 'at' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ), false );
	return true;
}

/** Avvisa Braccio Destro (che cancella i dati letti) e cancella la chiave di lettura. */
function ceotrust_disconnect() {
	global $wpdb;
	$conn = get_option( 'ceotrust_conn' );
	if ( is_array( $conn ) && ! empty( $conn['key_id'] ) ) {
		ceotrust_post(
			array(
				'disconnect_notice' => 1,
				'platform'          => 'woocommerce',
				'store_url'         => untrailingslashit( home_url() ),
				'key'               => (string) $conn['ck'],
			)
		);
		$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => (int) $conn['key_id'] ), array( '%d' ) );
	}
	update_option( 'ceotrust_conn', array(), false );
}

function ceotrust_post( $data ) {
	$r = wp_remote_post(
		CEOTRUST_ENDPOINT,
		array(
			'timeout' => 60,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $data ),
		)
	);
	if ( is_wp_error( $r ) ) {
		return null;
	}
	$j = json_decode( wp_remote_retrieve_body( $r ), true );
	return is_array( $j ) ? $j : null;
}
