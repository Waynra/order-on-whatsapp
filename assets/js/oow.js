/**
 * Order on WhatsApp: keeps button links in sync with quantity, variations and the cart.
 */
( function ( $ ) {
	'use strict';

	var config = window.oowConfig || {};

	function waUrl( message ) {
		return 'https://wa.me/' + config.phone + '?text=' + encodeURIComponent( message );
	}

	function fill( template, values ) {
		return template.replace( /\{(\w+)\}/g, function ( match, key ) {
			return Object.prototype.hasOwnProperty.call( values, key ) ? String( values[ key ] ) : match;
		} );
	}

	// Mirrors wc_price() so JS and PHP produce the same text.
	function formatPrice( amount ) {
		var c = config.currency;
		if ( ! c ) {
			return String( amount );
		}
		var parts = Math.abs( amount ).toFixed( c.decimals ).split( '.' );
		parts[ 0 ] = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, c.thousand );
		return c.format.replace( '%1$s', c.symbol ).replace( '%2$s', parts.join( c.decimal ) );
	}

	/* Product buttons ------------------------------------------------------ */

	function variationLabel( form ) {
		var labels = [];
		$( form ).find( '.variations select' ).each( function () {
			if ( ! this.value ) {
				return;
			}
			var label = $( form ).find( 'label[for="' + this.id + '"]' ).first().text().trim();
			var option = this.options[ this.selectedIndex ].text.trim();
			labels.push( label ? label + ': ' + option : option );
		} );
		return labels.join( ', ' );
	}

	function updateProductButton( button ) {
		var data = button.dataset;
		var form = button.closest( 'form.cart' );
		var qtyInput = form ? form.querySelector( 'input.qty' ) : null;
		var qty = qtyInput ? Math.max( 1, parseInt( qtyInput.value, 10 ) || 1 ) : 1;
		var price = parseFloat( data.variationPrice || data.price ) || 0;
		var name = data.name + ( data.variationLabel ? ' (' + data.variationLabel + ')' : '' );

		button.href = waUrl(
			fill( data.template, {
				product: name,
				qty: qty,
				price: formatPrice( price ),
				subtotal: formatPrice( price * qty ),
				sku: data.variationSku || data.sku,
				url: data.url,
			} )
		);
	}

	$( function () {
		$( '[data-oow-product]' ).each( function () {
			var button = this;
			var form = button.closest( 'form.cart' );

			if ( ! form ) {
				return;
			}

			$( form ).on( 'input change', 'input.qty', function () {
				updateProductButton( button );
			} );

			// WooCommerce fires these jQuery events on variable product forms.
			$( form )
				.on( 'found_variation', function ( event, variation ) {
					button.dataset.variationPrice = variation.display_price;
					button.dataset.variationSku = variation.sku || '';
					button.dataset.variationLabel = variationLabel( form );
					button.classList.remove( 'is-disabled' );
					updateProductButton( button );
				} )
				.on( 'reset_data', function () {
					delete button.dataset.variationPrice;
					delete button.dataset.variationSku;
					delete button.dataset.variationLabel;
					button.classList.add( 'is-disabled' );
					updateProductButton( button );
				} );
		} );
	} );

	$( document ).on( 'click', '[data-oow-product].is-disabled', function ( event ) {
		event.preventDefault();
		var params = window.wc_add_to_cart_variation_params;
		window.alert( params && params.i18n_make_a_selection_text ? params.i18n_make_a_selection_text : config.i18n.chooseOptions );
	} );

	/* Cart button ---------------------------------------------------------- */

	function minorToAmount( value, minorUnit ) {
		return parseInt( value, 10 ) / Math.pow( 10, minorUnit );
	}

	function cartMessage( cart ) {
		var lines = cart.items.map( function ( item ) {
			var options = ( item.variation || [] )
				.map( function ( v ) {
					return v.attribute + ': ' + v.value;
				} )
				.join( ', ' );
			var unit = minorToAmount( item.prices.price, item.prices.currency_minor_unit );
			return '- ' + item.name + ( options ? ' (' + options + ')' : '' ) + ' x ' + item.quantity + ' = ' + formatPrice( unit * item.quantity );
		} );

		return fill( config.cartTemplate, {
			items: lines.join( '\n' ),
			count: cart.items_count,
			total: formatPrice( minorToAmount( cart.totals.total_price, cart.totals.currency_minor_unit ) ),
		} );
	}

	$( document ).on( 'click', '[data-oow-cart]', function ( event ) {
		if ( ! config.cartUrl || ! window.fetch ) {
			return; // Fall back to the plain href.
		}
		event.preventDefault();

		var fallback = this.href;
		// Open the tab now, inside the click, so pop-up blockers allow it.
		var win = window.open( '', '_blank' );

		function go( url ) {
			if ( win ) {
				win.opener = null;
				win.location.href = url;
			} else {
				window.location.href = url;
			}
		}

		fetch( config.cartUrl, { credentials: 'same-origin', headers: { Accept: 'application/json' } } )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'Store API ' + response.status );
				}
				return response.json();
			} )
			.then( function ( cart ) {
				if ( ! cart.items || ! cart.items.length ) {
					if ( win ) {
						win.close();
					}
					window.alert( config.i18n.emptyCart );
					return;
				}
				go( waUrl( cartMessage( cart ) ) );
			} )
			.catch( function () {
				go( fallback );
			} );
	} );
} )( jQuery );
