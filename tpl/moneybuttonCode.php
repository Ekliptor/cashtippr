<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div id="ct-btn-wrap-<?php echo $btnConf['txid'];?>">
	<?php if($btnConf['addQrCode'] === true):?>  
      <div class="ct-qrcode-wrap">
        <a class="ct-qrcode-btn" href="javascript:;">
          <img src="<?php echo esc_attr($btnConf['qrcodeStatic']);?>" width="70" height="70" alt="<?php esc_attr_e('Tip with QR Code', 'ekliptor');?>" title="<?php esc_attr_e('Tip with QR Code', 'ekliptor');?>">
        </a>
      </div> 
    <?php endif;?> 
    <div class="ct-button-wrap">
      <div class="money-button"
	    data-to="<?php echo $btnConf['recAddress'];?>"
	    data-amount="<?php echo $btnConf['amount'];?>"
	    data-currency="<?php echo $btnConf['unit'];?>"
	    <?php if(false):?>
	    data-label="<?php echo $btnConf['amount'];?> <?php echo $btnConf['unit'];?>"
	    <?php endif;?>
	    data-button-data="<?php echo base64_encode(json_encode($btnConf['callbackData']));?>"
	    data-type="tip"
	    data-on-payment="onMoneyButtonPaymentG"
	    data-on-error="onMoneyButtonErrorG"
	    data-button-id="<?php echo $btnConf['txid'];?>"
	  ></div>
    </div>  
</div>
<?php if ($includedMoneybuttonScript === false): $includedMoneybuttonScript = true;?>
	<?php if($btnConf['addQrCode'] === true):?>  
		<?php include CASHTIPPR__PLUGIN_DIR . 'tpl/client/qrcode.php';?>
	<?php endif;?>
<script type="text/javascript">
	function onMoneyButtonPaymentG(p) {
		window.onMoneyButtonPayment(p);
	}
	function onMoneyButtonErrorG(p) {
		window.onMoneyButtonError(p);
	}
</script>
<script type="text/javascript" src="https://api.moneybutton.com/moneybutton.js"></script>
<?php endif;?>