<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Coinsnap_Bitcoin_Paywall_Settings {
	private $shortcode_class;

	public function __construct() {
		// Register menus
		add_action( 'admin_menu', [ $this, 'add_menu_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );

		// Instantiate the shortcode class
		$this->shortcode_class = new Coinsnap_Bitcoin_Paywall_Shortcode();
	}

	public function register_settings() {
		register_setting( 'coinsnap_bitcoin_paywall', 'coinsnap_bitcoin_paywall_options', [
			'type'              => 'array',
			'sanitize_callback' => [ $this, 'sanitize_options' ]
		] );

		// Provider Section
		add_settings_section(
			'coinsnap_bitcoin_paywall_provider_section',
			'Provider Settings',
			[ $this, 'provider_section_callback' ],
			'coinsnap_bitcoin_paywall'
		);

		add_settings_field(
			'provider',
			'Payment Provider',
			[ $this, 'render_field' ],
			'coinsnap_bitcoin_paywall',
			'coinsnap_bitcoin_paywall_provider_section',
			[
				'label_for' => 'provider',
				'type'      => 'select',
				'options'   => [
					'coinsnap' => 'Coinsnap',
					'btcpay'   => 'BTCPay'
				]
			]
		);

		// Coinsnap Section
		add_settings_section(
			'coinsnap_bitcoin_paywall_coinsnap_section',
			'Coinsnap Settings',
			[ $this, 'coinsnap_section_callback' ],
			'coinsnap_bitcoin_paywall'
		);

		add_settings_field(
			'coinsnap_store_id',
			'Coinsnap Store ID',
			[ $this, 'render_field' ],
			'coinsnap_bitcoin_paywall',
			'coinsnap_bitcoin_paywall_coinsnap_section',
			[
				'label_for' => 'coinsnap_store_id',
				'type'      => 'text'
			]
		);

		add_settings_field(
			'coinsnap_api_key',
			'Coinsnap API Key',
			[ $this, 'render_field' ],
			'coinsnap_bitcoin_paywall',
			'coinsnap_bitcoin_paywall_coinsnap_section',
			[
				'label_for' => 'coinsnap_api_key',
				'type'      => 'text'
			]
		);

		// BTCPay Section
		add_settings_section(
			'coinsnap_bitcoin_paywall_btcpay_section',
			'BTCPay Settings',
			[ $this, 'btcpay_section_callback' ],
			'coinsnap_bitcoin_paywall'
		);

		add_settings_field(
			'btcpay_store_id',
			'BTCPay Store ID',
			[ $this, 'render_field' ],
			'coinsnap_bitcoin_paywall',
			'coinsnap_bitcoin_paywall_btcpay_section',
			[
				'label_for' => 'btcpay_store_id',
				'type'      => 'text'
			]
		);

		add_settings_field(
			'btcpay_api_key',
			'BTCPay API Key',
			[ $this, 'render_field' ],
			'coinsnap_bitcoin_paywall',
			'coinsnap_bitcoin_paywall_btcpay_section',
			[
				'label_for' => 'btcpay_api_key',
				'type'      => 'text'
			]
		);

		add_settings_field(
			'btcpay_url',
			'BTCPay URL', [
			$this,
			'render_field'
		], 'coinsnap_bitcoin_paywall', 'coinsnap_bitcoin_paywall_btcpay_section', [
				'label_for' => 'btcpay_url',
				'type'      => 'text'
			]
		);
	}

	public function render_field( $args ) {
		$options     = get_option( 'coinsnap_bitcoin_paywall_options', [] );
		$field_id    = $args['label_for'];
		$field_type  = $args['type'];
		$field_value = isset( $options[ $field_id ] ) ? $options[ $field_id ] : '';

		switch ( $field_type ) {
			case 'select':
				echo '<select 
                id="' . esc_attr( $field_id ) . '" 
                name="coinsnap_bitcoin_paywall_options[' . esc_attr( $field_id ) . ']"
                class="regular-text">';
				foreach ( $args['options'] as $value => $label ) {
					echo '<option value="' . esc_attr( $value ) . '"' .
					     selected( $field_value, $value, false ) . '>' .
					     esc_html( $label ) . '</option>';
				}
				echo '</select>';
				break;

			case 'text':
				echo '<input type="text" 
                id="' . esc_attr( $field_id ) . '" 
                name="coinsnap_bitcoin_paywall_options[' . esc_attr( $field_id ) . ']" 
                value="' . esc_attr( $field_value ) . '" 
                class="regular-text"' .
				     ( isset( $args['readonly'] ) && $args['readonly'] ? ' readonly' : '' ) .
				     ( isset( $args['value'] ) ? ' value="' . esc_attr( $args['value'] ) . '"' : '' ) .
				     '>';
				break;
		}

		if ( isset( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	public function sanitize_options( $options ) {
		$sanitized = [];

		if ( isset( $options['provider'] ) ) {
			$sanitized['provider'] = sanitize_text_field( $options['provider'] );
		}

		if ( isset( $options['coinsnap_store_id'] ) ) {
			$sanitized['coinsnap_store_id'] = sanitize_text_field( $options['coinsnap_store_id'] );
		}

		if ( isset( $options['coinsnap_api_key'] ) ) {
			$sanitized['coinsnap_api_key'] = sanitize_text_field( $options['coinsnap_api_key'] );
		}

		if ( isset( $options['coinsnap_url'] ) ) {
			$sanitized['coinsnap_url'] = esc_url_raw( $options['coinsnap_url'] );
		}

		if ( isset( $options['btcpay_store_id'] ) ) {
			$sanitized['btcpay_store_id'] = sanitize_text_field( $options['btcpay_store_id'] );
		}

		if ( isset( $options['btcpay_api_key'] ) ) {
			$sanitized['btcpay_api_key'] = sanitize_text_field( $options['btcpay_api_key'] );
		}

		if ( isset( $options['btcpay_url'] ) ) {
			$sanitized['btcpay_url'] = esc_url_raw( $options['btcpay_url'] );
		}

		return $sanitized;
	}

	// Optional section callbacks for additional descriptions
	public function provider_section_callback() {
		echo esc_html_e( 'Select your preferred payment provider and configure its settings below.', 'coinsnap-bitcoin-paywall' );
	}

	public function coinsnap_section_callback() {
		echo esc_html_e( 'Enter your Coinsnap credentials here if you selected Coinsnap as your payment provider.', 'coinsnap-bitcoin-paywall' );
	}

	public function btcpay_section_callback() {
		echo esc_html_e( 'Enter your BTCPay credentials here if you selected BTCPay as your payment provider.', 'coinsnap-bitcoin-paywall' );
	}

	public function add_menu_page() {
		// Add a top-level menu page for Coinsnap Bitcoin Paywall
		add_menu_page(
			'Coinsnap Bitcoin Paywall',
			'Coinsnap Bitcoin Paywall',
			'manage_options',
			'coinsnap_bitcoin_paywall',
			[ $this, 'settings_page_html' ],
			'dashicons-lock',
			100
		);

		// Add the Paywall Shortcodes submenu
		add_submenu_page(
			'coinsnap_bitcoin_paywall', // Parent slug
			'Paywall Shortcodes', // Page title
			'Paywall Shortcodes', // Menu title
			'manage_options', // Capability
			'edit.php?post_type=paywall-shortcode' // Submenu slug
		);
	}

	/**
	 * Renders a specific settings section manually.
	 *
	 * @param string $section_id The ID of the section to render.
	 */
	private function render_section( $section_id ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections['coinsnap_bitcoin_paywall'][ $section_id ] ) ) {
			return;
		}

		$section = $wp_settings_sections['coinsnap_bitcoin_paywall'][ $section_id ];

		if ( ! empty( $section['title'] ) ) {
			echo '<h3>' . esc_html( $section['title'] ) . '</h3>';
		}

		if ( ! empty( $section['callback'] ) ) {
			call_user_func( $section['callback'], $section );
		}

		if ( ! empty( $wp_settings_fields['coinsnap_bitcoin_paywall'][ $section_id ] ) ) {
			echo '<table class="form-table">';
			do_settings_fields( 'coinsnap_bitcoin_paywall', $section_id );
			echo '</table>';
		}
	}

	public function settings_page_html() {
		?>
      <div class="wrap">
        <h1><?php esc_html_e( 'Coinsnap Bitcoin Paywall Settings', 'coinsnap-bitcoin-paywall' ); ?></h1>
        <form method="post" action="options.php">
			<?php
			// Render settings fields
			settings_fields( 'coinsnap_bitcoin_paywall' );

			// Render sections
			$this->render_section( 'coinsnap_bitcoin_paywall_provider_section' );

			// Render Coinsnap settings
			echo '<div id="coinsnap-settings-wrapper" class="provider-settings">';
			$this->render_section( 'coinsnap_bitcoin_paywall_coinsnap_section' );
			$this->render_connection_test_button( 'coinsnap' );
			echo '</div>';

			// Render BTCPay settings
			echo '<div id="btcpay-settings-wrapper" class="provider-settings">';
			$this->render_section( 'coinsnap_bitcoin_paywall_btcpay_section' );
			$this->render_connection_test_button( 'btcpay' );
			echo '</div>';

			// Render submit button
			submit_button();
			?>
        </form>
      </div>
		<?php
	}

	/**
	 * Render a connection test button for a specific provider.
	 *
	 * @param string $provider The provider name (e.g., 'coinsnap', 'btcpay').
	 */
	private function render_connection_test_button( $provider ) {
		$url = add_query_arg(
			[
				'page' => 'coinsnap_bitcoin_paywall',
				'coinsnap_test_connection' => 1,
				'provider' => $provider,
			],
			admin_url( 'admin.php' )
		);

		?>
      <div class="coinsnap-connection-test">
        <a href="<?php echo esc_url( $url ); ?>" class="button button-secondary">
			<?php
			printf(
			/* translators: %s: Provider name */
				esc_html__( 'Test %s Connection', 'coinsnap-bitcoin-paywall' ),
				ucfirst( $provider )
			);
			?>
        </a>
      </div>
		<?php
	}
}

new Coinsnap_Bitcoin_Paywall_Settings();
