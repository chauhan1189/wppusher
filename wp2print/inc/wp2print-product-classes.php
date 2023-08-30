<?php
class WC_Product_Fixed extends WC_Product {

	public function __construct( $product = 0 ) {
		$this->supports[]   = 'ajax_add_to_cart';
		parent::__construct( $product );
	}

	public function get_type() {
		return 'fixed';
	}

}

class WC_Product_Book extends WC_Product {

	public function __construct( $product = 0 ) {
		$this->supports[]   = 'ajax_add_to_cart';
		parent::__construct( $product );
	}

	public function get_type() {
		return 'book';
	}

}

class WC_Product_Area extends WC_Product {

	public function __construct( $product = 0 ) {
		$this->supports[]   = 'ajax_add_to_cart';
		parent::__construct( $product );
	}

	public function get_type() {
		return 'area';
	}

}

class WC_Product_Aec extends WC_Product {

	public function __construct( $product = 0 ) {
		$this->supports[]   = 'ajax_add_to_cart';
		parent::__construct( $product );
	}

	public function get_type() {
		return 'aec';
	}

}

class WC_Product_Aecbwc extends WC_Product {

	public function __construct( $product = 0 ) {
		$this->supports[]   = 'ajax_add_to_cart';
		parent::__construct( $product );
	}

	public function get_type() {
		return 'aecbwc';
	}

}

class WC_Product_Aecsimple extends WC_Product {

	public function __construct( $product = 0 ) {
		$this->supports[]   = 'ajax_add_to_cart';
		parent::__construct( $product );
	}

	public function get_type() {
		return 'aecsimple';
	}

}
?>