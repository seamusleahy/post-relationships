<?php

class PR_Select_UI_Widget extends PR_UI_Widget {
	const arg_type = 'select';


	function ui_args_filter( $args ) {
		$defualt = array(
			'multiple' => true,
			'orderby' => 'title',
			'order' => 'ASC',
			'posts_per_page' => 500,
			'size' => !array_key_exists( 'multiple', $args ) || $args['multiple'] ? 5 : 1,
		);
		return array_merge( $defualt, $args );
	}



	function render_field() {

		$direction = $this->direction;
		$opposite_direction = $direction == 'to' ? 'from' : 'to';
		$args = array(
			'post_type' => $this->relationship->$opposite_direction,
			'orderby' => $this->ui_args['orderby'],
			'order' => $this->ui_args['order'],
			'post__not_in' => array( $this->post->ID ),
			'posts_per_page' => 100,
			'fields' => 'ids',
		);
		$posts = get_posts( $args );

		$multiple = $this->ui_args['multiple'] ? ' multiple="multiple"' : '';

		$current_ids = array_map( function( $p ) { return $p->ID; }, $this->current_values->posts );

		?>
		<select name="<?php echo $this->field_name; ?>" <?php echo $multiple; ?> <?php if( $this->ui_args['size'] > 1 ) { echo 'size="'.$this->ui_args['size'].'"'; } ?>>
			<?php foreach( $posts as $post_id ) : ?>
				<?php $selected = in_array( $post_id, $current_ids ) ? ' selected="selected"' : ''; ?>
				<option value="<?php $post_id; ?>" <?php echo $selected; ?>><?php echo esc_html( get_the_title( $post_id ) ); ?></option>
			<?php endforeach; ?> 
		</select>
		<?php
	}
}

PR_Select_UI_Widget::register_widget();
