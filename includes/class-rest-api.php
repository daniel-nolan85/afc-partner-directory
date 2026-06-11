<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AFC_REST_API {

    private $namespace = 'custom/v1';
    private $route     = '/partners';

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {
        register_rest_route( $this->namespace, $this->route, array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_partners' ),
            'permission_callback' => '__return_true',
            'args'                => array(
                'category' => array(
                    'required'          => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'description'       => 'Filter partners by category slug',
                ),
                'per_page' => array(
                    'required'          => false,
                    'default'           => 20,
                    'sanitize_callback' => 'absint',
                    'description'       => 'Number of partners per page',
                ),
                'page' => array(
                    'required'          => false,
                    'default'           => 1,
                    'sanitize_callback' => 'absint',
                    'description'       => 'Page number',
                ),
            ),
        ) );
    }

    public function get_partners( WP_REST_Request $request ) {
        $category = $request->get_param( 'category' );
        $per_page = min( $request->get_param( 'per_page' ), 100 );
        $page     = $request->get_param( 'page' );

        $cache_key = 'afc_partners_' . md5( serialize( array( $category, $per_page, $page ) ) );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            $response = new WP_REST_Response( $cached, 200 );
            $response->header( 'X-AFC-Cache', 'HIT' );
            return $response;
        }

        $query_args = array(
            'post_type'      => 'partner',
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => $page,
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

        $query    = new WP_Query( $query_args );
        $partners = array();

        foreach ( $query->posts as $post ) {
            $logo_id  = get_post_meta( $post->ID, '_afc_logo_id', true );
            $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : null;

            $categories = wp_get_post_terms( $post->ID, 'partner_category', array( 'fields' => 'names' ) );

            $partners[] = array(
                'id'          => $post->ID,
                'name'        => get_the_title( $post ),
                'website_url' => get_post_meta( $post->ID, '_afc_website_url', true ),
                'logo_url'    => $logo_url,
                'categories'  => ! is_wp_error( $categories ) ? $categories : array(),
            );
        }

        $data = array(
            'partners'    => $partners,
            'total'       => (int) $query->found_posts,
            'total_pages' => (int) $query->max_num_pages,
            'page'        => $page,
            'per_page'    => $per_page,
        );

        set_transient( $cache_key, $data, 5 * MINUTE_IN_SECONDS );

        $response = new WP_REST_Response( $data, 200 );
        $response->header( 'X-AFC-Cache', 'MISS' );
        return $response;
    }
}
