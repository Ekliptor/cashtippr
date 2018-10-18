<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div class="metabox-holder columns-2">
	<div class="postbox-container-1">
		<?php do_meta_boxes( $this->pageHook, 'main', null ); ?>
	</div>
</div>