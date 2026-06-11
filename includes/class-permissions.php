<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AFC_Permissions {

    public function __construct() {
        add_action( 'init', array( $this, 'add_capabilities' ) );
    }

    public static function activate() {
        $admin = get_role( 'administrator' );
        $editor = get_role( 'editor' );

        if ( $admin ) {
            $admin->add_cap( 'manage_partners' );
        }
        if ( $editor ) {
            $editor->add_cap( 'manage_partners' );
        }
    }

    public static function deactivate() {
        $admin = get_role( 'administrator' );
        $editor = get_role( 'editor' );

        if ( $admin ) {
            $admin->remove_cap( 'manage_partners' );
        }
        if ( $editor ) {
            $editor->remove_cap( 'manage_partners' );
        }
    }

    public function add_capabilities() {
        $current_user = wp_get_current_user();
        if ( $current_user->has_cap( 'manage_options' ) && ! $current_user->has_cap( 'manage_partners' ) ) {
            $current_user->add_cap( 'manage_partners' );
        }
    }

    public static function can_manage_partners() {
        return current_user_can( 'manage_partners' );
    }
}
