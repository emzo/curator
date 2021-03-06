<?php

class CURTestSingleSite extends CUR_Test_Base {

	/**
	 * Setup each test.
	 *
	 * @since 0.2.1
	 */
	public function setUp() {
		global $wpdb;
		parent::setUp();
		$wpdb->suppress_errors();

		$this->setup_test_post_type();
	}

	/**
	 * Clean up after each test. Reset our mocks
	 *
	 * @since 0.2.1
	 */
	public function tearDown() {
		parent::tearDown();

		// @todo delete all posts, curated post, etc
		$this->fired_actions = array();
	}

	/**
	 * Test a post curation
	 *
	 * @since 0.2.1
	 */
	public function testPostCuration() {
		$post_id = cur_create_post();

		$post = get_post( $post_id );

		// Ensure we're able to create a post
		$this->assertTrue( ! empty( $post_id ) );
		$this->assertTrue( null !== $post );

		// Curate the post
		$curated_post = cur_curate_post( $post_id, $post );

		// We should get an int value back of the curated post ID if we were successful
		$this->assertTrue( false !== $curated_post && is_int( $curated_post ));

		// Check to ensure that the posts meta is stored
		$this->assertEquals( $curated_post, get_post_meta( $post_id, '_curator_related_id', true ) );
		$this->assertEquals( $post_id, get_post_meta( $curated_post, '_curator_related_id', true ) );

		// Check that the posts know they're related to each other
		$this->assertEquals( $post_id, cur_get_related_id( $curated_post ) );
		$this->assertEquals( $curated_post, cur_get_related_id( $post_id ) );

		// Test term association
		$curate_term = cur_get_module_term( 'curator' );
		$associated_terms = wp_list_pluck( wp_get_object_terms( $curated_post, cur_get_tax_slug() ), 'slug', 'term_id' );
		$this->assertTrue( in_array( $curate_term, $associated_terms ) );
	}

	/**
	 * Test a post uncuration
	 *
	 * @since 0.2.1
	 */
	public function testPostUncuration() {
		$post_id = cur_create_post();

		// Curate the post
		$curated_post = cur_curate_post( $post_id, get_post( $post_id ) );

		// Ensure we've actually curated it
		$this->assertEquals( $post_id, cur_get_related_id( $curated_post ) );
		$this->assertEquals( $curated_post, cur_get_related_id( $post_id ) );

		// Uncurate post
		$uncurate_item_response = cur_uncurate_item( $post_id );
		$this->assertEquals( 'stdClass', get_class( $uncurate_item_response ) );

		$this->assertNotEquals( false, $uncurate_item_response );
	}

	/**
	 * Test a post uncuration by deleting a curated post
	 *
	 * @since 0.2.1
	 */
	public function testDeleteCuratePost() {
		$post_id = cur_create_post();

		// Curate the post
		$curated_post = cur_curate_post( $post_id, get_post( $post_id ) );

		// Ensure we've actually curated it
		$this->assertEquals( $post_id, cur_get_related_id( $curated_post ) );
		$this->assertEquals( $curated_post, cur_get_related_id( $post_id ) );

		// Uncurate post by deleting the curated item
		wp_trash_post( $curated_post );

		$this->assertEquals( false, cur_get_curated_post( $post_id ) );

		// Ensure that the curated post is completely gone
		$this->assertEquals( null, get_post( $curated_post ) );
	}

	/**
	 * Test post uncuration by unpublishing an original post
	 *
	 * @since 0.2.1
	 */
	public function testUnpublishOriginalPost() {
		$post_id = cur_create_post();

		// Curate the post
		$curated_post = cur_curate_post( $post_id, get_post( $post_id ) );

		// Ensure that we've actually curated it
		$this->assertEquals( $post_id, cur_get_related_id( $curated_post ) );
		$this->assertEquals( $curated_post, cur_get_related_id( $post_id ) );

		// Uncurate post by unpublishing the original item
		$post = get_post( $post_id, ARRAY_A );
		$post['post_status'] = 'draft';
		wp_insert_post( $post );

		// Ensure that the post status has been updated to a draft
		$this->assertEquals( 'draft', get_post_status( $post_id ) );

		// Ensure that there is no longer a related curated post
		$this->assertEquals( false, cur_get_curated_post( $post_id ) );

		// Ensure the curated post is completely gone
		$this->assertEquals( null, get_post( $curated_post ) );
	}

	public function testFeaturePost() {
		$post_id = cur_create_post();

		// Curate the post
		$curated_post = cur_curate_post( $post_id, get_post( $post_id ) );

		// Feature the post
		cur_set_item_modules( array( 'featurer' => 'add' ), $curated_post );

		// Check to see if the item is featured
		$featurer_term = get_term_by( 'slug', 'cur-featured-item', 'cur-tax-curator' );

		// Get terms associated with curated post
		$associated_terms = wp_list_pluck( wp_get_object_terms( $curated_post, cur_get_tax_slug() ), 'slug', 'term_id' );

		$this->assertTrue( ! empty( $associated_terms[ $featurer_term->term_id ] ) );

		// Test the cur_is_featured module - should also report true
		$this->assertEquals( true, cur_is_featured( $post_id ) );
	}

	public function testNonFeaturedPost() {
		$post_id = cur_create_post();

		// Curate the post
		$curated_post = cur_curate_post( $post_id, get_post( $post_id ) );

		// Test that the item is not featured
		$this->assertEquals( false, cur_is_featured( $post_id ) );
	}
}