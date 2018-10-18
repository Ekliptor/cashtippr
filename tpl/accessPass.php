<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div class="ct-button">
  <div id="ct-button-text-<?php echo $btnConf['txid'];?>" class="ct-button-text">
    <?php if($days === 1):?>
      <?php esc_html_e($btnConf['tipTxt']); ?>
    <?php else:?>
      <?php esc_html_e($btnConf['tipTxt']); ?>
    <?php endif;?>
  </div>
      <?php include CASHTIPPR__PLUGIN_DIR . 'tpl/moneybuttonCode.php';?>
</div>    