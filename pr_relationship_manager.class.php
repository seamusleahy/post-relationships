<?php

class PR_RelationshipManager {

	protected function get_relationship( $name ) {

	}

	protected function get_post_id( $post ) {
		if( !empty( $post->ID ) ) {
			return $post->ID;
		} else {
			return (int) $post;
		}
	}

	protected function get_post_ids( $posts ) {
		$ret_posts = array();

		foreach( (array) $posts as $post ) {
			$ret_posts[] = $this->get_post_id( $post );
		}

		return $ret_posts;
	}


	protected function get_post_object( $post ) {
		return get_post( $post );
	}


	protected function get_post_objects( $posts ) {
		// TODO: optimize with a single DB call to pull posts instead of doing it for each one
		// is_a( $post, 'WP_Post' )
		$ret_posts = array();
		foreach( (array) $posts as $post ) {
			$ret_posts[] = $this->get_post( $post );
		}

		return $ret_posts;
	}

	/**
	 * Add relationships to a post.
	 *
	 * @param $name string - the name of the relationship field name
	 * @param $from int|post - the post ID or post object to connect to
	 * @param $to int|post|array - the post IDs or post object to connect
	 */
	function add_relationships( $name, $from, $to ) {

	}


	/**
	 * Remove relationships to a post.
	 *
	 * @param $name string - the name of the relationship field name
	 * @param $from int|post - the post ID or post object to connect to
	 * @param $to int|post|array - the post IDs or post object to connect
	 */
	function remove_relationships( $name, $from, $to ) {

	}

	/**
	 * Update all the relationships to a post. It will remove all the 
	 * existing relationships and add the $to as relationship.
	 *
	 * @param $name string - the name of the relationship field name
	 * @param $from int|post - the post ID or post object to connect to
	 * @param $to int|post|array - the post IDs or post object to connect
	 */
	function update_relationships( $name, $from, $to ) {

	}


	//
	// Retrieve the relationships
	//


	/**
	 * Get the posts that belong to the $from post.
	 *
	 * @param $name string - the name of the relationship field name
	 * @param $return_type string (optional) - the return value 'array' or 'wp_query'
	 * @param $from int|post (optional) - the post IDs or post object
	 *
	 * @return array of post objects or a WP_Query object 
	 */
	function get_relationships( $name, $return_type='array', $from=null ) {

	}


	/**
	 * Get the posts thats the $to post belongs to.
	 *
	 * @param $name string - the name of the relationship field name on the from posts.
	 * @param $return_type string (optional) - the return value 'array' or 'wp_query'
	 * @param $to int|post (optional) - the post IDs or post object
	 *
	 * @return array of post objects or a WP_Query object
	 */
	function get_reverse_relationships( $name, $return_type='array', $to=null ) {

	}
}


global $pr_relationship_manager;
$pr_relationship_manager = new PR_RelationshipManager();