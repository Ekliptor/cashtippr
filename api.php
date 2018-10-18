<?php
/**
 * This file contains API functions to be used within other plugins. Use them to add CashTippr to your templates.
 */

/**
 * Get a tip button from within your own plugin code.
 * @param float $amount the amount the button will show. 0.0 means the default value from WP settings.
 * @param boolean $canEdit true if the user can edit the amount, default false
 * @param string $beforeBtnText Write a custom text before the button. You may use the {words} tag to to insert the
 * 		number of hidden words on this posts. Defaults to the text from WP settings if empty.
 * @return string the tip button HTML
 */
function cashtippr_button(float $amount = 0.0, $canEdit = false, $beforeBtnText = ''): string {
	$attrs = array(	
		'amount' => $amount,
		'text' => empty($beforeBtnText) ? '' : $beforeBtnText
	);
	if ($canEdit === true)
		$attrs[] = 'edit';
	return Cashtippr::getInstance()->showTipprButton($attrs, null, 'tippr_button');
}

/**
 * Get a tip button hiding text. This function is to be called within your own functions/plugins.
 * @param string $text the text to hide
 * @param int $postID The ID of the post/page you are currently showing. You can use get_the_ID().
 * 			If this is not an actual post, but some dynamic content you want to hide, you must use
 * 			a self-generated unique ID. CashTippr needs this to check if the user has already paid for this hidden content.
 * @param float $amount the amount required to make the hidden content visible. 0.0 means the default value from WP settings.
 * @param boolean $canEdit true if the user can edit (increase) the amount, default false
 * @param string $beforeBtnText Write a custom text before the button. You may use the {words} tag to to insert the
 * 			number of hidden words. Defaults to the text from WP settings if empty.
 * @return string the tip button HTML
 */
function cashtippr_button_hide(string $text, int $postID, float $amount = 0.0, $canEdit = false, $beforeBtnText = ''): string {
	$attrs = array(	
		'amount' => $amount,
		'postID' => $postID, // TODO add a check to see if that post exists and differs from the currrently showing post
		'text' => empty($beforeBtnText) ? '' : $beforeBtnText
	);
	if ($canEdit === true)
		$attrs[] = 'edit';
	return Cashtippr::getInstance()->showTipprButton($attrs, $text, 'tippr_hide');
}
?>