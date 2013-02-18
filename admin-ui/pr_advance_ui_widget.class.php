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
    );
    return array_merge( $defualt, $args );
  }



  function render_field() {
    // TODO
  }
}

PR_Advance_UI_Widget::register_widget();