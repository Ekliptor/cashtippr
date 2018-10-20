<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div id="ct-modal-qrcode-dialog-tpl">
  <div id="ct-modal-qrcode-dialog" class="{dialog_class}">
    <div id="ct-modal-background"></div>
      <div id="ct-modal" class="ct-modal">
        <div class="ct-modal-inner">
        <div>
          <a class="ct-close-dialog" href="javascript:;">
            <img src="<?php echo esc_attr($btnConf['closeImage'])?>" width="50" height="50" alt="<?php esc_attr_e('close', 'ekliptor');?>" title="<?php esc_attr_e('close', 'ekliptor');?>">
          </a>
          <h2 class="ct-dialog-head"><?php esc_html_e('Send a tip', 'ekliptor');?></h2>
        </div>
        <form id="ct-qrcode-form" action="" method="post" enctype="application/x-www-form-urlencoded">
          <p>
		    <label for="ct-address"><?php esc_html_e( 'BCH address:', 'ekliptor' ); ?></label>
	      </p>
	      <p class="ct-textfield-group">
		    <input id="ct-address" type="text" name="ct-address" value="<?php echo esc_attr($btnConf['recAddress']);?>" readonly> 
		    <input type="button" class="ct-copy-field" value="<?php esc_attr_e( 'Copy', 'ekliptor' ); ?>">
	      </p>
	      <p>
		    <label for="ct-amount"><?php esc_html_e( 'Amount (BCH):', 'ekliptor' ); ?></label>
	      </p>
	      <p class="ct-textfield-group">
		    <input id="ct-amount" type="text" name="ct-amount" value="<?php echo esc_attr($btnConf['amountBCH']);?>" readonly>
		    <input type="button" class="ct-copy-field" value="<?php esc_attr_e( 'Copy', 'ekliptor' ); ?>">
	      </p>
	      <p class="ct-qr-rate">
	        <?php esc_html_e( 'Approximate value:', 'ekliptor' ); ?> 
	        <?php echo $btnConf['amount'];?> <?php echo esc_html($btnConf['unit']);?>
	      </p>
	      <p class="ct-qr-code">
	        <img class="ct-loading" src="<?php echo esc_attr($btnConf['loadingImage'])?>" width="140" height="140" alt="<?php esc_attr_e('loading...', 'ekliptor');?>" title="<?php esc_attr_e('loading...', 'ekliptor');?>">
	        <img class="ct-qr-code" src="" width="160" height="160" alt="<?php esc_attr_e('Tip with QR Code', 'ekliptor');?>" title"<?php esc_attr_e('Tip with QR Code', 'ekliptor');?>" style="display: none;">
	      </p>
        </form>
      </div>
    </div>
  </div>
</div>