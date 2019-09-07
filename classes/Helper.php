<?php
namespace Ekliptor\Wordpress;

class Helper {
	/**
	 * Updates the $key of the order meta with $value only if it doesn't already have the same value.
	 * This is only applicable to metadata with a unique key, otherwise we just always add the value using add_meta_data().
	 * @param \WC_Order $order
	 * @param string $key
	 * @param mixed $value
	 * @return bool True if the value was updated, false otherwise.
	 */
	public static function updateOrderMeta(\WC_Order $order, string $key, $value): bool {
		$currentValue = $order->get_meta($key);
		if ($currentValue === $value)
			return false;
		$order->add_meta_data($key, $value, true);
    	$order->save_meta_data();
    	return true;
	}
}
?>