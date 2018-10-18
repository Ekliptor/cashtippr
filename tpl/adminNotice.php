<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<p>
  <?php if (empty($tplVars['link'])): ?>
  		<?php esc_html_e($tplVars['msg'], 'ekliptor'); ?>
  <?php else: ?>	
  		<a href="<?php echo $tplVars['link']; ?>"><?php esc_html_e($tplVars['msg'], 'ekliptor'); ?></a>
  <?php endif; ?>		
</p>