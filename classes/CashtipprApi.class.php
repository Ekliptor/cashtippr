<?php
class CashtipprApiRes {
	public $error = false;
	public $errorMsg = '';
	public $data = array();
	
	public function setError(string $msg) {
		$this->error = true;
		$this->errorMsg = $msg;
	}
}

class CashtipprApi {
	/** @var CashtipprApi */
	private static $instance = null;
	/** @var Cashtippr */
	protected $cashtippr;
	
	private function __construct(Cashtippr $cashtippr) {
		if ($cashtippr === null)
			throw new Error("Cashtippr class must be provided in constructor of " . get_class($this));
		$this->cashtippr = $cashtippr;
	}
	
	public static function getInstance(Cashtippr $cashtippr = null) {
		if (self::$instance === null)
			self::$instance = new self($cashtippr);
		return self::$instance;
	}
	
	public function init() {
		// init hooks
		$webhookDataParam = array(
						'required' => true,
						'type' => 'string', // valid types: array, boolean, integer, number, string
						'sanitize_callback' => array( self::$instance, 'sanitizeStringParam' ),
						'description' => __( 'The Payment callback from moneybutton.', 'ekliptor' ),
					);
		register_rest_route( 'cashtippr/v1', '/moneybutton', array(
			array(
				'methods' => WP_REST_Server::CREATABLE,
				'permission_callback' => array( self::$instance, 'moneyButtonPermissionCallback' ),
				'callback' => array( self::$instance, 'processPayment' ),
				'args' => array(
					'data' => $webhookDataParam	
				)
			)
		) );
		
		$txidParam = array(
						'required' => true,
						'type' => 'string',
						'sanitize_callback' => array( self::$instance, 'sanitizeStringParam' ),
						'description' => __( 'The TXID of the payment.', 'ekliptor' ),
					);
		$amountParam = array(
						'required' => true,
						'type' => 'number',
						'sanitize_callback' => array( self::$instance, 'sanitizeFloatParam' ),
						'description' => __( 'The amount of the payment.', 'ekliptor' ),
					);
		register_rest_route( 'cashtippr/v1', '/mb-client', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				'permission_callback' => array( self::$instance, 'moneyButtonPermissionCallback' ),
				'callback' => array( self::$instance, 'processClientPayment' ),
				'args' => array(
					'txid' => $txidParam,
					'am' => $amountParam
				)
			)
		) );
		
		register_rest_route( 'cashtippr/v1', '/qrcode', array(
			array(
				'methods' => WP_REST_Server::READABLE,
				//'permission_callback' => array( self::$instance, 'moneyButtonPermissionCallback' ),
				'callback' => array( self::$instance, 'getQrCode' ),
				'args' => array(
					'txid' => $txidParam,
					'am' => $amountParam
				)
			)
		) );
	}
	
	public function processPayment(WP_REST_Request $request) {
		//Cashtippr::notifyErrorExt("api", $request);
		// https://github.com/WP-API/WP-API/issues/2490
		//add_post_meta($this->post->ID, 'tipAmount', 3.0, true);
		$response = new CashtipprApiRes();
		// TODO as of september 12 MoneyButton is just launching
		// the docs are still changing https://docs.moneybutton.com/docs/webhook.html
		// as of right now even their <script src="https://moneybutton.com/moneybutton.js" /> script respons with 404
		// https://docs.moneybutton.com/docs/html.html
		
		// TODO our TX are valid for 24h. we should always check via JS before paying?
		return rest_ensure_response($response);
	}
	
	public function processClientPayment(WP_REST_Request $request) {
		global $wpdb;
		$table = Cashtippr::getTableName('transactions');
		// TODO remove this once MoneyButton webhooks are working
		
		$response = new CashtipprApiRes();
		$txid = $request->get_param('txid');
		$query = $wpdb->prepare("SELECT txid, session_id, post_id, days FROM $table WHERE txid = '%s'", array($txid));
		$row = $wpdb->get_row($query);
		if (!empty($row)) {
			$currentAmount = (float)$request->get_param('am');
			// update the post's total received donations
			$postID = (int)$row->post_id;
			if ($postID !== 0) {
				$tipAmount = (float)get_post_meta($postID, 'tipAmount', true);
				$tipAmount += $currentAmount;
				update_post_meta($postID, 'tipAmount', $tipAmount); // always unique, not like add_post_meta
				// TODO include prev value? but then it won't be updated otherwise. better code our inc function with mysql directly
			}
			
			// update the user's donations
			// TODO verify amount once callback is ready
			$this->cashtippr->addTipAmount($currentAmount);
			$this->cashtippr->addTippedPost($postID, $currentAmount);
			$days = (int)$row->days;
			if ($days !== 0)
				$this->cashtippr->addFullAccessPass($days);
			
			// inc total number & amount of tips. don't use options here because they require too many DB queries. use our settings instead
			//update_option('cashtippr_tips', $value, false);
			$settings = $this->cashtippr->getSettings();
			$settings->setMultiple(array(	
				'tips' => $settings->get('tips') + 1,
				'tip_amount' => $settings->get('tip_amount') + $currentAmount
			));
			
			// delete the transaction from DB. every button has its unique txid and can only be paid once
			$wpdb->delete($table, array('txid' => $txid));
		}
		else {
			$response->error = true;
			$response->errorMsg = 'Payment not found';
		}
		return rest_ensure_response($response);
	}
	
	public function getQrCode(WP_REST_Request $request) {
		$response = new CashtipprApiRes();
		$qrImageUrl = $this->generateQrCode($request->get_param('txid'), $request->get_param('am'));
		if ($qrImageUrl !== '')
			$response->data[] = $qrImageUrl;
		else
			$response->setError('Unable to generate QR Code');
		return rest_ensure_response($response);
	}
	
	public function moneyButtonPermissionCallback(WP_REST_Request $request) {
		return true; // everyone can access this for now
		//$key = $request->get_param("apiKey");
	}
	
	public function sanitizeStringParam( $value, WP_REST_Request $request, $param ) {
		return trim( $value );
	}
	
	public function sanitizeFloatParam( $value, WP_REST_Request $request, $param ) {
		return (float)trim( $value );
	}
	
	/**
	 * Generate a QR code for the payment
	 * @param string $txid
	 * @param float $amount
	 * @return string the public image URL of the QR code
	 */
	protected function generateQrCode(string $txid, float $amount): string {
		global $wpdb;
		// we must use TXID instead of BCH address because later we will generate unique addresses per payment (when hiding content with qr code)
		$qrHash = hash('sha512', $txid . $amount);
		$fileName = sprintf('data/temp/qr/%s.png', $qrHash);
		$fileLocal = CASHTIPPR__PLUGIN_DIR . $fileName;
		$fileUrl =  plugins_url( $fileName, CASHTIPPR__PLUGIN_DIR . 'cashtippr.php' );
		if (file_exists($fileLocal) === true)
			return $fileUrl; // use it from cache
		
		$table = \Cashtippr::getTableName('transactions');
		$query = $wpdb->prepare("SELECT address, amount FROM $table WHERE txid = '%s'", array($txid));
		$row = $wpdb->get_row($query);
		if (empty($row))
			return '';
		if ($amount == 0.0)
			$amount = $row->amount;
		$amountBCH = $this->cashtippr->toAmountBCH($amount);
		$codeContents = "bitcoincash:{$row->address}?amount=$amountBCH";
		QR_Code\QR_Code::png($codeContents, $fileLocal);
		return $fileUrl;
	}
}
?>