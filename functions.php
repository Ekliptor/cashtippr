<?php
if (function_exists('pre_print_r') === false) {
	function pre_print_r($expression, $return = false) {
		$output = "<pre>" . print_r($expression, true) . "</pre><br>\n";
		if ($return)
			return $output;
		echo $output;
	}
}

if (function_exists('debugPrint') === false) {
	function debugPrint($name, $obj) {
		echo "<pre>$name:\r\n" . print_r($obj, true) . "\r\n</pre>";
	}
}
?>