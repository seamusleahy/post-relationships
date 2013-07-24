<?php

class PR_Advance_UI_Widget extends PR_UI_Widget {
	const arg_type = 'advance';

	function ui_args_filter( $args ) {
		// TODO
		$defualt = array(
			'multiple' => true,
			'orderby' => 'title',
			'order' => 'ASC',
			'posts_per_page' => 500,
			'add_to_start_of_selected' => false,
		);
		return array_merge( $defualt, $args );
	}


	function get_pool_posts( $additional_args=array() ) {
		$opposite_direction = $this->direction == 'to' ? 'from' : 'to';
		$args = array(
			'post_type' => $this->relationship->$opposite_direction,
			'orderby' => $this->ui_args['orderby'],
			'order' => $this->ui_args['order'],
			'post__not_in' => array( $this->post->ID ), // + $this->current_value_ids,
			'posts_per_page' => 20
		);

		if ( isset($additional_args['paged']) ) {
			$args['paged'] = $additional_args['paged'] ;//(int) filter_var( $args['paged'], FILTER_SANITIZE_NUMBER_INT );
		}

		if ( isset($additional_args['s']) ) {
			$args['s'] = filter_var( $additional_args['s'], FILTER_SANITIZE_STRING );
		}

		if ( !empty($additional_args['post_type']) ) {
			$args['post_type'] = filter_var( $additional_args['post_type'], FILTER_SANITIZE_STRING );
		}

		if ( isset($additional_args['orderby']) ) {
			$args['orderby'] = filter_var( $additional_args['orderby'], FILTER_SANITIZE_STRING );
		}

		if ( isset($additional_args['order']) && in_array(filter_var( $additional_args['order'], FILTER_SANITIZE_STRING ), array('ASC', 'DESC')) ) {
			$args['order'] = filter_var( $additional_args['order'], FILTER_SANITIZE_STRING );
		}

		return new WP_Query( $args );
	}


	function render_field() {

		$direction = $this->direction;
		$reverse_direction = $direction == 'from' ? 'to' : 'from';
		
		$query = $this->get_pool_posts( );

		?>
		<div class="pr-advance-widget" 
			data-widget="pr-advance-widget"
			data-name="<?php echo esc_attr( $this->field_name ); ?>"
			data-post-id="<?php echo $this->post->ID; ?>"
			data-relationship="<?php echo esc_attr( $this->relationship->name ); ?>"
			data-direction="<?php echo esc_attr( $this->direction ); ?>"
			data-add-to-start-of-selected="<?php echo esc_attr( $this->ui_args['add_to_start_of_selected'] ? 'true' : 'false' ); ?>"
			>
			<div class="selected">
				<h4>Selected</h4>
				<div class="post-list">

					<?php foreach ( $this->current_value_ids as $post_id ): ?>
						<?php 
						$post = get_post( $post_id );
						if ( !empty( $post ) ) {
							echo $this->render_post_item( $post );
						}
						 ?>
					<?php endforeach; ?>
				</div>
			</div>

			<?php
			$value = implode( ',', $this->current_value_ids );
			?>

			<div class="pool">
				<div class="filters">
					<div>
						<input data-filter="s" type="search" placeholder="Search" />
						<label><?php _e( 'Post Type', 'post-relationships' ); ?></label>
						<select data-filter="post_type">
							<option value="">Any</option>
							<?php foreach ( $this->relationship->$reverse_direction as $post_type ) {
								$post_type_obj = get_post_type_object( $post_type );

								if ( !empty( $post_type_obj ) ) {
									echo '<option value="', esc_attr($post_type), '">', esc_attr($post_type_obj->labels->name),'</option>';
								}
							} ?>
						</select>
					</div>
					<div>
						<label><?php _e( 'Order by', 'post-relationships' ); ?></label>
						<select data-filter="orderby">
							<option value="title">Title</option>
							<option value="date">Date</option>
							<option value="modified">Modified Date</option>
						</select>

						<select data-filter="order">
							<option value="ASC">lowest to highest</option>
							<option value="DESC">highest to lowest</option>
						</select>
					</div>
				</div>
				<div class="post-list">
					<?php foreach ( $query->posts as $post ): ?>
						<?php echo $this->render_post_item( $post, true ); ?>
					<?php endforeach; ?>
				</div>
			</div>

			<input name="<?php echo $this->field_name; ?>" type="hidden" value="<?php echo $value; ?>" />
		</div>
		<?php
	}


	/**
	 * Render a post item to display in the widget
	 */
	function render_post_item( $post, $in_pool=false ) {
		?>
		<div class="post" data-id="<?php echo $post->ID; ?>">
			<div class="actions">
				<a data-action="remove" class="remove">&#8722;</a><a data-action="add" class="add">+</a>
			</div>

			<div class="minified-view">
				<?php echo get_the_post_thumbnail( $post->ID, 'pr_advance_ui_thumb' ); ?>
				<h5 class="title"><?php echo get_the_title( $post->ID ); ?></h5>
				<div class="meta">
					<small class="post-type"><?php 
			$post_type = get_post_type_object( get_post_type( $post ) );
			echo esc_html( $post_type->labels->singular_name ); ?></small>
					<span class="sep">|</span>
					<small class="date"><?php echo mysql2date(get_option('date_format'), $post->post_date); ?></small>
					<span class="sep">|</span>
					<small class="expand" data-action="toggle-expanded" data-show-text="<?php esc_attr_e('Expand', 'post-relationships'); ?>" data-hide-text="<?php esc_attr_e('Hide', 'post-relationships'); ?>">Expand</small>
				</div>
			</div>

			<div class="expanded-view"><div class="inner">
				<?php echo get_the_post_thumbnail( $post->ID, 'post-thumbnail' ); ?>
				<?php echo apply_filters('the_content', $post->post_content ); ?>
			</div></div>
			
		</div>
		<?php
	}

	/**
	 * Called to enqueue stylesheets and javascripts
	 *
	 * @param $post
	 */
	function enqueue_styles_and_scripts( ) {
		$base_url = plugins_url( '', __FILE__ );
		wp_enqueue_script(
			'pr_advance_ui_widget',
			 $base_url . 'js/pr_advance_ui_widget.js',
			array( 'jquery', 'jquery-ui-core', 'jquery-ui-widget', 'jquery-ui-mouse',  'jquery-ui-sortable', 'underscore', 'backbone' )
		);

		wp_enqueue_style(
			'pr_advance_ui_widget',
			$base_url . 'css/pr_advance_ui.css'
		);
	}


	/**
	 * Get the data when the post is saved
	 */
	function save_post_get_value( $post ) {
		$value = $_POST[ $this->_field_name ];

		if( is_array( $value ) ) {
			$value = $value[0];
		}

		return explode( ',', $value );
	}


	/**
	 * Returns via ajax the posts in the pool
	 */
	static function ajax_pool_posts() {
		global $pr_admin_ui;

		// Filter the input
		$post_id = filter_input( INPUT_GET, 'post_id', FILTER_SANITIZE_NUMBER_INT );
		$post = get_post( $post_id );
		if ( empty($post) ) {
			exit;
		}

		$relationship_name = filter_input( INPUT_GET, 'relationship', FILTER_SANITIZE_STRING );
		$relationship = PR_Configuration::get_instances( $relationship_name );
		if( empty($relationship) ) {
			exit;
		}

		$direction = filter_input( INPUT_GET, 'direction', FILTER_SANITIZE_STRING );
		$direction = $direction == 'from' ? 'from' : 'to';


		// Handle the logic
		$ui = $pr_admin_ui->relationship_ui_args( $relationship );

		// Ensure that the relationship is valid for this post type
		if ( !in_array( $post->post_type, $relationship->$direction ) ) {
			exit;
		}

		$widget = new PR_Advance_UI_Widget( $relationship, $direction, $post, $ui[$direction] );
		$args = isset( $_GET['query'] ) ? (array) $_GET['query'] : array();

		$query = $widget->get_pool_posts( $args );

		$data = array( 
			'total' => (int) $query->found_posts,
			'query' => empty($args) ? null : $args,
			'items' => ''
		);

		ob_start();
		foreach ( $query->posts as $post ) {
			 $widget->render_post_item( $post, true );
		}
		$data['items'] = ob_get_clean();

		echo json_encode( $data );


		exit;
	}


	static function image_sizes() {
		add_image_size( 'pr_advance_ui_thumb', 30, 20, true );
	}
}

PR_Advance_UI_Widget::register_widget();

add_action( 'wp_ajax_pr_advance_ui_widget_pool_posts', array( 'PR_Advance_UI_Widget', 'ajax_pool_posts') );
add_action( 'init', array( 'PR_Advance_UI_Widget', 'image_sizes') );