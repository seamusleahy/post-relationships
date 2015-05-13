# Post Relationships #

Create relationships between posts

# Usage

This plugin allows you to create relationships between posts and custom post types.  It creates a Meta Box with a sortable ui that allows you create the relationship between posts.

### Creating Relationships

In order to create relationships between posts you must use the `pr_register_post_relationship` function to register them.

#### Parameters

*`$name` This can be anything you choose (e.g. `pr_register_post_relationship( 'my_custom_name' array( ) );`)

Create a function in your theme or include it in a separate file

```php
function theme_slug_post_relationships() {
	pr_register_post_relationship( $name, array(
			'from' => array( $post_type_a ), // From this post type
			'to'   => array( $post_type_b ), // To this post type
			'ui'   => array(
				'from' => array(
					'widget'      => 'advance', // Always use advance 
					'label'       => 'Posts Type A', // Label for the meta box
					'description' => __( 'Posts from post type A' ),
				),
				'to' => array(
					'widget'      => 'advance', // Always use advance
					'label'       => 'Post Type B', // Label for meta box
					'description' => __( 'Posts from post type B' )
				),
			)
		) );
}
add_action( 'init', 'theme_slug_post_relationships' );
```

#### Displaying The Relationships

You can use the `pr_get_relationships` function to return a WP_Query object

```php
<ul class="sub-posts">
        <?php
        $sub_query = pr_get_relationships( $name, $post->ID, array(  'posts_per_page' => 2, 'orderby' => 'date', 'order' => 'DESC' ) );

        if( !is_wp_error( $sub_query ) ):
            foreach ( $sub_query->get_posts() as $sub_post ): ?>
                <li>
                    <h2><a href="<?php echo get_permalink( $sub_post->ID ); ?>"><?php echo get_the_title( $sub_post->ID ); ?></a></h2>
                </li>
            <?php
            endforeach;
        endif; ?>
    </ul>
```