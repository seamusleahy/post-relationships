<?php
/**
 * Contains the API function for Post Relationships plugin.
 *
 */


//
// Setup
//

/**
 * Register a relationship between post types
 *
 * @param $name string - the name of the relationship field name
 * @param $args array - the various settings
 *
 * $args['from'] (string|array) the post types that the relationship is attached to
 * $args['to'] (string|array) the post types that allowed to be attached
 */
function pr_register_post_relationship( $name, $args ) {
  PR_Configuration::create_instances( $name, $args );
}



//
// Modify the relationships
//

/**
 * Add relationships to a post.
 *
 * @param $name string - the name of the relationship field name
 * @param $from int|post - the post ID or post object to connect to
 * @param $to int|post|array - the post IDs or post object to connect
 */
function pr_add_relationships( $name, $from, $to ) {
  global $pr_relationship_manager;
  return $pr_relationship_manager->add_relationships( $name, $from, $to );
}


/**
 * Remove relationships to a post.
 *
 * @param $name string - the name of the relationship field name
 * @param $from int|post - the post ID or post object to connect to
 * @param $to int|post|array - the post IDs or post object to connect
 */
function pr_remove_relationships( $name, $from, $to ) {
  global $pr_relationship_manager;
  return $pr_relationship_manager->remove_relationships( $name, $from, $to );
}


/**
 * Update all the relationships to a post. It will remove all the 
 * existing relationships and add the $to as relationship.
 *
 * @param $name string - the name of the relationship field name
 * @param $from int|post - the post ID or post object to connect to
 * @param $to int|post|array - the post IDs or post object to connect
 */
function pr_update_relationships( $name, $from, $to ) {
  global $pr_relationship_manager;
  return $pr_relationship_manager->update_relationships( $name, $from, $to );
}




/**
 * Update all the reverse relationships to a post. It will not remove the 
   * existing relationships, if not already related, it will add the $to as relationship.
 *
 * @param $name string - the name of the relationship field name
 * @param $to int|post - the post ID or post object to connect to
 * @param $from int|post|array - the post IDs or post object to connect
 */
function pr_update_reverse_relationships( $name, $to, $from ) {
  global $pr_relationship_manager;
  return $pr_relationship_manager->update_reverse_relationships( $name, $to, $from );
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
function pr_get_relationships( $name, $return_type='wp_query', $from=null ) {
  global $pr_relationship_manager;
  return $pr_relationship_manager->get_relationships( $name, $return_type, $from );
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
function pr_get_reverse_relationships( $name, $return_type='wp_query', $to=null ) {
  global $pr_relationship_manager;
  return $pr_relationship_manager->get_reverse_relationships( $name, $return_type, $to );
}
