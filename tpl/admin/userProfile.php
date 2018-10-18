		<h2><?php esc_html_e( 'CashTippr', 'ekliptor' ); ?></h2>
		
		<?php
		// don't use fieldId() and similar functions here because we are not creating post vars part of an array of a settings group
		
		$bchAddressInfo = $this->makeInfo(
			__( 'Enter the BCH address tor receive tips for all posts directly. The administrator can enable/disable this setting globally.', 'ekliptor' ),
			'',
			false
		);
		?>
		<table class="form-table">
			<tbody>
				<tr class="user-bch-wrap">
					<th><label for="author_bch_address"><?php esc_html_e( 'BCH address:', 'ekliptor' ); ?> <?php echo $bchAddressInfo;?></label></th>
					<td>
						<input type="text" name="author_bch_address" id="author_bch_address" placeholder="<?php echo esc_attr( $bchAddressPlaceholder ); ?>" value="<?php $this->userFieldValue( 'author_bch_address', $user->ID );?>" class="regular-text ltr" />
					</td>
				</tr>
			</tbody>
		</table>

		<?php 
		