<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div class="ct-button">
	<div id="ct-button-text-<?php echo $btnConf['txid'];?>" class="ct-button-text">
	  <?php if($btnConf['text'] !== ''):?>
	    <?php esc_html_e($btnConf['text']); ?>
	  <?php elseif($btnConf['isRestricted'] === true && $btnConf['restrictedTxt'] !== ''):?>
	    <span class="ct-restricted"><?php esc_html_e($btnConf['restrictedTxt']); ?></span>
	  <?php else:?>
	    <?php esc_html_e($this->settings->get('tip_txt'), 'ekliptor'); ?>
	  <?php endif;?>
	  <?php include CASHTIPPR__PLUGIN_DIR . 'tpl/amountEditInput.php';?>
	</div>
      <?php include CASHTIPPR__PLUGIN_DIR . 'tpl/moneybuttonCode.php';?>
</div>    