<?php

add_filter( 'flexible-shipping/shipping-method/rules-calculation-function', 'gc_override_shipping_cost', 10, 2 );
function gc_override_shipping_cost($calculated_cost, $object){
	return 'gc_override_price';
}
//add_shortcode( 'gc_test_code', 'gc_override_price' );
function gc_override_price( $calculated_cost, $rule_cost ){
	$cost_per_order='';
	// Get an instance of the WooCommerce cart
	if ( isset( WC()->cart ) && is_object( WC()->cart ) ) {
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );
		$weight_unit    = get_option( 'woocommerce_weight_unit' );
		$cart_instance = WC()->cart;
		// Get cart contents
		$cart_contents = $cart_instance->get_cart();
		// Initialize maximum weight variable
		$max_weight = 0.0;
		// Loop through cart items
		foreach ($cart_contents as $cart_item_key => $cart_item) {
			// Get the product ID
			if ( isset( $cart_item['product_id'] ) ) {
				$product_id = $cart_item['product_id'];
				if(!empty($product_id)){
					$product = $cart_item['data'];
					$qty = $cart_item['quantity'];
					$volumetric_weight = 0.0;
					if($product->get_length() && $product->get_width() && $product->get_height()){
						$length            = wc_get_dimension( str_replace( ",", ".", $product->get_length() ), 'cm', $dimension_unit );
						$width             = wc_get_dimension( str_replace( ",", ".", $product->get_width() ), 'cm', $dimension_unit );
						$height            = wc_get_dimension( str_replace( ",", ".", $product->get_height() ), 'cm', $dimension_unit );
						$volumetric_weight = ( $length * $width * $height ) / 5000 * $qty;
						$weight = 0.0;
						if ( $product->get_weight() ) {
							$weight = wc_get_weight( str_replace( ",", ".", $product->get_weight() ), 'kg', $weight_unit ) * $qty;
						}
						// Calculate the maximum weight
						$max_weight = max( $max_weight, max( $volumetric_weight, $weight ) );
						
					}
				}
			}
		}
	}

	// Now compare the maximum weight with the values obtained from your code
	$all_shipping_methods = WC()->shipping()->load_shipping_methods();
	$flexible_shipping = $all_shipping_methods['flexible_shipping'];
	$flexible_shipping_rates = $flexible_shipping->get_all_rates();

	foreach ( $flexible_shipping_rates as $rate_id => $flexible_shipping_rate ) {
		if ( isset( $flexible_shipping_rate['method_rules'] ) ) {
			$method_rules = $flexible_shipping_rate['method_rules'];
			foreach($method_rules as $method_rule){
				// Apply cost_per_order based on the maximum weight of a single product
				$max_weight = ceil($max_weight * 10) / 10;
				//echo '<br>';
				if ($max_weight >= $method_rule['conditions'][0]['min'] && $max_weight <= $method_rule['conditions'][0]['max']) {
					// Apply cost_per_order based on the conditions
					$cost_per_order = $method_rule['cost_per_order']; // This will be applied based on the conditions
					break 2;
				}
				else{

				}
			}
		}
	}
	return $cost_per_order;
}

?>
