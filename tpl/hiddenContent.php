<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div class="ct-button">
	<div id="ct-button-text-<?php echo $btnConf['txid'];?>" class="ct-button-text">
	  <?php if($btnConf['text'] !== ''):?>
	    <?php esc_html_e($btnConf['text']); ?>
	  <?php else:?>  
	    <span class="ct-restricted"><?php esc_html_e($btnConf['restrictedTxt']); ?></span>
	  <?php endif;?>  
	  <?php include CASHTIPPR__PLUGIN_DIR . 'tpl/amountEditInput.php';?>
	</div>
      <?php include CASHTIPPR__PLUGIN_DIR . 'tpl/moneybuttonCode.php';?>
</div>
<?php if($this->settings->get('show_search_engines') === true):?>
  <span id="ct-hidden-<?php echo $btnConf['txid'];?>" class="ct-hidden-text">
    <?php echo $btnConf['content'];?>
  </span>
<?php endif;?>  