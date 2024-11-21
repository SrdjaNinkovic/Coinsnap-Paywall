<?php
if (!defined('ABSPATH')) {
	exit;
}

class Coinsnap_Paywall_Shortcode_Metabox {
	public function __construct() {
		// Register custom post type
		add_action('init', [$this, 'register_paywall_shortcode_post_type']);

		// Add meta boxes
		add_action('add_meta_boxes', [$this, 'add_paywall_shortcode_metaboxes']);

		// Save meta box data
		add_action('save_post', [$this, 'save_paywall_shortcode_meta'], 10, 2);

		// Add custom columns to admin list
		add_filter('manage_paywall-shortcode_posts_columns', [$this, 'add_custom_columns']);
		add_action('manage_paywall-shortcode_posts_custom_column', [$this, 'populate_custom_columns'], 10, 2);
	}

	public function register_paywall_shortcode_post_type() {
		register_post_type('paywall-shortcode', [
			'labels' => [
				'name'               => 'Paywall Shortcodes',
				'singular_name'      => 'Paywall Shortcode',
				'menu_name'          => 'Paywall Shortcodes',
				'add_new'            => 'Add New',
				'add_new_item'       => 'Add New Paywall Shortcode',
				'edit_item'          => 'Edit Paywall Shortcode',
				'new_item'           => 'New Paywall Shortcode',
				'view_item'          => 'View Paywall Shortcode',
				'search_items'       => 'Search Paywall Shortcodes',
				'not_found'          => 'No paywall shortcodes found',
				'not_found_in_trash' => 'No paywall shortcodes found in Trash',
			],
			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'query_var'          => true,
			'rewrite'            => ['slug' => 'paywall-shortcode'],
			'capability_type'    => 'post',
			'has_archive'        => false,
			'hierarchical'       => false,
			'supports'           => ['title'],
		]);
	}

	public function add_paywall_shortcode_metaboxes() {
		add_meta_box(
			'coinsnap_paywall_shortcode_details',
			'Paywall Shortcode Details',
			[$this, 'render_paywall_shortcode_metabox'],
			'paywall-shortcode',
			'normal',
			'high'
		);
	}

	public function render_paywall_shortcode_metabox($post) {
		// Add nonce for security
		wp_nonce_field('coinsnap_paywall_shortcode_nonce', 'coinsnap_paywall_shortcode_nonce');

		// Retrieve existing meta values
		$description = get_post_meta($post->ID, '_coinsnap_paywall_description', true);
		$button_text = get_post_meta($post->ID, '_coinsnap_paywall_button_text', true);
		$price = get_post_meta($post->ID, '_coinsnap_paywall_price', true);
		$currency = get_post_meta($post->ID, '_coinsnap_paywall_currency', true);
		$duration = get_post_meta($post->ID, '_coinsnap_paywall_duration', true);
		$theme = get_post_meta($post->ID, '_coinsnap_paywall_theme', true);
		?>
      <table class="form-table">
        <tr>
          <th scope="row">
            <label for="coinsnap_paywall_description">Description</label>
          </th>
          <td>
                    <textarea
                        id="coinsnap_paywall_description"
                        name="coinsnap_paywall_description"
                        class="large-text"
                        rows="4"
                    ><?php echo esc_textarea($description); ?></textarea>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="coinsnap_paywall_button_text">Button Text</label>
          </th>
          <td>
            <input
                type="text"
                id="coinsnap_paywall_button_text"
                name="coinsnap_paywall_button_text"
                class="regular-text"
                value="<?php echo esc_attr($button_text ?: 'Pay Now'); ?>"
            >
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="coinsnap_paywall_price">Price</label>
          </th>
          <td>
            <input
                type="number"
                id="coinsnap_paywall_price"
                name="coinsnap_paywall_price"
                class="regular-text"
                step="0.01"
                min="0"
                value="<?php echo esc_attr($price ?: '0'); ?>"
            >
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="coinsnap_paywall_currency">Currency</label>
          </th>
          <td>
            <select
                id="coinsnap_paywall_currency"
                name="coinsnap_paywall_currency"
            >
              <option value="SATS" <?php selected($currency, 'SATS'); ?>>SATS</option>
              <option value="EUR" <?php selected($currency, 'EUR'); ?>>EUR</option>
              <option value="USD" <?php selected($currency, 'USD'); ?>>USD</option>
            </select>
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="coinsnap_paywall_duration">Duration (hours)</label>
          </th>
          <td>
            <input
                type="number"
                id="coinsnap_paywall_duration"
                name="coinsnap_paywall_duration"
                class="regular-text"
                min="1"
                value="<?php echo esc_attr($duration ?: '24'); ?>"
            >
          </td>
        </tr>
        <tr>
          <th scope="row">
            <label for="coinsnap_paywall_theme">Theme</label>
          </th>
          <td>
            <select
                id="coinsnap_paywall_theme"
                name="coinsnap_paywall_theme"
            >
              <option value="light" <?php selected($theme, 'light'); ?>>Light</option>
              <option value="dark" <?php selected($theme, 'dark'); ?>>Dark</option>
            </select>
          </td>
        </tr>
      </table>

      <!-- Shortcode Display -->
      <div class="coinsnap-shortcode-display">
        <h3>Shortcode</h3>
        <input
            type="text"
            class="large-text"
            readonly
            value='[paywall_payment id="<?php echo $post->ID; ?>"]'
        >
        <p class="description">Use this shortcode to display the paywall on any page or post.</p>
      </div>
		<?php
	}

	public function save_paywall_shortcode_meta($post_id, $post) {
		// Check if this is an autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

		// Check nonce for security
		if (!isset($_POST['coinsnap_paywall_shortcode_nonce']) ||
		    !wp_verify_nonce($_POST['coinsnap_paywall_shortcode_nonce'], 'coinsnap_paywall_shortcode_nonce')
		) return;

		// Check user permissions
		if (!current_user_can('edit_post', $post_id)) return;

		// Check post type
		if ($post->post_type !== 'paywall-shortcode') return;

		// Sanitize and save meta fields
		$meta_fields = [
			'description'   => 'coinsnap_paywall_description',
			'button_text'   => 'coinsnap_paywall_button_text',
			'price'         => 'coinsnap_paywall_price',
			'currency'      => 'coinsnap_paywall_currency',
			'duration'      => 'coinsnap_paywall_duration',
			'theme'         => 'coinsnap_paywall_theme'
		];

		foreach ($meta_fields as $key => $field) {
			if (isset($_POST[$field])) {
				$value = match($key) {
					'description' => sanitize_textarea_field($_POST[$field]),
					'button_text' => sanitize_text_field($_POST[$field]),
					'price'       => floatval($_POST[$field]),
					'currency'    => in_array($_POST[$field], ['SATS', 'EUR', 'USD']) ? $_POST[$field] : 'SATS',
					'duration'    => intval($_POST[$field]),
					'theme'       => in_array($_POST[$field], ['light', 'dark']) ? $_POST[$field] : 'light',
					default      => sanitize_text_field($_POST[$field])
				};

				update_post_meta($post_id, '_' . $field, $value);
			}
		}
	}

	public function add_custom_columns($columns) {
		$new_columns = [];
		foreach ($columns as $key => $title) {
			$new_columns[$key] = $title;
			if ($key === 'title') {
				$new_columns['price'] = 'Price';
				$new_columns['currency'] = 'Currency';
				$new_columns['shortcode'] = 'Shortcode';
			}
		}
		return $new_columns;
	}

	public function populate_custom_columns($column, $post_id) {
		switch ($column) {
			case 'price':
				echo esc_html(get_post_meta($post_id, '_coinsnap_paywall_price', true) ?: '0');
				break;
			case 'currency':
				echo esc_html(get_post_meta($post_id, '_coinsnap_paywall_currency', true) ?: 'SATS');
				break;
			case 'shortcode':
				echo '<code>[paywall_payment id="' . esc_attr($post_id) . '"]</code>';
				break;
		}
	}
}

// Initialize the class
new Coinsnap_Paywall_Shortcode_Metabox();
