<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AFC_Block {

    public function __construct() {
        add_action( 'init', array( $this, 'register_block' ) );
    }

    public function register_block() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        wp_register_script(
            'afc-partner-directory-block',
            AFC_PLUGIN_URL . 'blocks/partner-directory/index.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-api-fetch' ),
            AFC_PLUGIN_VERSION,
            true
        );

        wp_register_style(
            'afc-partner-directory-style',
            AFC_PLUGIN_URL . 'blocks/partner-directory/style.css',
            array(),
            AFC_PLUGIN_VERSION
        );

        register_block_type( 'afc/partner-directory', array(
            'editor_script'   => 'afc-partner-directory-block',
            'style'           => 'afc-partner-directory-style',
            'render_callback' => array( $this, 'render_block' ),
            'attributes'      => array(
                'category' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'columns' => array(
                    'type'    => 'number',
                    'default' => 3,
                ),
            ),
        ) );
    }

    public function render_block( $attributes ) {
        $category = isset( $attributes['category'] ) ? sanitize_text_field( $attributes['category'] ) : '';
        $columns  = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;

        $query_args = array(
            'post_type'      => 'partner',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        if ( ! empty( $category ) ) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'partner_category',
                    'field'    => 'slug',
                    'terms'    => $category,
                ),
            );
        }

        $query = new WP_Query( $query_args );

        if ( ! $query->have_posts() ) {
            return '<p class="afc-no-partners">' . esc_html__( 'No partners found.', 'afc-partner-directory' ) . '</p>';
        }

        ob_start();
        ?>
        <div class="afc-partner-directory" style="--afc-columns: <?php echo esc_attr( $columns ); ?>">
            <?php foreach ( $query->posts as $post ) :
                $logo_id     = get_post_meta( $post->ID, '_afc_logo_id', true );
                $logo_url    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
                $website_url = get_post_meta( $post->ID, '_afc_website_url', true );
                $name        = get_the_title( $post );
            ?>
                <div class="afc-partner-card">
                    <?php if ( $website_url ) : ?>
                        <a href="<?php echo esc_url( $website_url ); ?>" target="_blank" rel="noopener noreferrer" class="afc-partner-link">
                    <?php endif; ?>

                    <?php if ( $logo_url ) : ?>
                        <div class="afc-partner-logo">
                            <img src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $name ); ?> logo" />
                        </div>
                    <?php endif; ?>

                    <div class="afc-partner-name"><?php echo esc_html( $name ); ?></div>

                    <?php if ( $website_url ) : ?>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }
}
