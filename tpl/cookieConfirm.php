<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div id="ct-cookieMsg" class="hidden-print"><?php esc_html_e($this->settings->get('cookie_consent_txt'), 'ekliptor');?>
  <input type="button" id="ct-confirmCookies" class="button button-primary button-large" value="OK"></button>
  <?php if(function_exists('get_privacy_policy_url') === true):?>
    <a href="<?php echo esc_attr(get_privacy_policy_url());?>"><?php esc_html_e($privacyPageTitle, 'ekliptor');?></a>
  <?php endif;?>
</div>  