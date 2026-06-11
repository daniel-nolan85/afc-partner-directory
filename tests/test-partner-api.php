<?php
class Test_Partner_API extends WP_UnitTestCase {

    private $server;
    private $namespaced_route = '/custom/v1/partners';

    public function set_up() {
        parent::set_up();
        global $wp_rest_server;
        $this->server = $wp_rest_server = new WP_REST_Server;
        do_action( 'rest_api_init' );
    }

    public function tear_down() {
        global $wp_rest_server;
        $wp_rest_server = null;
        parent::tear_down();
    }

    /**
     * Test that the REST route is registered
     */
    public function test_route_exists() {
        $routes = $this->server->get_routes();
        $this->assertArrayHasKey( $this->namespaced_route, $routes );
    }

    /**
     * Test that the endpoint returns a 200 response
     */
    public function test_get_partners_returns_200() {
        $request  = new WP_REST_Request( 'GET', $this->namespaced_route );
        $response = $this->server->dispatch( $request );
        $this->assertEquals( 200, $response->get_status() );
    }

    /**
     * Test that the response contains the expected keys
     */
    public function test_response_structure() {
        $request  = new WP_REST_Request( 'GET', $this->namespaced_route );
        $response = $this->server->dispatch( $request );
        $data     = $response->get_data();

        $this->assertArrayHasKey( 'partners', $data );
        $this->assertArrayHasKey( 'total', $data );
        $this->assertArrayHasKey( 'total_pages', $data );
        $this->assertArrayHasKey( 'page', $data );
        $this->assertArrayHasKey( 'per_page', $data );
    }

    /**
     * Test that a published partner appears in the response
     */
    public function test_published_partner_appears_in_response() {
        $post_id = wp_insert_post( array(
            'post_title'  => 'Test Foundation',
            'post_type'   => 'partner',
            'post_status' => 'publish',
        ) );
        update_post_meta( $post_id, '_afc_website_url', 'https://example.com' );

        // Clear transient cache so fresh query runs
        delete_transient( 'afc_partners_' . md5( serialize( array( '', 20, 1 ) ) ) );

        $request  = new WP_REST_Request( 'GET', $this->namespaced_route );
        $response = $this->server->dispatch( $request );
        $data     = $response->get_data();

        $this->assertEquals( 1, $data['total'] );
        $this->assertEquals( 'Test Foundation', $data['partners'][0]['name'] );
        $this->assertEquals( 'https://example.com', $data['partners'][0]['website_url'] );
    }

    /**
     * Test that draft partners do not appear in the response
     */
    public function test_draft_partner_excluded_from_response() {
        wp_insert_post( array(
            'post_title'  => 'Draft Partner',
            'post_type'   => 'partner',
            'post_status' => 'draft',
        ) );

        delete_transient( 'afc_partners_' . md5( serialize( array( '', 20, 1 ) ) ) );

        $request  = new WP_REST_Request( 'GET', $this->namespaced_route );
        $response = $this->server->dispatch( $request );
        $data     = $response->get_data();

        $this->assertEquals( 0, $data['total'] );
    }

    /**
     * Test category filtering
     */
    public function test_category_filter() {
        $post_id = wp_insert_post( array(
            'post_title'  => 'Education Partner',
            'post_type'   => 'partner',
            'post_status' => 'publish',
        ) );

        $term = wp_insert_term( 'Education', 'partner_category' );
        wp_set_post_terms( $post_id, array( $term['term_id'] ), 'partner_category' );

        wp_insert_post( array(
            'post_title'  => 'Uncategorized Partner',
            'post_type'   => 'partner',
            'post_status' => 'publish',
        ) );

        delete_transient( 'afc_partners_' . md5( serialize( array( 'education', 20, 1 ) ) ) );

        $request = new WP_REST_Request( 'GET', $this->namespaced_route );
        $request->set_param( 'category', 'education' );
        $response = $this->server->dispatch( $request );
        $data     = $response->get_data();

        $this->assertEquals( 1, $data['total'] );
        $this->assertEquals( 'Education Partner', $data['partners'][0]['name'] );
    }

    /**
     * Test that per_page parameter is respected
     */
    public function test_per_page_parameter() {
        for ( $i = 1; $i <= 5; $i++ ) {
            wp_insert_post( array(
                'post_title'  => "Partner $i",
                'post_type'   => 'partner',
                'post_status' => 'publish',
            ) );
        }

        delete_transient( 'afc_partners_' . md5( serialize( array( '', 2, 1 ) ) ) );

        $request = new WP_REST_Request( 'GET', $this->namespaced_route );
        $request->set_param( 'per_page', 2 );
        $response = $this->server->dispatch( $request );
        $data     = $response->get_data();

        $this->assertCount( 2, $data['partners'] );
        $this->assertEquals( 5, $data['total'] );
        $this->assertEquals( 3, $data['total_pages'] );
    }

    /**
     * Test that the create endpoint requires manage_partners capability
     */
    public function test_create_endpoint_requires_permission() {
        $request  = new WP_REST_Request( 'POST', $this->namespaced_route . '/create' );
        $request->set_param( 'name', 'Unauthorized Partner' );
        $response = $this->server->dispatch( $request );

        $this->assertEquals( 403, $response->get_status() );
    }

    /**
     * Test website URL sanitization
     */
    public function test_website_url_is_sanitized() {
        $post_id = wp_insert_post( array(
            'post_title'  => 'Sanitization Test',
            'post_type'   => 'partner',
            'post_status' => 'publish',
        ) );

        // esc_url_raw should strip javascript: protocol
        $dirty_url = 'javascript:alert(1)';
        $clean_url = esc_url_raw( $dirty_url );
        update_post_meta( $post_id, '_afc_website_url', $clean_url );

        $stored = get_post_meta( $post_id, '_afc_website_url', true );
        $this->assertStringNotContainsString( 'javascript', $stored );
    }
}
