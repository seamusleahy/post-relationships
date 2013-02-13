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
	 * Get a post ID
	 */
	protected function get_post( $post ) {
		return get_post( $post );
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
	 *
	 * @param $relationship Relationship object
	 * @param $from WP_Post|array - the from posts
	 * @param $to WP_Post|array - the to posts
	 *
	 * @return boolean
	 */
	protected function validate_relationship_connections( $relationship, $from, $to ) {
		if( !is_array( $from ) ) {
			$from = array( $from );
		}
		foreach( $from as $f ) {
			if( !in_array( $f->post_type, $relationship->from ) ) {
				return new WP_Error( 'invalid from post-type', __('The from post-type cannot be used for the relationship') );
			}
		}

		if( !is_array( $to ) ) {
			$to = array( $to );
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


	/**
	 * Update all the reverse relationships to a post. It will not remove the 
	 * existing relationships, if not already related, it will add the $to as relationship.
	 *
	 * @param $name string - the name of the relationship field name
	 * @param $to int|post - the post ID or post object to connect to
	 * @param $from int|post|array - the post IDs or post object to connect
	 */
	function update_reverse_relationships( $name, $to, $from ) {
		$from = $this->get_post_objects( $from );
		$to = $this->get_post_object( $to );

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

		// Get the existing posts
		$existing_connections = $this->get_reverse_relationships( $name, 'array', $to, array( 'fields' => 'ids' ) );

		// Add all
		foreach ( $from as $f ) {
			// Found out if the connection already exists
			$key = array_search( $f->ID, $existing_connections );

			if( $key === false ) {
				// A new connection
				add_post_meta( $f->ID, $relationship->name, $to->ID, false );
			} else {
				unset( $existing_connections[ $key ] ); // Remove it to know which ones to remove
			}
		}

		// Delete existing connections that are not kept
		foreach( $existing_connections as $id ) {
			delete_post_meta( $id, $relationship->name, $to->ID );
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
	function get_relationships( $name, $return_type='wp_query', $from=null, $additional_args=array() ) {
		$from = $this->get_post_object( $from );

		$relationship = $this->get_relationship( $name );

		// Validate connections
		$error = $this->validate_relationship( $relationship );
		if( $error !== true ) {
			return $error;
		}

		$posts = get_post_meta( $from->ID, $relationship->name, false );
		$post_ids = $this->get_post_ids( $posts );

		// If $post_ids is empty, then WP_Query will ignore post__in. Force it.
		if( empty($post_ids) ) {
			$post_ids = array( 0 );
		}


		$query_args = array(
			'post_type' => 'any',
			'post__in' => $post_ids,
			'orderby' => 'post__in',
			'order' => 'ASC',
		);

		if( $return_type=='array' ) {
			$query_args['posts_per_page'] = '-1';
		}

		// Add additional arguments
		if( !empty( $additional_args['fields'] ) ) {
			$query_args['fields'] = $additional_args['fields'];
		}

		$query = new WP_Query( $query_args );

		if( $return_type=='array' ) {
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
	 * @param $additional_args (optional) - additional arguments
	 *
	 * @return array of post objects or a WP_Query object
	 */
	function get_reverse_relationships( $name, $return_type='wp_query', $to=null, $additional_args=array() ) {
		$to = $this->get_post_object( $to );

		$relationship = $this->get_relationship( $name );

		// Validate connections
		$error = $this->validate_relationship( $relationship );
		if( $error !== true ) {
			return $error;
		}

		// Build the query parameters
		$query_args = array(
			'post_type' => 'any',
			'meta_query' => array(
				array(
					'key' => $relationship->name,
					'value' => $to->ID,
					'compare' => '='
				)
			),
		);

		if( $return_type=='array' ) {
			$query_args['posts_per_page'] = '-1';
		}

		// Add additional arguments
		if( !empty( $additional_args['fields'] ) ) {
			$query_args['fields'] = $additional_args['fields'];
		}

		$query = new WP_Query( $query_args );

		if( $return_type=='array' ) {
			return $query->get_posts();
		} else {
			return $query;
		}
	}
}


global $pr_relationship_manager;
$pr_relationship_manager = new PR_RelationshipManager();