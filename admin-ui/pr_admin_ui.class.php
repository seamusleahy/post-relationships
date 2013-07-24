<?php
/**
 * Handles the registrations of meta boxes for the relationships.
 */
class PR_Admin_UI {

	static protected $widget_classes;
	static public function get_widget_classes() {
		if( empty( self::$widget_classes ) ) {
			$widget_classes = array();

			//
			// Add your widget classes to the registered type using the `pr_widget_classes` filter.
			// The key is the type that is used in the configuration and the value is the name of the class.
			self::$widget_classes = apply_filters( 'pr_widget_classes', $widget_classes );
		}

		return self::$widget_classes;
	}

	function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes') );
		add_action( 'save_post', array( $this, 'save_post' ) );
	}


	/**
	 * Called for the 'add_meta_boxes' hook to add meta boxes on the posts page
	 */
	function add_meta_boxes() {
		$relationships = PR_Configuration::get_all_instances();
		global $post;

		// Adding each relationship
		foreach( $relationships as $relationship ) {
			$ui = $this->relationship_ui_args( $relationship );

			// Adding to and from metaboxes
			foreach( array( 'to', 'from' ) as $direction ) {
				if( $ui[$direction]['widget'] ) {

					// For each post-type because add_meta_box only takes a single post-type name
					foreach( $relationship->$direction as $post_type ) {
						if( $post_type == get_post_type() ) {

							// Get the widget types
							$widget_classes = self::get_widget_classes();
							if( !array_key_exists( $ui[$direction]['widget'], $widget_classes ) ) {
								// Missing widget class
								break;
							}

							$widget_class = $widget_classes[ $ui[$direction]['widget'] ];
							$widget = new $widget_class( $relationship, $direction, $post, $ui[$direction] );


							add_meta_box(
								"post_relationship_{$direction}_{$relationship->name}",
								$ui[$direction]['label'],
								array( $widget, 'render'),
								$post_type,
								$ui[$direction]['context'],
								$ui[$direction]['priority'],
								array( 'widget' => $widget )
							);

							add_action( 'admin_enqueue_scripts', array( $widget, 'enqueue_styles_and_scripts') );
						}
					}
				}
			}
		}
	}


	/**
	 * Handle the saving
	 */
	function save_post( $post_id ) {
		// Bail if we're doing an auto save	
		if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$post = get_post( $post_id );
		$relationships = PR_Configuration::get_all_instances();
		$widget_classes = self::get_widget_classes();

		// find all the relationships each relationship
		foreach( $relationships as $relationship ) {
			$ui = $this->relationship_ui_args( $relationship );
			
			// There are two directions to check for
			foreach( array( 'to', 'from' ) as $direction ) {
				// Check if current post-type is for the direction relation and has a widget
				if( in_array( $post->post_type, $relationship->$direction ) && $ui[$direction]['widget'] && !empty($widget_classes[ $ui[$direction]['widget'] ]) ) {
					$widget_class = $widget_classes[ $ui[$direction]['widget'] ];
					$widget = new $widget_class( $relationship, $direction, $post, $ui );
					$widget->save_post( $post );
				}
			}
		}
	}


	/**
	 * The callback called for rendering the meta-box
	 */
	function render_meta_box( $widget ) {
		$widget->render();
	}


	/**
	 * Fills in missing relationship ui args and then returns the settings.
	 */
	function relationship_ui_args( $relationship ) {
		// Not set, set it all to false
		if( !is_array( $relationship->ui ) ) {
			$relationship->ui = array(
				'from' => false,
				'to' => false,
			);
		}

		// Make sure we have `widget` set
		foreach( array('from', 'to') as $field ) {
			if( !isset( $relationship->ui[$field] ) ) {
				$relationship->ui[$field] = array();
			}

			if( !isset( $relationship->ui[$field]['widget'] ) ) {
				// No widget, set to false
				$relationship->ui[$field]['widget'] = false;

			} elseif( $relationship->ui[$field]['widget'] ) {
				if( empty( $relationship->ui[$field]['label'] ) ) {
					// Have a widget, but no label
					$relationship->ui[$field]['label'] = __('Post Relationship'); // TODO: smart label
				}

				if( empty( $relationship->ui[$field]['context'] ) ) {
					$relationship->ui[$field]['context'] = 'normal';
				}

				if( empty( $relationship->ui[$field]['priority'] ) ) {
					$relationship->ui[$field]['priority'] = 'default';
				}
			}
		}
		
		return $relationship->ui;
	}
}

global $pr_admin_ui;
$pr_admin_ui = new PR_Admin_UI();