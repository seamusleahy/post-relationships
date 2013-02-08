<?php

/**
 * Defines the configuration for post relationship.
 */
class PR_Configuration {
	
	static protected $_instances = array();


	/**
	 * Create a new instances
	 */
	static public function create_instances( $name, $args ) {

		if( array_key_exists( $name, self::$_instances ) ) {
			// Update the configuration
			self::$_instances[$name]->update( $args );

		} else {
			// Create a new instances
			self::$_instances[$name] = new self( $name, $args );
		}

		return self::$_instances[$name];
	}


	/**
	 *  Get the a configuration instances
	 */
	static public function get_instances( $name ) {
		if( array_key_exists( $name, self::$_instances ) ) {
			return self::$_instances[ $name ];
		}
		return null;
	}



	protected function __construct( $name, $args ) {
		$this->name = $name;
		$this->update( $args );
	}


	/**
	 * Update the args
	 */
	protected function update( $args ) {

		foreach( array( 'from', 'to' ) as $field ) {
			if( !empty( $args[$field]) ) {
				$this->$field = (array) $args[$field];
			} elseif( empty( $this->$field ) ) {
				$this->$field = array( 'post' );
			}
		}
	}
}