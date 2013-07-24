<?php

class PR_RelationshipManager {

	public function get_relationship( $name ) {
		return PR_Configuration::get_instances( $name );
	}

	/**
	 * Get a post ID
	 */
	protected function get_post_id( $post ) {
		if( is_numeric( $post ) ) {
			return (int) $post;
			
		} elseif( is_a( $post, 'WP_Post' ) ) {
			return $post->ID;

		} else {
			$post = get_post( $post );
			return $post->ID;
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
			$ret_posts[] = $this->get_post_object( $post );
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

				// Update the reverse direction for quick calculations of the reverse side
				$reverse_relationships = json_decode( get_post_meta( $t->ID, '_reverse__'.$relationship->name, true ) );
				if( !is_array( $reverse_relationships ) ) {
					$reverse_relationships = array();
				}
				if( !in_array( $from->ID, $reverse_relationships ) ) {
					$reverse_relationships[] = $from->ID;
					update_post_meta( $t->ID, '_reverse__'.$relationship->name, json_encode( $reverse_relationships ) );
				}
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

			// Update the reverse direction for quick calculations of the reverse side
			$reverse_relationships = json_decode( get_post_meta( $t->ID, '_reverse__'.$relationship->name, true ) );
			if( !is_array( $reverse_relationships ) ) {
				$reverse_relationships = array();
			}

			$reverse_relationships = array_unique( $reverse_relationships );
			if( ( $key = array_search( $from->ID, $reverse_relationships ) ) !== false ) {
				unset( $reverse_relationships[ $key ] );
				update_post_meta( $t->ID, '_reverse__'.$relationship->name, json_encode( $reverse_relationships ) );
			}
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

		// Determing the ones that will be deleted for the reverse linking
		$previous_tos = get_post_meta( $from->ID, $relationship->name, false );
		if( is_array( $previous_tos ) ) {
			$removed_posts = array_diff( $previous_tos, $this->get_post_ids( $to ) );
		} else {
			$removed_posts = array();
		}

		// Delete all
		delete_post_meta( $from->ID, $relationship->name );

		// Add all
		foreach ( $to as $t ) {
			add_post_meta( $from->ID, $relationship->name, $t->ID, false );

			// Update the reverse direction for quick calculations of the reverse side
			$reverse_relationships = json_decode( get_post_meta( $t->ID, '_reverse__'.$relationship->name, true ) );
			if( !is_array( $reverse_relationships ) ) {
				$reverse_relationships = array();
			}

			if( !in_array( $from->ID, $reverse_relationships ) ) {
				$reverse_relationships[] = $from->ID;
				update_post_meta( $t->ID, '_reverse__'.$relationship->name, json_encode( $reverse_relationships ) );
			}
		}
		
		// Remove the reverse linking from those that were deleted
		foreach ( $removed_posts as $t ) {
			// Update the reverse direction for quick calculations of the reverse side
			$reverse_relationships = json_decode( get_post_meta( $t, '_reverse__'.$relationship->name, true ) );
			if( !is_array( $reverse_relationships ) ) {
				$reverse_relationships = array();
			}

			$reverse_relationships = array_unique( $reverse_relationships );
			if( ( $key = array_search( $from->ID, $reverse_relationships ) ) !== false ) {
				unset( $reverse_relationships[ $key ] );
				update_post_meta( $t, '_reverse__'.$relationship->name, json_encode( $reverse_relationships ) );
			}
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
		$existing_connections = json_decode( get_post_meta( $to->ID, '_reverse__'.$relationship->name, true ) );
		if( !is_array( $existing_connections ) ) {
			$existing_connections = array();
		}


		// Add all
		foreach ( $from as $f ) {
			// Found out if the connection already exists
			$key = array_search( $f->ID, $existing_connections );

			if( $key === false ) {
				// A new connection
				add_post_meta( $f->ID, $relationship->name, $to->ID, false );
			} else {
				unset( $existing_connections[ $key ] ); // Remove it to because the remaining we know to remove
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
	 * @param $from int|post (optional) - the post IDs or post object
	 *
	 * @return array of IDs
	 */
	function get_relationship_ids( $name, $from=null ) {
		$from = $this->get_post_object( $from );

		$relationship = $this->get_relationship( $name );

		// Validate connections
		$error = $this->validate_relationship( $relationship );
		if( $error !== true ) {
			return $error;
		}

		$posts = get_post_meta( $from->ID, $relationship->name, false );

		return (array) $posts;
	}


	/**
	 * Get the posts that belong to the $from post.
	 *
	 * @param $name string - the name of the relationship field name
	 * @param $from int|post (optional) - the post IDs or post object
	 * @param $additiona_args
	 *
	 * @return array of post objects or a WP_Query object 
	 */
	function get_relationships( $name, $from=null, $additional_args=array() ) {
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
			'post_relationship' => array( 'relationship' => $relationship, 'direction' => 'to' ),
		);

	
		$query_args = $this->setup_extra_query_args( $query_args, $additional_args );

		$query = new WP_Query( $query_args );

		return $query;
	}

	/**
	 * Get the post IDs thats the $to post belongs to.
	 *
	 * @param $name string - the name of the relationship field name on the from posts.
	 * @param $to int|post (optional) - the post IDs or post object
	 *
	 * @return array of IDs
	 */
	function get_reverse_relationship_ids( $name, $to=null ) {
		$to = $this->get_post_object( $to );

		$relationship = $this->get_relationship( $name );

		// Validate connections
		$error = $this->validate_relationship( $relationship );
		if( $error !== true ) {
			return $error;
		}

		$posts = json_decode( get_post_meta( $to->ID, '_reverse__'.$relationship->name, true ) );
		if( !is_array( $posts ) ) {
			$posts = array();
		}

		return $posts;
	}



	/**
	 * Get the posts thats the $to post belongs to.
	 *
	 * @param $name string - the name of the relationship field name on the from posts.
	 * @param $to int|post (optional) - the post IDs or post object
	 * @param $additional_args (optional) - additional arguments
	 *
	 * @return array of post objects or a WP_Query object
	 */
	function get_reverse_relationships( $name, $to=null, $additional_args=array() ) {
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
			'post_relationship' => array( 'relationship' => $relationship, 'direction' => 'to'),
		);

		$query_args = $this->setup_extra_query_args( $query_args, $additional_args );

		$query = new WP_Query( $query_args );

		return $query;
	}


	/**
	 * Setup query args
	 */
	function setup_extra_query_args( $query_args, $additional_args ) {

		foreach ($additional_args as $key => $value ) {
			
			switch( $key ) {
				case 'meta_query':
					$query_args['meta_query'] = $query_args['meta_query'] + $additional_args['meta_query'];
					break;

				case 'posts_per_page':
					if( (int) $value > 0 ) {
						$query_args[$key] = $value;
					}
					break;

				default:
					$query_args[$key] = $value;
			}
		}

		return $query_args;
	}
}


global $pr_relationship_manager;
$pr_relationship_manager = new PR_RelationshipManager();