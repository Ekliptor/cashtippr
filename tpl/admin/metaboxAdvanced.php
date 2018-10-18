		<h4><?php esc_html_e( 'Data', 'ekliptor' ); ?></h4>
		
		<?php
		$searchInfo = $this->makeInfo(
			__( 'This will will hide content using CSS. This way search engines can see and index your full articles. Visitors viewing the HTML source code of your pages can also view full articles.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'show_search_engines',
					esc_html__( 'Show full contents of posts to search engines', 'ekliptor' ) . ' ' . $searchInfo,
					'',
					false
				),
			),
			true
		);
		
		$cookieInfo = $this->makeInfo(
			__( 'In countries within the EU you are required by law to inform your visitors that your website is using cookies. Enabling this will inform your visitors on their first visit with a message at the buttom of the page.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'show_cookie_consent',
					esc_html__( 'Show cookie consent dialog to new users', 'ekliptor' ) . ' ' . $cookieInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p>
			<label for="<?php $this->fieldId( 'cookie_consent_txt' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Cookie consent text:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'cookie_consent_txt' ); ?>" class="large-text" id="<?php $this->fieldId( 'cookie_consent_txt' ); ?>" placeholder="<?php echo esc_attr( $cookiePlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'cookie_consent_txt' ) ); ?>" />
		</p>
		
		<p>
			<label for="<?php $this->fieldId( 'session_name' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Browser session name:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'session_name' ); ?>" class="large-text" id="<?php $this->fieldId( 'session_name' ); ?>" placeholder="<?php echo esc_attr( $sessionPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'session_name' ) ); ?>" />
		</p>
		<?php 
		$this->description( __( 'Changing the session name value will cause all previous payments to be not recognized anymore!', 'ekliptor' ) );
		?>
		<hr>
		
		<h4><?php esc_html_e( 'Memcached', 'ekliptor' ); ?></h4>
		<?php
		$memcachedInfo = $this->makeInfo(
			__( 'If your website has many visitors (> 50k daily) using memcached for sessions instead of MySQL can improve performance.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'use_memcached',
					esc_html__( 'Use memcached server for user session storage', 'ekliptor' ) . ' ' . $memcachedInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p>
			<label for="<?php $this->fieldId( 'memcached_host' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Host', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'memcached_host' ); ?>" class="large-text" id="<?php $this->fieldId( 'memcached_host' ); ?>" placeholder="<?php echo esc_attr( $memHostPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'memcached_host' ) ); ?>" />
		</p>
		<p>
			<label for="<?php $this->fieldId( 'memcached_host' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Port', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'memcached_port' ); ?>" class="large-text" id="<?php $this->fieldId( 'memcached_port' ); ?>" placeholder="<?php echo esc_attr( $memPortPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'memcached_port' ) ); ?>" />
		</p>
		<?php 
		