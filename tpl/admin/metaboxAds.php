<?php
use Ekliptor\Wordpress\TemplateEngine;
?>
		<h4><?php esc_html_e( 'Banners or Popups', 'ekliptor' ); ?></h4>
		<?php 
		$adsInfo = $this->makeInfo(
			__( 'This will insert custom ad code at the top of posts and pages.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_ads_top',
					esc_html__( 'Insert advertisement at the top of posts', 'ekliptor' ) . ' ' . $adsInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p>
			<label for="<?php $this->fieldId( 'ad_code' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'The banner HTML or JavaScript code of your ad:', 'ekliptor' ); ?>
			</label>
		</p>
		<p>
			<textarea name="<?php $this->fieldName( 'ad_code' ); ?>" class="large-text" id="<?php $this->fieldId( 'ad_code' ); ?>" rows="3" cols="70" placeholder="<?php echo esc_attr( $adPlaceholder ); ?>"><?php echo esc_attr( $this->getFieldValue( 'ad_code' ) ); ?></textarea>
		</p>
		<?php 
		$this->description( __( 'This code will be inserted at the top of all posts for users who have made 0 donations so far.', 'ekliptor' ) );
		?>
		<hr>
		
		<h4><?php esc_html_e( 'AdBlock Detection', 'ekliptor' ); ?></h4>
		<?php 
		$adblockInfo = $this->makeInfo(
			__( 'Detect AdBlock usage. This will load some additional JavaScript and fire events in PHP and JavaScript, see developer docs for details: https://cashtippr.com/developer-docs/', 'ekliptor' ),
			'',
			false
		);
		$adblockDisableInfo = $this->makeInfo(
			__( 'Disable the page for AdBlock users if they did not donate anything yet. The page will then show your custom WordPress page chosen below. The page will stay disabled until a donation/tip is made. Make sure to include a tip button shortcode on this page!', 'ekliptor' ),
			'',
			false
		);
		
		$adblockPage = $this->makeSelect('adblock_page', __('AdBlock Page:', 'ekliptor'), CTIP_TemplateEngine::getPageNames());
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'detect_adblock',
					esc_html__( 'Detect AdBlock', 'ekliptor' ) . ' ' . $adblockInfo,
					'',
					false
				),
				$this->makeCheckbox(
					'adblockDisable',
					esc_html__( 'Disable page for AdBlock users with 0 tips', 'ekliptor' ) . ' ' . $adblockDisableInfo,
					'',
					false
				),
				$adblockPage,
			),
			true
		);
		
		$adblockNoConflictInfo = $this->makeInfo(
			__( 'This will prevent false positives (AdBlock detection when the visitor has no adblock installed) that may happen if other plugins inspect AdBlock as well. If enabled AdBlock detection will be less strict. It is recommended to only enable this if you run into problems with other plugins.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'adblockNoConflict',
					esc_html__( 'Enable no-conflict mode', 'ekliptor' ) . ' ' . $adblockNoConflictInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<hr>
		
		<h4><?php esc_html_e( 'Bitcoin Faucet', 'ekliptor' ); ?></h4>
		<?php 
		$faucetInfo = $this->makeInfo(
			__( 'Display a Bitcoin faucet address at the end of all posts containing a tip button (only for users who haven\'t donated anything yet). This is a great way to increase your revenue and spread crptocurrency adoption at the same time. You can also add a link to an exchange with your referral link.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_faucet',
					esc_html__( 'Show Bitcoin faucet for new users:', 'ekliptor' ) . ' ' . $faucetInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'faucet_bch' ); ?>" class="large-text" id="<?php $this->fieldId( 'faucet_bch' ); ?>" placeholder="<?php echo esc_attr( $faucetPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'faucet_bch' ) ); ?>" />
		</p>
		
		<p>
			<label for="<?php $this->fieldId( 'faucet_bch_text' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Text on the link:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'faucet_bch_text' ); ?>" class="large-text" id="<?php $this->fieldId( 'faucet_bch_text' ); ?>" placeholder="<?php echo esc_attr( $faucetTextPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'faucet_bch_text' ) ); ?>" />
		</p>
		<?php 