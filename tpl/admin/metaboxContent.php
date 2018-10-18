		<h4><?php esc_html_e( 'Auto button insert', 'ekliptor' ); ?></h4>
		
		<?php
		$tippButtonInfo = $this->makeInfo(
			__( 'This will add a tip button if it is not already added manually via shortcode.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'button_posts',
					esc_html__( 'Show a tip button on all posts', 'ekliptor' ) . ' ' . $tippButtonInfo,
					'',
					false
				),
				$this->makeCheckbox(
					'button_pages',
					esc_html__( 'Show a tip button on all pages', 'ekliptor' ) . ' ' . $tippButtonInfo,
					'',
					false
				),
			),
			true
		);

		$charsInfo = $this->makeInfo(
			__( 'Only the first words of a post will be shown. At the end this plugin will show the donation button and notice saying the user should donate to read the rest of this post. This does not affect posts that already have a button added manually via shortcode.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_limit_words',
					esc_html__( 'Limit how many words of a post users can see:', 'ekliptor' ) . ' ' . $charsInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p class="ct-input-wrap">
			<input type="number" min="1.0" step="1.0" name="<?php $this->fieldName( 'limit_words' ); ?>" class="large-text" id="<?php $this->fieldId( 'limit_words' ); ?>" placeholder="<?php echo esc_attr( $limitWordsPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'limit_words' ) ); ?>" />
		</p>
		<?php 
		$this->description( __( 'You can use the [tippr_hide] shortcode in your posts to manually limit how much of it users can view. Example: [tippr_hide]my hidden text[/tippr_hide]', 'ekliptor' ) );
		
		$postsInfo = $this->makeInfo(
			__( 'Allow new users to view a number of posts in full without donating.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_posts_free',
					esc_html__( 'Show posts completely to new users:', 'ekliptor' ) . ' ' . $postsInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p class="ct-input-wrap">
			<input type="number" min="1.0" step="1.0" name="<?php $this->fieldName( 'posts_free' ); ?>" class="large-text" id="<?php $this->fieldId( 'posts_free' ); ?>" placeholder="<?php echo esc_attr( $postsFreePlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'posts_free' ) ); ?>" />
		</p>
		
		<p>
			<label for="<?php $this->fieldId( 'hide_tip_txt' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Text before the hidden words tip button:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'hide_tip_txt' ); ?>" class="large-text" id="<?php $this->fieldId( 'hide_tip_txt' ); ?>" placeholder="<?php echo esc_attr( $tipTextPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'hide_tip_txt' ) ); ?>" />
		</p>
		<hr>

		<h4><?php esc_html_e( 'Free access', 'ekliptor' ); ?></h4>
		<?php

		$freeDaysInfo = $this->makeInfo(
			__( 'After a post reached a certain age it will be made available for free to everybody. This setting ensures that all valuabe content is eventually available and nothing on the internet gets "lost" or hidden behind paywalls. The donation button will still show at the end of the post to encourage further donations.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_free_access',
					esc_html__( 'Make old posts available after time (in days):', 'ekliptor' ) . ' ' . $freeDaysInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p class="ct-input-wrap">
			<input type="number" min="1.0" step="1.0" name="<?php $this->fieldName( 'free_access_days' ); ?>" class="large-text" id="<?php $this->fieldId( 'free_access_days' ); ?>" placeholder="<?php echo esc_attr( $freeAccessPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'free_access_days' ) ); ?>" />
		</p>
		<?php
		
		$freeTargetInfo = $this->makeInfo(
			__( 'After a post reached a predefined amount of money through donations it will be made available for free to everybody. This can encourage micro donations and also enables you to get larger donations from sponsors who want to spread your content. The donation button will still show at the end of the post to encourage further donations. This setting applies to both [tippr_hide] tags and automatically hidden words.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_donation_goal',
					esc_html__( 'Make posts available after a donation goal is reached (in button currency):', 'ekliptor' ) . ' ' . $freeTargetInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p class="ct-input-wrap">
			<input type="number" min="0.1" step="0.1" name="<?php $this->fieldName( 'donation_goal' ); ?>" class="large-text" id="<?php $this->fieldId( 'donation_goal' ); ?>" placeholder="<?php echo esc_attr( $donationGoalPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'donation_goal' ) ); ?>" />
		</p>
		
		<p>
			<label for="<?php $this->fieldId( 'donation_goal_txt' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Text before the donation goal progress bar:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'donation_goal_txt' ); ?>" class="large-text" id="<?php $this->fieldId( 'donation_goal_txt' ); ?>" placeholder="<?php echo esc_attr( $progressTextPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'donation_goal_txt' ) ); ?>" />
		</p>
		
		<?php 
		$showDonationsInfo = $this->makeInfo(
			__( 'This will publicly show the total amount of tips on every post (whithout a donation goal). If a donation goal is enabled the current tips and goal will always be shown.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'show_donations',
					esc_html__( 'Show the donated amount per post', 'ekliptor' ) . ' ' . $showDonationsInfo,
					'',
					false
				),
			),
			true
		);
		?>
		<p>
			<label for="<?php $this->fieldId( 'donation_status_txt' ); ?>" class="ct-toblock">
				<?php esc_html_e( 'Text before the donation goal progress bar:', 'ekliptor' ); ?>
			</label>
		</p>
		<p class="ct-input-wrap">
			<input type="text" name="<?php $this->fieldName( 'donation_status_txt' ); ?>" class="large-text" id="<?php $this->fieldId( 'donation_status_txt' ); ?>" placeholder="<?php echo esc_attr( $donationsTextPlaceholder ); ?>" value="<?php echo esc_attr( $this->getFieldValue( 'donation_status_txt' ) ); ?>" />
		</p>
		<hr>
		
		<h4><?php esc_html_e( 'Categories', 'ekliptor' ); ?></h4>
		<?php
		$this->description( __( 'Upcoming feature in next version of this plugin...', 'ekliptor' ) );
		
		$freeDaysInfo = $this->makeInfo(
			__( 'Only display the donation button on posts belonging to specific categories. This also includes child categories of a category listed here.', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_show_button_categories',
					esc_html__( 'Receive donation categories', 'ekliptor' ) . ' ' . $freeDaysInfo,
					'',
					false
				),
			),
			true
		);
		
		$freeDaysInfo = $this->makeInfo(
			__( 'Always show posts of the following categories and child categories for free. This setting takes precedence over "Receive donation categories".', 'ekliptor' ),
			'',
			false
		);
		
		$this->wrapFields(
			array(
				$this->makeCheckbox(
					'enable_free_categories',
					esc_html__( 'Always show categories for free', 'ekliptor' ) . ' ' . $freeDaysInfo,
					'',
					false
				),
			),
			true
		);
		