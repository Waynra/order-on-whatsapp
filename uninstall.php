<?php
/**
 * Remove plugin data when the plugin is deleted from the Plugins screen.
 *
 * @package OrderOnWhatsApp
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'oow_settings' );
