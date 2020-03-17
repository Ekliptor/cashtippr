<?php
if (!defined('ABSPATH'))
	exit("denied");
?>
<div class="wrap ct-metaboxes">
	<form id="ct-options-form" method="post" action="options.php">
		<?php wp_nonce_field( 'closedpostboxes', 'closedpostboxesnonce', false ); ?>
		<?php wp_nonce_field( 'meta-box-order', 'meta-box-order-nonce', false ); ?>
		<?php settings_fields( $this->settings->settingsField ); ?>

		<div class="ct-top-wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<p class="ct-top-buttons">
				<?php
				submit_button( __('Save Changes'), 'primary', 'submit', false, array( 'id' => '' ) );
				?>
			</p>
		</div>

		<?php do_action( "{$this->pageHook}_settings_page_boxes", $this->pageHook ); ?>

		<div class="ct-bottom-buttons">
			<?php
			submit_button( __('Save Changes'), 'primary', 'submit', false, array( 'id' => '' ) );
			?>
		</div>
		<input type="hidden" name="all_checkboxes" value="<?php echo $this->tpl->getAllCheckboxes(true);?>" />
		<input type="hidden" name="all_multiselect" value="<?php echo $this->tpl->getAllMultiselect(true);?>" />
	</form>
</div>
<?php
//= Add postbox listeners
?>
<script type="text/javascript">
	jQuery(document).ready( function($) {
		// close postboxes that should be closed
		$('.if-js-closed').removeClass('if-js-closed').addClass('closed');
		// postboxes setup
		if (typeof postboxes !== "undefined") {
			postboxes.add_postbox_toggles('<?php echo esc_js( $this->pageHook ); ?>');
		}

		var canonical = document.getElementById( 'wp-admin-canonical' );
		if ( canonical && window.history.replaceState ) { // as the JS code from the removable_query_args hook
			var wpRefUrl = document.getElementById('ct-options-form')['_wp_http_referer'];
			if (wpRefUrl)
				wpRefUrl.value = canonical.href + window.location.hash
		}
	});
</script>
<?php
