		<h4><?php esc_html_e( 'CashTippr shortcode', 'ekliptor' ); ?></h4>
		<?php
		$this->description( __( 'You can put a tip button on every page or post by just writing: [tippr_button]', 'ekliptor' ) );
		$this->description( __( 'To use a specific amount as tip: [tippr_button amount="5.5"]', 'ekliptor' ) );
		$this->description( __( 'To let the user enter an amount: [tippr_button edit] or [tippr_button amount="5.5" edit]', 'ekliptor' ) );
		$this->description( __( 'Write a custom text before the button: [tippr_button text="BCH please"]', 'ekliptor' ) );
		$this->description( __( 'Tip to a specific BCH address (addresses per author must be enabled): [tippr_button address="BCH address"]', 'ekliptor' ) );
		?>
		<hr>

		<h4><?php esc_html_e( 'Bitcoin Cash Address', 'ekliptor' ); ?></h4>
		<p>
			<label for="<?php $this->fieldId( 'bch_address' ); ?>" class="ct-toblock">
				<strong><?php printf( esc_html__( 'Your %s address to receive money:', 'ekliptor' ), 'BCH' ); ?></strong>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'bch_address' ); ?>" class="large-text" id="<?php $this->fieldId( 'bch_address' ); ?>" placeholder="<?php echo esc_attr( $bchAddressPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'bch_address' ) ); ?>" autocomplete=off />
		</p>
		
		<p>
			<label for="<?php $this->fieldId( 'button_currency' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Button currency:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'button_currency' ); ?>" class="large-text" id="<?php $this->fieldId( 'button_currency' ); ?>" placeholder="<?php echo esc_attr( $btnCurrencyPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'button_currency' ) ); ?>" />
		</p>
		
		<p>
			<label for="<?php $this->fieldId( 'default_amount' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Default button amount:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="number" min="0.0000001" step="0.0000001" name="<?php $this->fieldName( 'default_amount' ); ?>" class="large-text" id="<?php $this->fieldId( 'default_amount' ); ?>" placeholder="<?php echo esc_attr( $defAmountPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'default_amount' ) ); ?>" />
		</p>
		
		<p>
			<label for="<?php $this->fieldId( 'tip_txt' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Text before the tip button:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'tip_txt' ); ?>" class="large-text" id="<?php $this->fieldId( 'tip_txt' ); ?>" placeholder="<?php echo esc_attr( $tipTextPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'tip_txt' ) ); ?>" />
		</p>
		<hr>
		
		<h4><?php esc_html_e( 'Full access passes', 'ekliptor' ); ?></h4>
		<?php 
		$dailyInfo = $this->makeInfo(
			__( 'This will will show an additional payment button option where users can pay once to view all articles on your blog. Access will be granted for 24 hours.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_daily_pass',
					esc_html__( 'Show daily full-access payment option', 'ekliptor' ) . ' ' . $dailyInfo,
					'',
					false
				),
			),
			true
		);
		?>
		
		<p>
			<label for="<?php $this->fieldId( 'daily_access_price' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Daily access price:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'daily_access_price' ); ?>" class="large-text" id="<?php $this->fieldId( 'daily_access_price' ); ?>" placeholder="<?php echo esc_attr( $pricePlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'daily_access_price' ) ); ?>" />
		</p>
		
		<p>
			<label for="<?php $this->fieldId( 'daily_tip_txt' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Text before the daily tip button:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'daily_tip_txt' ); ?>" class="large-text" id="<?php $this->fieldId( 'daily_tip_txt' ); ?>" placeholder="<?php echo esc_attr( $tipTextPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'daily_tip_txt' ) ); ?>" />
		</p>
		
		<?php 
		$customInfo = $this->makeInfo(
			__( 'This will will show an additional payment button option where users can pay once to view all articles for a duration you can specify. You can use this for monthly or annual memberships.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_custom_pass',
					esc_html__( 'Show custom interval full-access payment option (in days):', 'ekliptor' ) . ' ' . $customInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p class="ct-input-wrap">
			<input type="number" min="1.0" step="1.0" name="<?php $this->fieldName( 'custom_access_days' ); ?>" class="large-text" id="<?php $this->fieldId( 'custom_access_days' ); ?>" placeholder="<?php echo esc_attr( $customPassPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'custom_access_days' ) ); ?>" />
		</p>
		<p>
			<label for="<?php $this->fieldId( 'custom_access_price' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Custom interval access price:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'custom_access_price' ); ?>" class="large-text" id="<?php $this->fieldId( 'custom_access_price' ); ?>" placeholder="<?php echo esc_attr( $pricePlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'custom_access_price' ) ); ?>" />
		</p>
		
		<p>
			<label for="<?php $this->fieldId( 'custom_tip_txt' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Text before the custom tip button:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'custom_tip_txt' ); ?>" class="large-text" id="<?php $this->fieldId( 'custom_tip_txt' ); ?>" placeholder="<?php echo esc_attr( $tipTextPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'custom_tip_txt' ) ); ?>" />
		</p>
		<?php 
		$customInfo = $this->makeInfo(
			__( 'This allows every WordPress author to set his own BCH address on his profile page to receive tips directly to his wallet. If an author has not set his BCH address, tips will go to the admin wallet.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'author_bch_addresses',
					esc_html__( 'Enable individual BCH addresses per author', 'ekliptor' ) . ' ' . $customInfo,
					'',
					false
				),
			),
			true
		);
		
		
		