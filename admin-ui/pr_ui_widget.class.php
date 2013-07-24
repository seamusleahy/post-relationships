<?php

/**
 * Base class for creating post relationship admin UI widgets.
 *
 * When you extend, set a class constant of arg_type with the name of your widget type
 * to be used in the registeration process.
 * Call the static method register_widget().
 */
class PR_UI_Widget {

	static private $registered_classes = array();

	/**
	 * Call this by the extended classes.
	 */
	static function register_widget() {
		$class = get_called_class();
		self::$registered_classes[ $class::arg_type ] = $class;
	}


	/**
	 * Initialized the hooks that are used for registering the admin UI widgets
	 */
	static function initialize_hooks() {
		add_filter( 'pr_widget_classes', array( get_class(), 'pr_widget_classes') );
	}


	/**
	 * Add all the registered widget classes
	 */
	static function pr_widget_classes( $types ) {
		return $types + self::$registered_classes;
	}


	/**
	 * Constructor
	 *
	 * @param $relationship PR_Relationship_Manager
	 * @param $direction string - 'to' or 'from'
	 * @param $post WP_Post - the current post
	 * @param $ui array - the arguments for the widget
	 */
	function __construct( $relationship, $direction, $post, $ui_args ) {
		$this->relationship = $relationship;
		$this->direction = $direction;
		$this->post = $post;
		$this->ui_args = $this->ui_args_filter( $ui_args );

		$this->nonce_name = $relationship->name . '-nonce';

		$this->_field_name = $relationship->name . '-' . $direction;
		$this->field_name =	$this->_field_name . '[]';
	}


	/**
	 * Setup the UI args for this widget
	 */
	function ui_args_filter( $args ) {
		return $args;
	}


	/**
	 * Render the output of the widget
	 */
	function render() {
		if( $this->direction == 'from' ) {
			$this->current_values = pr_get_relationships( $this->relationship->name, $this->post );
			$this->current_value_ids = pr_get_relationship_ids( $this->relationship->name, $this->post );

		} elseif ( $this->direction == 'to' ) {
			$this->current_values = pr_get_reverse_relationships( $this->relationship->name, $this->post );
			$this->current_value_ids = pr_get_reverse_relationship_ids( $this->relationship->name, $this->post );
		}
		wp_nonce_field( $this->relationship->name, $this->nonce_name );

		$this->render_field();

		// Display the description
		if( !empty( $this->ui_args['description'] ) ) {
			echo '<p>', $this->ui_args['description'], '</p>';
		}
	}


	/**
	 * Extend to render the widget
	 */
	function render_field() { }


	/**
	 * Save the post data
	 */
	function save_post( $post ) {
		// Bail if we're doing an auto save
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// if our nonce isn't there, or we can't verify it, bail
		if( !isset( $_POST[ $this->nonce_name ] ) || !wp_verify_nonce( $_POST[ $this->nonce_name ], $this->relationship->name ) ) {
			return;
		}
		
		// if our current user can't edit this post, bail
		$permission = 'edit_post';
		$permission = apply_filters( 'pr_ui_widget_permission', $permission, $this );
		if( !current_user_can( $permission, $post->ID ) ) {
			return;
		}

		$value = $this->save_post_get_value( $post );

		// Remove empty items
		foreach ( (array) $value as $k => $v ) {
			if ( empty($v) ) {
				unset($value[$k]);
			}
		}

		// Now we have a IDs
		if( $this->direction == 'from' ) {
			pr_update_relationships( $this->relationship->name, $post, $value );
		} elseif( $this->direction == 'to' ) {
			pr_update_reverse_relationships( $this->relationship->name, $post, $value );
		}
	}


	/**
	 * Called to enqueue stylesheets and javascripts
	 *
	 * @param $post
	 */
	function enqueue_styles_and_scripts( ) {

	}


	/**
	 * Get the data when the post is saved
	 */
	function save_post_get_value( $post ) {

		if ( !isset( $_POST[ $this->_field_name ] ) )
			return;

		$value = $_POST[ $this->_field_name ];

		if( !is_array( $value ) ) {
			$value = explode( ',', $value );
		}

		return $value;
	}
}

PR_UI_Widget::initialize_hooks();