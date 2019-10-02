		<h4><?php esc_html_e( 'Custom button styles', 'ekliptor' ); ?></h4>
		<?php 
		$this->description( __( 'You can can add custom styles to your CashTippr buttons. For example you can change the color, align them right or centered and much more using CSS.', 'ekliptor' ) );
		?>
		<hr>
		
		<?php 
		$this->description( __( 'Example button CSS:', 'ekliptor' ) );
		$this->description( __( 'Align button centered: ', 'ekliptor' ) . '.ct-button {text-align: center;}' );
		$this->description( __( 'Change button color to blue: ', 'ekliptor' ) . '.ct-badger-button {color: #1423e9;}' );
		$this->description( __( 'Color button background red: ', 'ekliptor' ) . '.ct-badger-button {background-color: #f20e20;}' );
		$this->description( __( 'Change button font and size: ', 'ekliptor' ) . '.ct-badger-button {font-family: "Times New Roman", Times, serif; font-size: 10px;}' );
		$this->description( __( 'Change the "hidden words info text" to italic: ', 'ekliptor' ) . '.ct-restricted {font-style: italic; font-weight: lighter;}' );
		?>
		<hr>
		
		<p>
			<label for="<?php $this->fieldId( 'custom_css' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Custom CSS:', 'ekliptor' ); ?>
			</label>
		</p>
		<p>
			<textarea name="<?php $this->fieldName( 'custom_css' ); ?>" class="large-text" id="<?php $this->fieldId( 'custom_css' ); ?>" rows="3" cols="70" placeholder="<?php echo esc_attr( $cssPlaceholder ); ?>"><?php echo esc_attr( $this->getFieldValue( 'custom_css' ) ); ?></textarea>
		</p>
		<?php 
		$this->description( __( 'You can write your CSS into the field above or manually into the CSS files of your WordPress theme. Remember to separate CSS selectors by line breaks.', 'ekliptor' ) );
		