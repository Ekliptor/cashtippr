<?php
class ButtonTemplates {
	/** @var Cashtippr */
	protected $cashtippr;
	/** @var CTIP_Settings */
	protected $settings;
	
	public function __construct(Cashtippr $cashtippr) {
		$this->cashtippr = $cashtippr;
		$this->settings = $this->cashtippr->getSettings();
	}
	
	public function getProgressBar(int $postID): string {
		$tipForPost = (float)get_post_meta($postID, 'tipAmount', true);
		$donationPerc = $tipForPost / $this->settings->get('donation_goal') * 100;
		$bar = array(
			'color' => $this->getProgressBarColor($donationPerc),
			'progressPercent' => floor($donationPerc > 100 ? 100 : $donationPerc),
			'progressText' => sprintf('%s / %s %s', $this->formatForCurrency($tipForPost),
					$this->formatForCurrency($this->settings->get('donation_goal')), $this->settings->get('button_currency')),
			'progressTextBefore' => $this->settings->get('donation_goal_txt'),
			'reachedGoal' => $this->cashtippr->reachedDonationGoal($postID)
		);
		ob_start();
		include CASHTIPPR__PLUGIN_DIR . 'tpl/progressBar.php';
		$progressHtml = ob_get_contents();
		ob_end_clean();
		return $progressHtml;
	}
	
	public function getDonationStatus(int $postID): string {
		$tipForPost = (float)get_post_meta($postID, 'tipAmount', true);
		$status = array(
			'text' => $this->replaceDonationStatusVars($tipForPost)
		);
		ob_start();
		include CASHTIPPR__PLUGIN_DIR . 'tpl/donationStatus.php';
		$statusHtml = ob_get_contents();
		ob_end_clean();
		return $statusHtml;
	}
	
	public function fillTipprButtonTextTemplate(string $text): string {
		$text = str_replace('{site_name}', get_bloginfo('name'), $text);
		$text = str_replace('{days}', $this->settings->get('custom_access_days'), $text);
		return $text;
	}
	
	public function fillTipprButtonHiddenTextTemplate(string $text, int $hiddenWords/*, string $post*/): string {
		$text = str_replace('{words}', $hiddenWords, $text);
		return $text;
	}
	
	protected function getProgressBarColor(float $donationPerc): string {
		if ($donationPerc < 10.0)
			return 'red';
		else if ($donationPerc < 40.0)
			return 'orange';
		else if ($donationPerc < 75.0)
			return 'yellow';
		return 'green';
	}
	
	protected function replaceDonationStatusVars(float $tipForPost): string {
		$text = $this->settings->get('donation_status_txt');
		$text = str_replace('{tips}', $this->formatForCurrency($tipForPost), $text);
		return $text;
	}
	
	protected function formatForCurrency(float $amount): string {
		$currency = $this->settings->get('button_currency');
		if (in_array($currency, CashtipprData::TWO_DIGIT_CURRENCY_FORMAT) === true)
			//return sprintf('%.2f', $amount) . ' ' . $currency; //use comma separator format from locale
			return number_format_i18n($amount, 2) . ' ' . $currency;
		return number_format_i18n($amount, 8) . ' ' . $currency;
	}
}
?>