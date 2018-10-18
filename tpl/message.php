<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<!doctype html>
<html>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<style>
* {
	text-align: center;
	margin: 0;
	padding: 0;
	font-family: "Lucida Grande", Verdana, Arial, "Bitstream Vera Sans",
		sans-serif;
}

p {
	margin-top: 1em;
	font-size: 18px;
}
</style>


<body>
	<p><?php echo ($escapeHtml ? esc_html( $message ) : $message); ?></p>
</body>
</html>