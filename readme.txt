=== CashTippr ===
Contributors: ekliptor
Tags: bitcoin, bch, ecommerce, e-commerce, blog, tipping, store, sales, sell, shop, bitcoin cash, btc
Donate link: https://cashtippr.com/
Requires at least: 4.7
Tested up to: 5.1
Requires PHP: 7.0
Stable tag: 1.0.47
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

CashTippr: Bitcoin Cash instant tips & payments for your content

== Description ==

Earn money for your content using instant Bitcoin Cash tips (0 conf).

**Advantages & Features:**

* earn tips from your content
* add tip buttons for voluntary donations or
* hide parts of your posts to require users to tip
* 0 coding skills required

Check the demo at [CashTippr.com](https://cashtippr.com/ "CashtTippr") and send me a tip to support development.

**Easy Setup:**

* just enter your BCH address in WP admin panel. That’s all!
* add tip buttons to posts by typing a shortcode: [tippr_button]
* ability to hide content until donation: [tippr_hide]your hidden text[/tippr_hide] – a powerful way to sell digital media such photos, videos, etc...
* automatically insert tip buttons at the end of posts/pages

**Advanced Features:**

* set limits how many hidden posts new users can view (cookie based)
* customize the text before the buttons
* set daily/weekly/monthly full-access passes for all your hidden content
* setup donation goals for posts to make them fully available for free
* setup expiry times to make old posts available for free
* show ads for users who have made 0 donations
* include a Bitcoin Cash (BCH) faucet on the bottom of the page to increase your revenue and BCH adoption
* show hidden contents to search engines (only hide it with CSS)
* Memcached support for high traffic sites (>50k users daily)


This plugin loads code API code from bitcoin.com to interact with BadgerWallet.


== Installation ==
Just install it from the WordPress plugin store or upload the cashtippr.zip file via your browser in your WordPress plugin page.

== Frequently Asked Questions ==
= Is this plugin for free? =
Yes this plugin is completely free.

== Screenshots ==
1. Tip button in post
2. Admin Area 1
3. Admin Area 2
4. Admin Area 3

== Changelog ==
= 1.0.40 =
* replaced MoneyButton with BadgerWallet

= 1.0.29 =
* added support for QR code tips in BCH of non-restricted content (only when USD is selected as currency, more coming later)

= 1.0.26 =
* added BCH addresses per WordPress author

= 1.0.17 =
* added internal WP hooks in preparation for blurry image addon (and others)
* minor fixes

= 1.0.14 =
* show donation progress bar on posts with a donation goal
* option to show donation amoount posts
* added more API functions: cashtippr_button(float $amount = 0.0, $canEdit = false, $beforeBtnText = '') and cashtippr_button_hide(string $text, int $postID, float $amount = 0.0, $canEdit = false, $beforeBtnText = '')

= 1.0.13 =
* added cashtippr_button(float $amount = 0.0, $canEdit = false) API function to be used within your own plugin code and templates

= 1.0.11 =
* initial release fixes

= 1.0.10 =
* Initial release.


