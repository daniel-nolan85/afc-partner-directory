<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AFC_Meta_Fields {

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
        add_action( 'save_post_partner', array( $this, 'save_meta' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    public function enqueue_admin_scripts( $hook ) {
        global $post;
        if ( ( $hook === 'post-new.php' || $hook === 'post.php' ) && isset( $post ) && $post->post_type === 'partner' ) {
            wp_enqueue_media();
            wp_enqueue_script(
                'afc-admin',
                AFC_PLUGIN_URL . 'assets/js/admin.js',
                array( 'jquery' ),
                AFC_PLUGIN_VERSION,
                true
            );
        }
    }

    public function add_meta_box() {
        add_meta_box(
            'afc_partner_details',
            __( 'Partner Details', 'afc-partner-directory' ),
            array( $this, 'render_meta_box' ),
            'partner',
            'normal',
            'high'
        );
    }

    public function render_meta_box( $post ) {
        wp_nonce_field( 'afc_save_partner_meta', 'afc_partner_nonce' );

        $website_url = get_post_meta( $post->ID, '_afc_website_url', true );
        $logo_id     = get_post_meta( $post->ID, '_afc_logo_id', true );
        $logo_url    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        ?>
        <table class="form-table">
            <tr>
                <th><label for="afc_website_url"><?php esc_html_e( 'Website URL', 'afc-partner-directory' ); ?></label></th>
                <td>
                    <input
                        type="url"
                        id="afc_website_url"
                        name="afc_website_url"
                        value="<?php echo esc_url( $website_url ); ?>"
                        class="regular-text"
                        placeholder="https://example.com"
                    />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e( 'Logo', 'afc-partner-directory' ); ?></label></th>
                <td>
                    <div id="afc-logo-preview">
                        <?php if ( $logo_url ) : ?>
                            <img src="<?php echo esc_url( $logo_url ); ?>" style="max-width:200px;display:block;margin-bottom:8px;" />
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="afc_logo_id" name="afc_logo_id" value="<?php echo esc_attr( $logo_id ); ?>" />
                    <button type="button" class="button" id="afc-upload-logo"><?php esc_html_e( 'Upload / Select Logo', 'afc-partner-directory' ); ?></button>
                    <?php if ( $logo_id ) : ?>
                        <button type="button" class="button" id="afc-remove-logo" style="margin-left:8px;"><?php esc_html_e( 'Remove Logo', 'afc-partner-directory' ); ?></button>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_meta( $post_id ) {
        if ( ! isset( $_POST['afc_partner_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['afc_partner_nonce'] ) ), 'afc_save_partner_meta' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        if ( isset( $_POST['afc_website_url'] ) ) {
            $website_url = esc_url_raw( wp_unslash( $_POST['afc_website_url'] ) );
            update_post_meta( $post_id, '_afc_website_url', $website_url );
        }

        if ( isset( $_POST['afc_logo_id'] ) ) {
            $logo_id = absint( $_POST['afc_logo_id'] );
            if ( $logo_id > 0 ) {
                update_post_meta( $post_id, '_afc_logo_id', $logo_id );
            } else {
                delete_post_meta( $post_id, '_afc_logo_id' );
            }
        }
    }
}
