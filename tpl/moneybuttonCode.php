<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div id="ct-btn-wrap-<?php echo $btnConf['txid'];?>" class="ct-btn-wrap-top">
	<?php if($btnConf['addQrCode'] === true && !isset($btnConf['noQrButton'])):?>  
      <div class="ct-qrcode-wrap">
        <a class="ct-qrcode-btn" href="javascript:;">
          <div class="ct-qrcode-img-wrap">
            <img src="<?php echo esc_attr($btnConf['qrcodeStatic']);?>" width="50" height="50" alt="<?php esc_attr_e('Click to tip with QR Code', 'ekliptor');?>" title="<?php esc_attr_e('Click to tip with QR Code', 'ekliptor');?>">
            <div class="ct-qr-btn-txt"><?php esc_html_e('Click', 'ekliptor');?></div>
          </div>
        </a>
      </div> 
    <?php endif;?> 
	<div class="ct-button-wrap">
      <button
        class="ct-badger-button button"
        data-to="<?php echo esc_attr($btnConf['recAddress']);?>"
        data-satoshis="<?php echo $btnConf['sats'];?>"
        data-success-callback="onBadgerPaymentG_<?php echo $btnConf['txid'];?>"
      ><?php echo esc_html($btnConf['btnText']);?> <span class="ct-btn-display-amount"><?php echo $btnConf['amount'];?></span> <?php echo $btnConf['unit'];?></button>
    </div>  
</div>
<?php if($btnConf['addQrCode'] === true && $includedQrCodeTemplate === false): $includedQrCodeTemplate = true;?>  
	<?php include CASHTIPPR__PLUGIN_DIR . 'tpl/client/qrcode.php';?>
<?php endif;?>
<?php if ($includedMoneybuttonScript === false): $includedMoneybuttonScript = true;?>
<script type="text/javascript">
	function onBadgerPaymentG_<?php echo $btnConf['txid'];?>(txid) {
		window.onBadgerPayment({
			buttonId: "<?php echo $btnConf['txid'];?>",
			txid: txid,
			amount: <?php echo esc_js($btnConf['amount']);?>,
			currency: "<?php echo esc_js($btnConf['unit']);?>",
			buttonData: "<?php echo base64_encode(json_encode($btnConf['callbackData']));?>"
		});
	}
</script>
<?php endif;?>