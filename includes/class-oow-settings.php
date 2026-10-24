<?php
/**
 * Settings page under Settings > Order on WhatsApp.
 *
 * @package OrderOnWhatsApp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders the plugin settings with the WordPress Settings API.
 */
class OOW_Settings {

	const OPTION = 'oow_settings';
	const GROUP  = 'oow';
	const PAGE   = 'order-on-whatsapp';

	/**
	 * Hook into WordPress.
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_notices', array( __CLASS__, 'missing_phone_notice' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( OOW_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Default values for every setting.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'phone'             => '',
			'button_text'       => __( 'Order via WhatsApp', 'order-on-whatsapp' ),
			'show_on_product'   => 1,
			'show_on_shop'      => 0,
			'product_template'  => __( "Hello, I'd like to order:\n*{product}*\nQuantity: {qty}\nPrice: {price}\nSubtotal: {subtotal}\n{url}", 'order-on-whatsapp' ),
			'show_on_cart'      => 1,
			'cart_template'     => __( "Hello, I'd like to order these items:\n{items}\n\n*Total: {total}*", 'order-on-whatsapp' ),
			'floating'          => 1,
			'floating_text'     => __( 'Hello, I have a question about your products.', 'order-on-whatsapp' ),
			'floating_position' => 'right',
		);
	}

	/**
	 * Read one setting, or all of them when no key is given.
	 *
	 * @param string|null $key Setting key.
	 * @return mixed
	 */
	public static function get( $key = null ) {
		$options = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );

		if ( null === $key ) {
			return $options;
		}

		return isset( $options[ $key ] ) ? $options[ $key ] : null;
	}

	/**
	 * URL of the settings page.
	 *
	 * @return string
	 */
	public static function page_url() {
		return admin_url( 'options-general.php?page=' . self::PAGE );
	}

	/**
	 * Add the page to the Settings menu.
	 */
	public static function add_page() {
		add_options_page(
			__( 'Order on WhatsApp', 'order-on-whatsapp' ),
			__( 'Order on WhatsApp', 'order-on-whatsapp' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Section and field definitions, grouped by section.
	 *
	 * @return array
	 */
	private static function sections() {
		$placeholders = __( 'Placeholders: %s', 'order-on-whatsapp' );

		return array(
			'oow_general'  => array(
				'title'  => __( 'General', 'order-on-whatsapp' ),
				'fields' => array(
					'phone'       => array(
						'label'       => __( 'WhatsApp number', 'order-on-whatsapp' ),
						'type'        => 'text',
						'placeholder' => '6281234567890',
						'description' => __( 'International format with country code and no "+" or leading 0, e.g. <code>6281234567890</code> (Indonesia) or <code>919876543210</code> (India).', 'order-on-whatsapp' ),
					),
					'button_text' => array(
						'label' => __( 'Button text', 'order-on-whatsapp' ),
						'type'  => 'text',
					),
				),
			),
			'oow_product'  => array(
				'title'  => __( 'Products', 'order-on-whatsapp' ),
				'fields' => array(
					'show_on_product'  => array(
						'label' => __( 'Product page', 'order-on-whatsapp' ),
						'type'  => 'checkbox',
						'text'  => __( 'Show the button under "Add to cart" on single product pages', 'order-on-whatsapp' ),
					),
					'show_on_shop'     => array(
						'label' => __( 'Shop pages', 'order-on-whatsapp' ),
						'type'  => 'checkbox',
						'text'  => __( 'Show the button on shop and category pages (simple products only)', 'order-on-whatsapp' ),
					),
					'product_template' => array(
						'label'       => __( 'Message', 'order-on-whatsapp' ),
						'type'        => 'textarea',
						'description' => sprintf( $placeholders, '<code>{product}</code> <code>{qty}</code> <code>{price}</code> <code>{subtotal}</code> <code>{sku}</code> <code>{url}</code>' ),
					),
				),
			),
			'oow_cart'     => array(
				'title'  => __( 'Cart', 'order-on-whatsapp' ),
				'fields' => array(
					'show_on_cart'  => array(
						'label' => __( 'Cart page', 'order-on-whatsapp' ),
						'type'  => 'checkbox',
						'text'  => __( 'Show a button that sends the whole cart', 'order-on-whatsapp' ),
					),
					'cart_template' => array(
						'label'       => __( 'Message', 'order-on-whatsapp' ),
						'type'        => 'textarea',
						'description' => sprintf( $placeholders, '<code>{items}</code> <code>{count}</code> <code>{total}</code>' ),
					),
				),
			),
			'oow_floating' => array(
				'title'  => __( 'Floating chat button', 'order-on-whatsapp' ),
				'fields' => array(
					'floating'          => array(
						'label' => __( 'Enable', 'order-on-whatsapp' ),
						'type'  => 'checkbox',
						'text'  => __( 'Show a floating WhatsApp button on every page', 'order-on-whatsapp' ),
					),
					'floating_text'     => array(
						'label' => __( 'Message', 'order-on-whatsapp' ),
						'type'  => 'textarea',
					),
					'floating_position' => array(
						'label'   => __( 'Position', 'order-on-whatsapp' ),
						'type'    => 'select',
						'options' => array(
							'right' => __( 'Bottom right', 'order-on-whatsapp' ),
							'left'  => __( 'Bottom left', 'order-on-whatsapp' ),
						),
					),
				),
			),
		);
	}

	/**
	 * Register the option, sections and fields.
	 */
	public static function register() {
		register_setting(
			self::GROUP,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);

		foreach ( self::sections() as $section_id => $section ) {
			add_settings_section( $section_id, $section['title'], '__return_false', self::PAGE );

			foreach ( $section['fields'] as $key => $field ) {
				$args = array(
					'key'   => $key,
					'field' => $field,
				);
				if ( 'checkbox' !== $field['type'] ) {
					$args['label_for'] = 'oow-' . $key;
				}
				add_settings_field( 'oow-' . $key, $field['label'], array( __CLASS__, 'render_field' ), self::PAGE, $section_id, $args );
			}
		}
	}

	/**
	 * Clean user input before it is saved.
	 *
	 * @param mixed $input Raw form values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$defaults = self::defaults();
		$clean    = array();

		$raw_phone      = isset( $input['phone'] ) ? trim( (string) $input['phone'] ) : '';
		$clean['phone'] = preg_replace( '/\D+/', '', $raw_phone );
		$phone_error    = '';

		if ( '' !== $clean['phone'] && '0' === $clean['phone'][0] ) {
			$phone_error = __( 'The WhatsApp number must start with the country code (e.g. 62 or 91), not 0. The previous number was kept.', 'order-on-whatsapp' );
		} elseif ( '' !== $clean['phone'] && ( strlen( $clean['phone'] ) < 8 || strlen( $clean['phone'] ) > 15 ) ) {
			$phone_error = __( 'The WhatsApp number should have 8 to 15 digits including the country code. The previous number was kept.', 'order-on-whatsapp' );
		}

		// A wrong number would break every button, so keep the last good one.
		if ( '' !== $phone_error ) {
			add_settings_error( self::OPTION, 'oow_phone', $phone_error );
			$clean['phone'] = (string) self::get( 'phone' );
		}

		foreach ( array( 'button_text' ) as $key ) {
			$clean[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
		}

		foreach ( array( 'product_template', 'cart_template', 'floating_text' ) as $key ) {
			$value         = isset( $input[ $key ] ) ? sanitize_textarea_field( $input[ $key ] ) : '';
			$clean[ $key ] = str_replace( array( "\r\n", "\r" ), "\n", $value );
		}

		// Empty text fields fall back to the defaults so buttons never render blank.
		foreach ( array( 'button_text', 'product_template', 'cart_template', 'floating_text' ) as $key ) {
			if ( '' === trim( $clean[ $key ] ) ) {
				$clean[ $key ] = $defaults[ $key ];
			}
		}

		foreach ( array( 'show_on_product', 'show_on_shop', 'show_on_cart', 'floating' ) as $key ) {
			$clean[ $key ] = empty( $input[ $key ] ) ? 0 : 1;
		}

		$position                   = isset( $input['floating_position'] ) ? $input['floating_position'] : '';
		$clean['floating_position'] = in_array( $position, array( 'left', 'right' ), true ) ? $position : 'right';

		return $clean;
	}

	/**
	 * Render one settings field.
	 *
	 * @param array $args Field arguments from register().
	 */
	public static function render_field( $args ) {
		$key   = $args['key'];
		$field = $args['field'];
		$value = self::get( $key );
		$name  = self::OPTION . '[' . $key . ']';
		$id    = 'oow-' . $key;

		switch ( $field['type'] ) {
			case 'checkbox':
				printf(
					'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="1" %3$s> %4$s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( 1, (int) $value, false ),
					esc_html( $field['text'] )
				);
				break;

			case 'textarea':
				printf(
					'<textarea id="%1$s" name="%2$s" rows="6" class="large-text code">%3$s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_textarea( $value )
				);
				break;

			case 'select':
				printf( '<select id="%1$s" name="%2$s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['options'] as $option => $label ) {
					printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $option ), selected( $value, $option, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;

			default:
				printf(
					'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" placeholder="%4$s">',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( isset( $field['placeholder'] ) ? $field['placeholder'] : '' )
				);
		}

		if ( ! empty( $field['description'] ) ) {
			printf( '<p class="description">%s</p>', wp_kses( $field['description'], array( 'code' => array() ) ) );
		}
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Remind admins to add a number, since nothing shows on the site without one.
	 */
	public static function missing_phone_notice() {
		if ( ! current_user_can( 'manage_options' ) || '' !== self::get( 'phone' ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'settings_page_' . self::PAGE ), true ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%1$s <a href="%2$s">%3$s</a></p></div>',
			esc_html__( 'Order on WhatsApp is active, but the buttons stay hidden until you add your WhatsApp number.', 'order-on-whatsapp' ),
			esc_url( self::page_url() ),
			esc_html__( 'Add it now', 'order-on-whatsapp' )
		);
	}

	/**
	 * Add a "Settings" link on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public static function action_links( $links ) {
		array_unshift( $links, sprintf( '<a href="%1$s">%2$s</a>', esc_url( self::page_url() ), esc_html__( 'Settings', 'order-on-whatsapp' ) ) );
		return $links;
	}
}
