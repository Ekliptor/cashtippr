<?php
if (!defined('ABSPATH'))
	exit("denied");
// TODO make input settings for HTML + CSS to allow customized progress bars. including circles...
?>
<?php if ($bar['reachedGoal'] === false):?>
  <div class="ct-progress-text-before"><?php esc_html_e($bar['progressTextBefore'], 'ekliptor');?></div>
<?php endif;?>  
<div class="ct-progress-bar ct-<?php echo $bar['color'];?>">
  <span class="ct-progress-bar-progress" style="width: <?php echo $bar['progressPercent'];?>%"></span><br>
  <div class="ct-progress-text"><?php esc_html_e($bar['progressText'], 'ekliptor');?></div>
</div>