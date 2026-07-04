<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'iconist_auto_convert' );
delete_option( 'iconist_exceptions' );
delete_option( 'iconist_backup_enabled' );
delete_option( 'iconist_bulk_last_run' );
delete_transient( 'iconist_svg_count' );
