<?php

class PR_RelationshipManager {

	protected function get_relationship( $name ) {
		return PR_Configuration::get_instances( $name );
	}

	/**
	 * Get a post ID
	 */
	protected function get_post_id( $post ) {
		if( !empty( $post->ID ) ) {
			return $post->ID;
		} else {
			return (int) $post;
		}
	}

	/**
	 * Get an array of post IDs
	 */
	protected function get_post_ids( $posts ) {
		$ret_posts = array();

		foreach( (array) $posts as $post ) {
			$ret_posts[] = $this->get_post_id( $post );
		}

		return $ret_posts;
	}


	/**
	 * Get a post object
	 */
	protected function get_post_object( $post ) {
		return get_post( $post );
	}


	/**
	 * Get an array of post objects
	 */
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
	 *	Validate if a relationship object is valid
	 */
	protected function validate_relationship( $relationship ) {
		if( empty( $relationship ) ) {
			return new WP_Error( 'invalid relationship', __('No relationship found by that name.'), array( $name, $from, $to ) );
		}
		return true;
	}

	/**
	 *	Validate if a relationship is possible from $from to $to.
	 */
	protected function validate_relationship_connections( $relationship, $from, $to ) {
		if( !in_array( $from->post_type, $relationship->from ) ) {
			return new WP_Error( 'invalid from post-type', __('The from post-type cannot be used for the relationship') );
		}

		foreach( $to as $t ) {
			if( !in_array( $t->post_type, $relationship->to ) ) {
				return new WP_Error( 'invalid from post-type', __('The to post-type cannot be used for the relationship') );
			}
		}
		return true;
	}

	/**
	 * Add relationships to a post.
	 *
	 * @param $name string - the name of the relationship field name
	 * @param $from int|post - the post ID or post object to connect to
	 * @param $to int|post|array - the post IDs or post object to connect
	 */
	function add_relationships( $name, $from, $to ) {
		$from = $this->get_post_object( $from );
		$to = $this->get_post_objects( $to );

		$relationship = $this->get_relationship( $name );

		// Validate connections
		$error = $this->validate_relationship( $relationship );
		if( $error !== true ) {
			return $error;
		}

		$error = $this->validate_relationship_connections( $relationship, $from, $to );
		if( $error !== true ) {
			return $error;
		}

		
		// This will not re-add posts that are already added
		$existing_connections = get_post_meta( $from->ID, $relationship->name, false );

		foreach ( $to as $t ) {
			if( !in_array( $t->ID, $existing_connections ) ) {
				add_post_meta( $from->ID, $relationship->name, $t->ID, false );
			}
		}
	}


	/**
	 * Remove relationships to a post.
	 *
	 * @param $name string - the name of the relationship field name
	 * @param $from int|post - the post ID or post object to connect to
	 * @param $to int|post|array - the post IDs or post object to connect
	 */
	function remove_relationships( $name, $from, $to ) {
		$from = $this->get_post_object( $from );
		$to = $this->get_post_objects( $to );

		$relationship = $this->get_relationship( $name );

		// Validate connections
		$error = $this->validate_relationship( $relationship );
		if( $error !== true ) {
			return $error;
		}

		foreach ( $to as $t ) {
			delete_post_meta( $from->ID, $relationship->name, $t->ID, false );
		}
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
		$from = $this->get_post_object( $from );
		$to = $this->get_post_objects( $to );

		$relationship = $this->get_relationship( $name );

		// Validate connections
		$error = $this->validate_relationship( $relationship );
		if( $error !== true ) {
			return $error;
		}

		$error = $this->validate_relationship_connections( $relationship, $from, $to );
		if( $error !== true ) {
			return $error;
		}

		// Delete all
		delete_post_meta( $from->ID, $relationship->name );

		// Add all
		foreach ( $to as $t ) {
			add_post_meta( $from->ID, $relationship->name, $t->ID, false );
		}
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
	function get_relationships( $name, $return_type='wp_query', $from=null ) {
		$from = $this->get_post_object( $from );

		$relationship = $this->get_relationship( $name );

		// Validate connections
		$error = $this->validate_relationship( $relationship );
		if( $error !== true ) {
			return $error;
		}

		$posts = get_post_meta( $from->ID, $relationship->name, false );
		$post_ids = $this->get_post_ids( $posts );

		$query = new WP_Query( array(
			'post_type' => 'any',
			'post__in' => $post_ids,
			'orderby' => 'post__in',
			'order' => 'ASC',
		));

		if( $return_type=='array' ) {
			$query->set( 'posts_per_page', '-1' );
			return $query->get_posts();
		} else {
			return $query;
		}
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
	function get_reverse_relationships( $name, $return_type='wp_query', $to=null ) {
		$to = $this->get_post_object( $to );

		$relationship = $this->get_relationship( $name );

		// Validate connections
		$error = $this->validate_relationship( $relationship );
		if( $error !== true ) {
			return $error;
		}


		$query = new WP_Query( array(
			'post_type' => 'any',
			'meta_query' => array(
				array(
					'key' => $relationship->name,
					'value' => $to->ID,
					'compare' => '='
				)
			),
		));

		if( $return_type=='array' ) {
			$query->set( 'posts_per_page', '-1' );
			return $query->get_posts();
		} else {
			return $query;
		}
	}
}


global $pr_relationship_manager;
$pr_relationship_manager = new PR_RelationshipManager();