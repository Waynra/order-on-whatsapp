<?php
/**
 * Front-end buttons: product page, shop loop, cart, shortcode and floating chat.
 *
 * @package OrderOnWhatsApp
 */

defined( 'ABSPATH' ) || exit;

/**
 * Renders the WhatsApp buttons and loads their assets.
 */
class OOW_Frontend {

	/**
	 * Hook into WordPress and WooCommerce.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_action( 'wp_footer', array( __CLASS__, 'floating_button' ) );
		add_shortcode( 'order_on_whatsapp', array( __CLASS__, 'shortcode' ) );

		if ( class_exists( 'WooCommerce' ) ) {
			add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'product_button' ) );
			add_action( 'woocommerce_after_shop_loop_item', array( __CLASS__, 'loop_button' ), 20 );
			add_action( 'woocommerce_proceed_to_checkout', array( __CLASS__, 'cart_button' ), 30 );
			add_filter( 'render_block_woocommerce/proceed-to-checkout-block', array( __CLASS__, 'append_cart_button' ) );
		}
	}

	/**
	 * Buttons only make sense once a number is saved.
	 *
	 * @return bool
	 */
	private static function ready() {
		return '' !== OOW_Settings::get( 'phone' );
	}

	/**
	 * Register the stylesheet and script. The script is enqueued only where a button needs it.
	 */
	public static function register_assets() {
		if ( ! self::ready() ) {
			return;
		}

		wp_enqueue_style( 'oow', OOW_URL . 'assets/css/oow.css', array(), OOW_VERSION );
		wp_register_script( 'oow', OOW_URL . 'assets/js/oow.js', array( 'jquery' ), OOW_VERSION, true );

		$config = array(
			'phone'        => OOW_Settings::get( 'phone' ),
			'cartTemplate' => OOW_Settings::get( 'cart_template' ),
			'cartUrl'      => function_exists( 'WC' ) ? rest_url( 'wc/store/v1/cart' ) : '',
			'i18n'         => array(
				'chooseOptions' => __( 'Please choose the product options first.', 'order-on-whatsapp' ),
				'emptyCart'     => __( 'Your cart is empty.', 'order-on-whatsapp' ),
			),
		);

		if ( function_exists( 'get_woocommerce_currency_symbol' ) ) {
			$config['currency'] = array(
				'symbol'   => self::decode( get_woocommerce_currency_symbol() ),
				'format'   => self::decode( get_woocommerce_price_format() ),
				'decimals' => wc_get_price_decimals(),
				'decimal'  => wc_get_price_decimal_separator(),
				'thousand' => wc_get_price_thousand_separator(),
			);
		}

		wp_add_inline_script( 'oow', 'window.oowConfig = ' . wp_json_encode( $config ) . ';', 'before' );
	}

	/**
	 * Button under "Add to cart" on single product pages.
	 */
	public static function product_button() {
		global $product;

		if ( ! self::ready() || ! OOW_Settings::get( 'show_on_product' ) || ! $product instanceof WC_Product ) {
			return;
		}

		wp_enqueue_script( 'oow' );
		echo self::product_link( $product, 'oow-button--product' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in product_link().
	}

	/**
	 * Small button on shop and category pages. Variable products need options, so they are skipped.
	 */
	public static function loop_button() {
		global $product;

		if ( ! self::ready() || ! OOW_Settings::get( 'show_on_shop' ) || ! $product instanceof WC_Product ) {
			return;
		}

		if ( ! $product->is_type( 'simple' ) || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		echo self::product_link( $product, 'oow-button--loop' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in product_link().
	}

	/**
	 * Cart button for the classic [woocommerce_cart] shortcode page.
	 */
	public static function cart_button() {
		echo self::cart_link(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in cart_link().
	}

	/**
	 * Cart button for the block-based Cart page.
	 *
	 * @param string $block_content Rendered "Proceed to checkout" block.
	 * @return string
	 */
	public static function append_cart_button( $block_content ) {
		return $block_content . self::cart_link();
	}

	/**
	 * [order_on_whatsapp] shortcode.
	 *
	 * Inside a product (or with product_id) it sends that product; anywhere else it sends a plain message.
	 *
	 * @param array $atts Shortcode attributes: text, message, product_id.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		if ( ! self::ready() ) {
			return '';
		}

		$atts = shortcode_atts(
			array(
				'text'       => OOW_Settings::get( 'button_text' ),
				'message'    => '',
				'product_id' => 0,
			),
			$atts,
			'order_on_whatsapp'
		);

		$product = null;
		if ( function_exists( 'wc_get_product' ) ) {
			$product = $atts['product_id'] ? wc_get_product( absint( $atts['product_id'] ) ) : ( isset( $GLOBALS['product'] ) ? $GLOBALS['product'] : null );
		}

		// Contributors can place shortcodes, so never reveal drafts or private products.
		if ( $product instanceof WC_Product && 'publish' !== $product->get_status() ) {
			$product = null;
		}

		if ( '' === $atts['message'] && $product instanceof WC_Product ) {
			return self::product_link( $product, 'oow-button--shortcode', $atts['text'] );
		}

		$message = '' !== $atts['message'] ? $atts['message'] : OOW_Settings::get( 'floating_text' );

		return sprintf(
			'<a class="oow-button oow-button--shortcode" href="%1$s" target="_blank" rel="noopener">%2$s<span>%3$s</span></a>',
			self::wa_href( $message ),
			self::icon(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
			esc_html( $atts['text'] )
		);
	}

	/**
	 * Floating chat button on every page.
	 */
	public static function floating_button() {
		if ( ! self::ready() || ! OOW_Settings::get( 'floating' ) ) {
			return;
		}

		printf(
			'<a class="oow-floating oow-floating--%1$s" href="%2$s" target="_blank" rel="noopener" aria-label="%3$s">%4$s</a>',
			esc_attr( OOW_Settings::get( 'floating_position' ) ),
			self::wa_href( OOW_Settings::get( 'floating_text' ) ),
			esc_attr__( 'Chat with us on WhatsApp', 'order-on-whatsapp' ),
			self::icon() // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static SVG.
		);
	}

	/**
	 * Markup for a product button. The href works without JavaScript; the script updates it
	 * when the shopper changes the quantity or picks a variation.
	 *
	 * @param WC_Product $product     Product to order.
	 * @param string     $extra_class Extra CSS class.
	 * @param string     $text        Button text, defaults to the setting.
	 * @return string
	 */
	private static function product_link( WC_Product $product, $extra_class = '', $text = '' ) {
		$template = OOW_Settings::get( 'product_template' );
		$price    = (float) wc_get_price_to_display( $product );
		$values   = array(
			'product'  => $product->get_name(),
			'qty'      => 1,
			'price'    => self::plain_price( $price ),
			'subtotal' => self::plain_price( $price ),
			'sku'      => $product->get_sku(),
			'url'      => $product->get_permalink(),
		);

		$classes = array( 'oow-button', $extra_class );
		if ( $product->is_type( 'variable' ) ) {
			$classes[] = 'is-disabled';
		}

		return sprintf(
			'<a class="%1$s" href="%2$s" target="_blank" rel="noopener" data-oow-product data-template="%3$s" data-name="%4$s" data-price="%5$s" data-sku="%6$s" data-url="%7$s">%8$s<span>%9$s</span></a>',
			esc_attr( implode( ' ', array_filter( $classes ) ) ),
			self::wa_href( self::fill( $template, $values ) ),
			esc_attr( $template ),
			esc_attr( $values['product'] ),
			esc_attr( $price ),
			esc_attr( $values['sku'] ),
			esc_url( $values['url'] ),
			self::icon(),
			esc_html( '' !== $text ? $text : OOW_Settings::get( 'button_text' ) )
		);
	}

	/**
	 * Markup for the cart button. The message is built on click from the Store API so it
	 * always matches the current cart, including changes made without a page reload.
	 *
	 * @return string
	 */
	private static function cart_link() {
		if ( ! self::ready() || ! OOW_Settings::get( 'show_on_cart' ) ) {
			return '';
		}

		wp_enqueue_script( 'oow' );

		return sprintf(
			'<a class="oow-button oow-button--cart" href="%1$s" target="_blank" rel="noopener" data-oow-cart>%2$s<span>%3$s</span></a>',
			self::wa_href( OOW_Settings::get( 'floating_text' ) ),
			self::icon(),
			esc_html( OOW_Settings::get( 'button_text' ) )
		);
	}

	/**
	 * Replace {placeholders} in a template.
	 *
	 * @param string $template Message template.
	 * @param array  $values   Placeholder values keyed without braces.
	 * @return string
	 */
	private static function fill( $template, array $values ) {
		$pairs = array();
		foreach ( $values as $key => $value ) {
			$pairs[ '{' . $key . '}' ] = (string) $value;
		}
		return strtr( $template, $pairs );
	}

	/**
	 * Click-to-chat link for the saved number.
	 *
	 * @param string $message Pre-filled message.
	 * @return string
	 */
	private static function wa_url( $message ) {
		return 'https://wa.me/' . preg_replace( '/\D+/', '', OOW_Settings::get( 'phone' ) ) . '?text=' . rawurlencode( $message );
	}

	/**
	 * Click-to-chat link escaped for an href attribute.
	 *
	 * esc_url() strips encoded line breaks (%0A), which would flatten the message into one line.
	 * The URL is built here from a fixed host, a digits-only number and rawurlencode() output,
	 * so esc_attr() is enough.
	 *
	 * @param string $message Pre-filled message.
	 * @return string
	 */
	private static function wa_href( $message ) {
		return esc_attr( self::wa_url( $message ) );
	}

	/**
	 * Price as plain text in the store's currency format, e.g. "Rp185.000".
	 *
	 * @param float $amount Price.
	 * @return string
	 */
	private static function plain_price( $amount ) {
		return self::decode( wp_strip_all_tags( wc_price( $amount ) ) );
	}

	/**
	 * Decode HTML entities such as &nbsp; or &#8377; into real characters.
	 *
	 * @param string $text Text with entities.
	 * @return string
	 */
	private static function decode( $text ) {
		return html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * WhatsApp logo (Simple Icons, CC0).
	 *
	 * @return string
	 */
	private static function icon() {
		return '<svg class="oow-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/></svg>';
	}
}
