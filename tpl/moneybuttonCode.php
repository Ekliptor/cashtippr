<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div id="ct-btn-wrap-<?php echo $btnConf['txid'];?>" class="ct-btn-wrap-top">
	<?php if($btnConf['addQrCode'] === true):?>  
      <div class="ct-qrcode-wrap">
        <a class="ct-qrcode-btn" href="javascript:;">
          <img src="<?php echo esc_attr($btnConf['qrcodeStatic']);?>" width="70" height="70" alt="<?php esc_attr_e('Tip with QR Code', 'ekliptor');?>" title="<?php esc_attr_e('Tip with QR Code', 'ekliptor');?>">
        </a>
      </div> 
    <?php endif;?> 
	<div class="ct-button-wrap">
      <button
        class="badger-button"
        data-to="<?php echo esc_attr($btnConf['recAddress']);?>"
        data-satoshis="<?php echo $btnConf['sats'];?>"
        data-success-callback="onBadgerPaymentG"
      ><?php esc_attr_e('Send', 'ekliptor');?> <span class="ct-btn-display-amount"><?php echo $btnConf['amount'];?></span> <?php echo $btnConf['unit'];?></button>
    </div>  
</div>
<?php if ($includedMoneybuttonScript === false): $includedMoneybuttonScript = true;?>
	<?php if($btnConf['addQrCode'] === true):?>  
		<?php include CASHTIPPR__PLUGIN_DIR . 'tpl/client/qrcode.php';?>
	<?php endif;?>
<script type="text/javascript">
	function onBadgerPaymentG(txid) {
		window.onBadgerPayment({
			buttonId: "<?php echo $btnConf['txid'];?>",
			txid: txid,
			amount: <?php echo esc_js($btnConf['amount']);?>,
			currency: "<?php echo esc_js($btnConf['unit']);?>",
			buttonData: "<?php echo base64_encode(json_encode($btnConf['callbackData']));?>"
		});
	}
</script>
<script type="text/javascript" src="https://developer.bitcoin.com/badger/badgerButton-1.0.0.js"></script>
<?php endif;?>