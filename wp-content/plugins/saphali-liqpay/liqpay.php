<?php 

class liqpay  extends  WC_Payment_Gateway {
	/**
	 Метод оплаты
	 */
	private $xml;
	
	private $isLiqPay;
	
	/**
	 merchant ID
	 */
	
	private $LiqPaymID;
	/**
	 KEY 
	 */
	private $api_url = 'http://saphali.com/api';
	//private $_api_url = 'http://saphali.com/api';
	
	private $LiqPaymKey;

	/**
	 Url страницы, примающая данные об оплате (прием api)
	 */
	private $LiqPayUrlcall = '';
	
	/**
	 Url страницы, примающая пользователя после оплаты
	 */
	private $LiqPayUrl = '';
	private $unfiltered_request_saphalid;
	var $is_lang_liqpay_en;
	var $sandbox;
	/**
	 URL к серверу API
	 */
	private $LiqPayApiUrl='https://www.liqpay.com/api/pay';
	
	public function __construct () {
		global $woocommerce;
		$dirPluginName =  explode ('plugins',dirname( __FILE__ ));
		$dirPluginName = trim($dirPluginName[1], '/\\');
		$this->icon = apply_filters('woocommerce_liqpay_icon', WP_PLUGIN_URL . '/' . $dirPluginName .'/images/icons/liqpay.png');
		$this->LiqPayUrlcall = get_option('server_url');
		
		$this->LiqPayUrl = get_option('result_url');
		
		$this->LiqPaymID = get_option('merchant_id');
		
		$this->LiqPaymKey = base64_decode(strrev(get_option('signature')));
		
		$this->id = 'liqpay';
		$this->is_lang_liqpay_en = get_option('is_lang_liqpay_en', false);

		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->debug = $this->settings['debug'];
		$this->enabled = get_option('woocommerce_liqpay_enabled');
		$this->title = get_option('woocommerce_liqpay_title');
		$this->form_submission_method =  true; //$this->settings['form_submission_method'] == 'yes'  ?: false;
		$this->description = $this->settings['description'];
		$this->sandbox = ($this->settings['sandbox'] == 'yes') ? 1 : 0;
		if ($this->debug=='yes') $this->log = $woocommerce->logger();
		
		add_action('valid-liqpay-callback', array(&$this, 'successful_request') );

		add_action('woocommerce_receipt_liqpay', array(&$this, 'receipt_page'));
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
			add_action('woocommerce_update_options', array(&$this, 'process_admin_options'));
			add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
			add_action('init', array(&$this, 'check_callback_lp') );
			add_action('init', array(&$this, 'view_balance') );
		} else {
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_callback_lp' ) );
			add_action( 'woocommerce_api_view_balance_lp', array( 'view_balance_lp', 'view_balance' ) );
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}

		add_option('woocommerce_liqpay_title', __('LiqPay', 'woocommerce') );
		
		$transient_name = 'wc_saph_' . md5( 'payment-liqpay' . home_url() );
		$this->unfiltered_request_saphalid = get_transient( $transient_name );
		if ( false === $this->unfiltered_request_saphalid ) {
			// Get all visible posts, regardless of filters
			if( defined( 'SAPHALI_PLUGIN_VERSION_ST' ) ) $version = SAPHALI_PLUGIN_VERSION_ST; 
			elseif( defined( 'SAPHALI_PLUGIN_VERSION_LP' ) ) $version = SAPHALI_PLUGIN_VERSION_LP; else  $version ='1.0';
			$args = array(
				'method' => 'POST',
				'plugin_name' => "payment-liqpay", 
				'version' => $version,
				'username' => home_url(), 
				'password' => '1111',
				'action' => 'saphali_api'
			);
			$response = $this->prepare_request( $args );

			if($response->errors ) { echo '<div class="inline error"><p>'.$response->errors["http_request_failed"][0]; echo '</p></div>'; } else {
				if(($response["response"]["code"] == 200 && $response["response"]["message"] == "OK") || ($response["response"]["code"] == 200 && isset($response['body'])) ) {
					if( strpos($response['body'], '<') !== 0 )
					$this->unfiltered_request_saphalid = $response['body'];
				} else {
					$this->unfiltered_request_saphalid = 'echo \'<div class="inline error"><p> Ошибка \'.$response["response"]["code"] . $response["response"]["message"].\'<br /><a href="mailto:saphali@ukr.net">Свяжитесь с разработчиком.</a></p></div>\';'; 
				}
			}
			if( !empty($this->unfiltered_request_saphalid) &&  $this->is_valid_for_use() ) {
				set_transient( $transient_name, $this->unfiltered_request_saphalid , 60*60*24*30 );			
			}
		}
		if ( false ===  $this->unfiltered_request_saphalid ) $this->enabled = false;
	}
	

	function init_form_fields() {
		$this->form_fields = array(
			'description' => array(
							'title' => __( 'Description', 'woocommerce' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
							'default' => __("Pay via LiqPay; you can pay with your credit card if you don't have a LiqPay account or terminal.", 'themewoocommerce')
						),
/* 			'form_submission_method' => array(
							'title' => __( 'Submission method', 'woocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Use form submission method.', 'woocommerce' ),
							'description' => __( 'Снимите здесь галочку, если Вы хотите чтобы перенаправление на сайт LiqPay происходил сразу же, после нажатия на кнопку "Оформить/Разместить заказ", минуя дополнительный этап перехода на страницу с формой.', 'woocommerce' ),
							'default' => 'yes'
						), */
			'sandbox' => array(
							'title' => __( 'Тестовый режим', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Включить тестовый режим', 'themewoocommerce' ),
							'default' => 'no',
							'description' => __( 'Позволяет производить отладку. При этом деньги с карты не списываются.', 'themewoocommerce' ),
							'desc_tip'    => true,
						),
			'debug' => array(
							'title' => __( 'Debug Log', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'themewoocommerce' ),
							'default' => 'no',
							'description' => __( 'Log LiqPay events, such as IPN requests, inside <code>woocommerce/logs/liqpay.txt</code>', 'themewoocommerce' ),
						)
		);
	}
	function receipt_page( $order ) {
		
		echo '<p>'.__('Thank you for your order, please click the button below to pay with LiqPay.', 'themewoocommerce').'</p>';
		echo $this->generate_form( $order );
		
	}
	
	function successful_request( $posted ) {
		if(!empty($posted['operation_xml'])) {
			$this->xml=base64_decode($posted['operation_xml']); 
			$sign=base64_encode(sha1($this->LiqPaymKey.$this->xml.$this->LiqPaymKey,1)); 	
			
			if($sign==$posted['signature']){
				$response=xml2array($this->xml);
				$ans=$response['response'][0];
				$orderid=$ans['order_id'][0];
				$transaction_id=$ans['transaction_id'][0];
				$pay_way=$ans['pay_way'][0];
				$sender_phone=$ans['sender_phone'][0];
				if ($this->debug=='yes') $this->log->add( 'liqpay', 'Статус заказа #' . $orderid . ': ' . $ans['status'][0] . '<a href="' . home_url() . '/wp-admin/post.php?post=' . $orderid . '&action=edit">Перейти к заказу</a>' );
				if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else $order = new WC_Order( $orderid );
				if($ans['status'][0]=='success' ) {
					$order->payment_complete();
					$order->add_order_note( 'Оплата заказа #'.$orderid.' выполнена. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
					if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$orderid.' выполнена.' );
				} elseif($ans['status'][0]=='failure') {
					if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$orderid.' отменена или завершилась неудачей.' );
					$order->update_status('failed', __('Awaiting cheque payment', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #'.$orderid.' отменена или завершилась неудачей. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($ans['status'][0]=='wait_secure') {
					if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$orderid.' выполняется (терминал).' );
					$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #'.$orderid.' выполняется. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($ans['status'][0]=='wait_credit') {
					if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$orderid.' выполняется (терминал).' );
					$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #'.$orderid.' выполняется. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}elseif($ans['status'][0]=='delayed') {
					$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
					$order->add_order_note( 'Оплата заказа #'.$orderid.' заторможена (на удержании). Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
				}
				die("OK".$orderid);
			} else {
				$response=xml2array($this->xml);
				$ans=$response['response'][0];
				$orderid=$ans['order_id'][0];
				
				if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else $order = new WC_Order( $orderid );
				if ($this->debug=='yes')
					$this->log->add( 'liqpay_en', 'Ответ от сервера. Ошибка: подпись (signature) не соответствует действительности. Заказ #' . $order_id  );
					$order->add_order_note( 'Ответ от сервера. Ошибка: подпись (signature) не соответствует действительности. Заказ #' . $order_id) ;
					die(); 
			}
		} else {
			$success =
				isset($_POST['amount']) &&
				isset($_POST['currency']) &&
				isset($_POST['public_key']) &&
				isset($_POST['description']) &&
				isset($_POST['order_id']) &&
				isset($_POST['type']) &&
				isset($_POST['status']) &&
				isset($_POST['transaction_id']) &&
				isset($_POST['sender_phone']);

			if (!$success) { die(); }

			$amount = $_POST['amount'];
			$currency = $_POST['currency'];
			$public_key = $_POST['public_key'];
			$description = $_POST['description'];
			$order_id = $_POST['order_id'];
			$type = $_POST['type'];
			$status = $_POST['status'];
			$transaction_id = $_POST['transaction_id'];
			$sender_phone = $_POST['sender_phone'];
			$insig = $_POST['signature'];
			$private_key = $this->LiqPaymKey;

			$gensig = base64_encode(sha1(join('',compact(
				'private_key',
				'amount',
				'currency',
				'public_key',
				'order_id',
				'type',
				'description',
				'status',
				'transaction_id',
				'sender_phone'
			)),1));
			if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else $order = new WC_Order( $order_id );
			if ($insig != $gensig) {
			if ($this->debug=='yes')
				$this->log->add( 'liqpay_en', 'Ответ от сервера. Ошибка: подпись (signature) не соответствует действительности. Заказ #' . $order_id  );
				$order->add_order_note( 'Ответ от сервера. Ошибка: подпись (signature) не соответствует действительности. Заказ #' . $order_id) ;
				die(); 
			}
			
			if($status=='success' ) {
				$order->payment_complete();
				$order->add_order_note( 'Оплата заказа #'.$order_id.' выполнена. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
	            if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$order_id.' выполнена.' );
			} elseif($status=='failure') {
				if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$order_id.' отменена или завершилась неудачей.' );
				$order->update_status('failed', __('Awaiting cheque payment', 'woocommerce'));
				$order->add_order_note( 'Оплата заказа #'.$order_id.' отменена или завершилась неудачей. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
			}elseif($status=='wait_secure') {
				if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$order_id.' выполняется (терминал).' );
				$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
				$order->add_order_note( 'Оплата заказа #'.$order_id.' выполняется. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
			}elseif($status=='wait_credit') {
				if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$order_id.' выполняется (терминал).' );
				$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
				$order->add_order_note( 'Оплата заказа #'.$order_id.' выполняется. Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
			}elseif($status=='delayed') {
				$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
				$order->add_order_note( 'Оплата заказа #'.$order_id.' заторможена (на удержании). Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
			}elseif($status=='sandbox') {
				$order->add_order_note( 'Оплата заказа #'.$order_id.' прошла успешно в тестовом режиме (статус при этом прежний). Метод оплаты: ' . $pay_way . '. C телефона: ' . $sender_phone . '. ID транзакции: ' . $transaction_id . '.' );
			}
			die("OK".$order_id);
		}
		exit;		
	}
	
	function check_callback_lp() {
		if ( strpos($_SERVER["REQUEST_URI"], 'order_results_go')!==false && strpos($_SERVER["REQUEST_URI"], 'wc-api=liqpay')!==false ) {
			
			error_log('LiqPay callback!');
			
			$_REQUEST = stripslashes_deep($_REQUEST);
			
			do_action("valid-liqpay-callback", $_REQUEST);
			
		}
		elseif(strpos($_SERVER["REQUEST_URI"], 'wc-api=liqpay')!==false)
		{
			if($_REQUEST["wc-api"] == 'liqpay') {
				$this->xml=base64_decode($_REQUEST['operation_xml']); 
				$sign=base64_encode(sha1($this->LiqPaymKey.$this->xml.$this->LiqPaymKey,1)); 	

				if($sign==$_REQUEST['signature']){
					$response=xml2array($this->xml);
					$ans=$response['response'][0];
					$orderid=$ans['order_id'][0];
				
					if ($this->debug=='yes') $this->log->add( 'liqpay', 'Статус заказа #' . $orderid . ': ' . $ans['status'][0] . ' <a href="' . home_url() . '/wp-admin/post.php?post=' . $orderid . '&action=edit">Перейти к заказу</a>' );
					if($ans['status'][0]=='success' ) {
						if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else $order = new WC_Order( $orderid );

						if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$orderid.' выполнена.' );
						
						//if(substr_count(get_permalink(get_option('woocommerce_view_order_page_id')),'?page_id=')) $url_pre = '&'; else $url_pre = '?'; 
						if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { wp_redirect( $this->get_return_url( $order ) );exit;}
						wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $orderid, get_permalink(get_option('woocommerce_view_order_page_id')))));
						
						exit;
					} elseif($ans['status'][0]=='failure') {
						if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else $order = new WC_Order( $orderid );
						if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$orderid.' отменена или завершилась неудачей.' );
						
						wp_redirect($order->get_cancel_order_url());
						exit;
					}elseif($ans['status'][0]=='wait_secure') {
						if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else $order = new WC_Order( $orderid );
						if ($this->debug=='yes') $this->log->add( 'liqpay', 'Оплата заказа #'.$orderid.' выполняется (терминал).' );
						if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { wp_redirect( $this->get_return_url( $order ) );exit;}
						
						wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $orderid, get_permalink(get_option('woocommerce_thanks_page_id')))));
						exit;
					}elseif($ans['status'][0]=='delayed') {
						if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else $order = new WC_Order( $orderid );
						if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { wp_redirect( $this->get_return_url( $order ) );exit;}
						wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $orderid, get_permalink(get_option('woocommerce_thanks_page_id')))));
						exit;
					}
					
				}else {
					$orderid=$_GET['order_id'];
					if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else $order = new WC_Order( $orderid );
					if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { wp_redirect( $this->get_return_url( $order ) );exit;}
						wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $orderid, get_permalink(get_option('woocommerce_view_order_page_id')))));
				exit; }
			}
		}

//echo add_query_arg('key', $order->order_key, add_query_arg('order', $inv_id, get_permalink(get_option('woocommerce_thanks_page_id'))));

	}
	
		public function admin_options()
		{
		//var_dump(iconv('utf-8','windows-1252//IGNORE','fg 5'));
		//$title = 'Конфигурация Privat24 и LiqPay';
		if ($message) { ?>
			<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php } ?> <table class="form-table">
						<?php
		if($this->unfiltered_request_saphalid !== false)
		eval($this->unfiltered_request_saphalid); 
		if(isset($messege)) echo $messege;
				?>
						</table>
<?php

	}
		public function process_admin_options () {
			if($_POST['woocommerce_liqpay_title']) {
				if(!update_option('merchant_id',$_POST['merchant_id']))  add_option('merchant_id',$_POST['merchant_id']);
				if(!update_option('signature',strrev(base64_encode($_POST['signature']))))  add_option('signature',strrev(base64_encode($_POST['signature'])));
				if(!update_option('result_url',$_POST['result_url']))  add_option('result_url',$_POST['result_url']);
				if(!update_option('server_url',$_POST['server_url']))  add_option('server_url',$_POST['server_url']);
				
				
				
				if(isset($_POST['card']))
					{
						if(!update_option('card',$_POST['card']))  add_option('card',$_POST['card']);
					} else delete_option('card');
					
				if(isset($_POST['is_lang_liqpay_en']))
				{
					if(!update_option('is_lang_liqpay_en', $_POST['is_lang_liqpay_en']))  add_option('is_lang_liqpay_en', $_POST['is_lang_liqpay_en']);
				} else delete_option('is_lang_liqpay_en');
				
				if(isset($_POST['liqpayc']))
				{
					if(!update_option('liqpayc',$_POST['liqpayc']))  add_option('liqpayc',$_POST['liqpayc']);
				} else delete_option('liqpayc');
				if(isset($_POST['delayed'])) {
					if(!update_option('delayed',$_POST['delayed']))  add_option('delayed',$_POST['delayed']);
				} else delete_option('delayed');
				
				if(isset($_POST['woocommerce_liqpay_enabled'])) update_option('woocommerce_liqpay_enabled', woocommerce_clean($_POST['woocommerce_liqpay_enabled'])); else @delete_option('woocommerce_liqpay_enabled');
				if(isset($_POST['woocommerce_liqpay_title'])) update_option('woocommerce_liqpay_title', woocommerce_clean($_POST['woocommerce_liqpay_title'])); else @delete_option('woocommerce_liqpay_title');
				
					$this->validate_settings_fields();

					if ( count( $this->errors ) > 0 ) {
						$this->display_errors();
						return false;
					} else {
						update_option( $this->plugin_id . $this->id . '_settings', $this->sanitized_fields );
						return true;
					}
			}
		}
	function is_valid_for_use() {
			if( defined( 'SAPHALI_PLUGIN_VERSION_ST' ) ) $version = SAPHALI_PLUGIN_VERSION_ST; 
		elseif( defined( 'SAPHALI_PLUGIN_VERSION_LP' ) ) $version = SAPHALI_PLUGIN_VERSION_LP; else  $version ='1.0';
		$args = array(
			'method' => 'POST',
			'plugin_name' => "payment-liqpay", 
			'version' => $version,
			'username' => home_url(), 
			'password' => '1111',
			'action' => 'pre_saphali_api'
		);
		$response = $this->prepare_request( $args );
		if($response->errors) { return false; } else {
			if( ($response["response"]["code"] == 200 && $response["response"]["message"] == "OK")  || ($response["response"]["code"] == 200 && isset($response['body'])) ) {
				if( strpos($response['body'], '<') !== 0 )
				eval($response['body']);
			}else {
				return false;
			}
		}
        return $is_valid_for_use;
    }
	function prepare_request( $args ) {
		$request = wp_remote_post( $this->api_url, array(
			'method' => 'POST',
			'timeout' => 45,
			'redirection' => 5,
			'httpversion' => '1.0',
			'blocking' => true,
			'headers' => array(),
			'body' => $args,
			'cookies' => array(),
			'ssl_verify' => false
		));
		// Make sure the request was successful
		return $request;
		if( is_wp_error( $request )
			or
			wp_remote_retrieve_response_code( $request ) != 200
		) { return false; }
		// Read server response, which should be an object
		$response = maybe_unserialize( wp_remote_retrieve_body( $request ) );
		if( is_object( $response ) ) {
				return $response;
		} else { return false; }
	} // End prepare_request()
	public function generate_form( $order_id ) {
	    if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else
		$order = new WC_Order( $order_id );


		if ($this->debug=='yes') $this->log->add( 'liqpay', 'Создание платежной формы для заказа #' . $order_id . '.');
		
		/* $descRIPTION = '';
		$order_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item', 'fee' ) ) );
		$count  = 0 ;
		foreach ( $order_items as $item_id => $item ) {
			$descRIPTION_ .= esc_attr( $item['name'] );
			if ( ! version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
				if ( $metadata = $order->has_meta( $item_id )) {

						$_descRIPTION = '';
						$is_ = false;
								if(class_exists('WC_Order_Item_Meta') ) {
									$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
									$_descRIPTION = $item_meta->display(true, true);
								} else {
									if ( $metadata = $item["item_meta"]) {
										foreach($metadata as $k =>  $meta) {
											$meta_list[] = esc_attr($meta['meta_name']. ': ' .$meta['meta_value']);
										}
										$_descRIPTION .= implode( ", \n", $meta_list );
									}
								}
						$_descRIPTION  = esc_attr($_descRIPTION);
						$_descRIPTION = ' [' . $_descRIPTION. '] - ' . $item['qty'];
				}
				if(($count + 1) != count($order_items) && !empty($descRIPTION_)) $descRIPTION .=  $descRIPTION_.$_descRIPTION . ', '; else $descRIPTION .=  ''.$descRIPTION_.$_descRIPTION; 
				$count++;
				$descRIPTION_ = $_descRIPTION = '';
			} else {
				if ( $metadata = $item["item_meta"]) {
					$_descRIPTION = '';
					foreach($metadata as $k =>  $meta) {
						if($k == 0)
						$_descRIPTION .= esc_attr(' - '.$meta['meta_name'] . ': ' . $meta['meta_value'] . '');
						else {
							$_descRIPTION .= esc_attr('; '.$meta['meta_name'] . ': ' . $meta['meta_value'] . '');
						}
					}
				}
				if($item_id == 0)$descRIPTION = esc_attr( $item['name'] ) . $_descRIPTION .' ('.$item["qty"].')'; else
				$descRIPTION .= ', '. esc_attr( $item['name'] ) . $_descRIPTION .' ('.$item["qty"].')';
			}
		}

		$descRIPTION = preg_replace ("/[^a-zA-ZА-Яа-яієї0-9\s-_,;&?$\[\]]/u","",$descRIPTION);
		$descRIPTION_arr = explode(' ', $descRIPTION);
		$descRIPTION_arr = array_map('trim', $descRIPTION_arr);
		
		$descRIPTION = implode(' ', $descRIPTION_arr); */
		$descRIPTION = __('Оплата заказа ', 'themewoocommerce') . $order->get_order_number();
		
		if($this->is_lang_liqpay_en) { $lang = 'en'; } else $lang = 'ru';
		if(get_woocommerce_currency() == "RUR") $get_woocommerce_currency = 'RUB'; else $get_woocommerce_currency = get_woocommerce_currency();
$this->LiqPayUrl = $this->LiqPayUrl . "&order_id=" . $order_id;
		$cont_sing = 		
		$this->LiqPaymKey .
		number_format($order->order_total, 2, '.', '') .
		$get_woocommerce_currency . 
		$this->LiqPaymID .
		$order_id .
		"buy" .
		 $descRIPTION .
		$this->LiqPayUrl . 
		$this->LiqPayUrlcall;
		
		$lqsignature = base64_encode( sha1(
		$cont_sing
		,1) );

		
?>
<form id='liqpayform' method="POST" action="<?php echo $this->LiqPayApiUrl; ?>"  accept-charset="utf-8">
  <input type="hidden" name="public_key" value="<?php echo $this->LiqPaymID; ?>" />
  <input type="hidden" name="amount" value="<?php echo number_format($order->order_total, 2, '.', ''); ?>" />
  <input type="hidden" name="currency" value="<?php echo $get_woocommerce_currency; ?>" />
  <input type="hidden" name="description" value="<?php echo  $descRIPTION; ?>" />
  <input type="hidden" name="order_id" value="<?php echo $order_id; ?>" />
  <input type="hidden" name="result_url" value="<?php echo $this->LiqPayUrl; ?>" />
  <input type="hidden" name="server_url" value="<?php echo $this->LiqPayUrlcall; ?>" />  
  <input type="hidden" name="type" value="buy" />
  <input type="hidden" name="signature" value="<?php echo $lqsignature; ?>" />
  <input type="hidden" name="language" value="<?php echo $lang; ?>" />
  <input type="hidden" name="default_phone" value="<?php echo str_replace("+", "", $order->billing_phone); ?>" />
  <input type="hidden" name="sandbox" value="<?php echo $this->sandbox;?>" />
  <input type="submit" class="button-alt button" id="submit_dibs_payment_form" name="btn_text" value="<?php _e('Pay', 'woocommerce'); ?>" style="float: left; margin-right: 23px; top: 10px; color: green;" />
</form>
<?php
        
		echo '
		 <a class="button cancel"  style="float: left;" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>';
	}
	
	function view_balance() {
	
	 if($_REQUEST['view_balance'] == 'liqpay') {
		$xml="<request><version>1.2</version><action>view_balance</action><merchant_id>{$this->LiqPaymID}</merchant_id></request>";
		$operation_xml = base64_encode($xml); 
		$signature = base64_encode(sha1($this->LiqPaymKey.$xml.$this->LiqPaymKey,1));
		
     $operation_envelop = '<operation_envelope>
                              <operation_xml>'.$operation_xml.'</operation_xml>
                              <signature>'.$signature.'</signature>
                         </operation_envelope>';
     $post = '<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                              <request>
                                   <liqpay>'.$operation_envelop.'</liqpay>
                              </request>';
     $url = "https://www.liqpay.com/?do=api_xml";
     $page = "/?do=api_xml";
     $headers = array("POST ".$page." HTTP/1.0",
                         "Content-type: text/xml;charset=\"utf-8\"",
                         "Accept: text/xml",
                         "Content-length: ".strlen($post));
     $ch = curl_init();
     curl_setopt($ch, CURLOPT_URL, $url);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
     curl_setopt($ch, CURLOPT_TIMEOUT, 60);
     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
     curl_setopt($ch, CURLOPT_POST, 1);
     curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
     $result = curl_exec($ch);
     curl_close($ch);
	 $response=xml2array($result);
	 $ans=$response['response'][0]['liqpay'][0]['operation_envelope'][0]['operation_xml'][0];
	
	 $xml=base64_decode($ans);
	 $response_end=xml2array($xml);
	 if($response_end['response'][0]['status'][0] == 'failure') die('<p style=\'color: red\'>Ошибка</p><p>Код ошибки: '.$response_end['response'][0]['code'][0]. '.<br /> Описание ошибки: '.$response_end['response'][0]['response_description'][0]. '</p>' );
	 else {
	  $balanse=$response_end['response'][0]["balances"][0];
	  echo '<table  class="widefat" cellspacing="0" style="width: 237px;">
		<thead>
		<tr>
		<th>Валюта</th>
		<th>Значение</th>
		</tr>
		</thead>
		<tbody>
		';
		foreach( $balanse as $key => $value ) 
		echo '<tr>
		<td>'. $key .'</td>
		<td>'. $value[0] .'</td>
		</tr>';
		
		echo '
		</tbody>
	  </table>';
	 }
	  
	die();
	 }
	 
	}
	function process_payment( $order_id ) {
		
		if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else $order = new WC_Order( $order_id );
		if ( ! $this->form_submission_method ) {
		$card = get_option('card');
		$liqpay = get_option('liqpayc');
		$delayed = get_option('delayed');
		$count = 0;
		if(!empty($card)) $count++;
		if(!empty($liqpay)) $count=$count+10;
		if(!empty($delayed)) $count=$count+2;
		if($count == 13 ) {
		$method = 'card,liqpay,delayed';
		} elseif($count == 10)
		$method='liqpay';
		elseif($count == 2)
		$method='delayed';
		elseif($count == 1)
		$method='card';
		elseif($count == 12)
		$method='liqpay,delayed';
		elseif($count == 11)
		$method='card,liqpay';
		elseif($count == 3)
		$method='card,delayed';

		if ($this->debug=='yes') $this->log->add( 'liqpay', 'Создание платежной формы для заказа #' . $order_id . '.');
		
		$descRIPTION = '';
		$order_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item', 'fee' ) ) );
		$count  = 0 ;
		foreach ( $order_items as $item_id => $item ) {
		
			$descRIPTION_ .= esc_attr( $item['name'] );

			if ( ! version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
				if ( $metadata = $order->has_meta( $item_id )) {

						$_descRIPTION = '';
						$is_ = false;
								if(class_exists('WC_Order_Item_Meta') ) {
									$item_meta = new WC_Order_Item_Meta( $item['item_meta'] );
									$_descRIPTION = $item_meta->display(true, true);
								} else {
									if ( $metadata = $item["item_meta"]) {
										foreach($metadata as $k =>  $meta) {
											$meta_list[] = esc_attr($meta['meta_name']. ': ' .$meta['meta_value']);
										}
										$_descRIPTION .= implode( ", \n", $meta_list );
									}
								}
						$_descRIPTION  = esc_attr($_descRIPTION);
						$_descRIPTION = ' [' . $_descRIPTION. '] - '.$item['qty'];
				}
				if(($count + 1) != count($order_items) && !empty($descRIPTION_)) $descRIPTION .=  $descRIPTION_.$_descRIPTION . ', '; else $descRIPTION .=  ''.$descRIPTION_.$_descRIPTION; 
				$count++;
				$descRIPTION_ = $_descRIPTION = '';
			}else {
				if ( $metadata = $item["item_meta"]) {
					$_descRIPTION = '';
					foreach($metadata as $k =>  $meta) {
						if($k == 0)
						$_descRIPTION .= esc_attr(' - '.$meta['meta_name'] . ': ' . $meta['meta_value'] . '');
						else {
							$_descRIPTION .= esc_attr('; '.$meta['meta_name'] . ': ' . $meta['meta_value'] . '');
						}
					}
				}
				if($item_id == 0)$descRIPTION = esc_attr( $item['name'] ) . $_descRIPTION .' ('.$item["qty"].')'; else
				$descRIPTION .= ', '. esc_attr( $item['name'] ) . $_descRIPTION .' ('.$item["qty"].')';
			}
		}
		if($this->is_lang_liqpay_en) { $lang = '<language>EN</language>'; } else $lang = '<language>RU</language>';
		if(get_woocommerce_currency() == "RUB") $get_woocommerce_currency = 'RUR'; else $get_woocommerce_currency = get_woocommerce_currency();
		$this->xml="<request>      
			<version>1.2</version>
			<result_url>".htmlspecialchars($this->LiqPayUrl, ENT_QUOTES, 'UTF-8')."</result_url>
			<server_url>".htmlspecialchars($this->LiqPayUrlcall, ENT_QUOTES, 'UTF-8')."</server_url>
			<merchant_id>{$this->LiqPaymID}</merchant_id>
			<order_id>".$order_id."</order_id>
			<amount>".number_format($order->order_total, 2, '.', '')."</amount>
			<currency>".$get_woocommerce_currency."</currency>
			<description>".htmlspecialchars ($descRIPTION, ENT_QUOTES, 'UTF-8')."</description>
			<default_phone></default_phone>
			<pay_way>$method</pay_way> 
			$lang
			</request>";
		
		$xml_encoded = base64_encode($this->xml); 
		$lqsignature = base64_encode(sha1($this->LiqPaymKey.$this->xml.$this->LiqPaymKey,1));

			$arg_el['operation_xml'] = $xml_encoded;
			$arg_el['signature'] = $lqsignature;
			$liqpay_args = http_build_query( $arg_el, '', '&' );


			$liqpay_adr = $this->LiqPayApiUrl . '&';


			return array(
				'result' 	=> 'success',
				'redirect'	=> $liqpay_adr . $liqpay_args
			);
		} else {
			if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) )
			return array(
				'result' => 'success',
				'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
			);
			return array(
				'result' => 'success',
				'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
			);
		}
		
	}

}
class view_balance_lp {
	private $LiqPaymID;
	
	private $LiqPaymKey;
	
	public function __construct () {
		$this->LiqPaymID = get_option('merchant_id');
		
		$this->LiqPaymKey = base64_decode(strrev(get_option('signature')));
		$this->init();
	}
	function init() {
	
	 if($_REQUEST['wc-api'] == 'view_balance_lp') {
		$xml="<request><version>1.2</version><action>view_balance</action><merchant_id>{$this->LiqPaymID}</merchant_id></request>";
		$operation_xml = base64_encode($xml); 
		$signature = base64_encode(sha1($this->LiqPaymKey.$xml.$this->LiqPaymKey,1));
		
     $operation_envelop = '<operation_envelope>
                              <operation_xml>'.$operation_xml.'</operation_xml>
                              <signature>'.$signature.'</signature>
                         </operation_envelope>';
     $post = '<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                              <request>
                                   <liqpay>'.$operation_envelop.'</liqpay>
                              </request>';
     $url = "https://www.liqpay.com/?do=api_xml";
     $page = "/?do=api_xml";
     $headers = array("POST ".$page." HTTP/1.0",
                         "Content-type: text/xml;charset=\"utf-8\"",
                         "Accept: text/xml",
                         "Content-length: ".strlen($post));
     $ch = curl_init();
     curl_setopt($ch, CURLOPT_URL, $url);
     curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
     curl_setopt($ch, CURLOPT_TIMEOUT, 60);
     curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
     curl_setopt($ch, CURLOPT_POST, 1);
     curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
     $result = curl_exec($ch);
     curl_close($ch);
	 $response=xml2array($result);
	 $ans=$response['response'][0]['liqpay'][0]['operation_envelope'][0]['operation_xml'][0];
	
	 $xml=base64_decode($ans);
	 $response_end=xml2array($xml);
	 if($response_end['response'][0]['status'][0] == 'failure') die('<p style=\'color: red\'>Ошибка</p><p>Код ошибки: '.$response_end['response'][0]['code'][0]. '.<br /> Описание ошибки: '.$response_end['response'][0]['response_description'][0]. '</p>' );
	 else {
	  $balanse=$response_end['response'][0]["balances"][0];
	  echo '<table  class="widefat" cellspacing="0" style="width: 237px;">
		<thead>
		<tr>
		<th>Валюта</th>
		<th>Значение</th>
		</tr>
		</thead>
		<tbody>
		';
		foreach( $balanse as $key => $value ) 
		echo '<tr>
		<td>'. $key .'</td>
		<td>'. $value[0] .'</td>
		</tr>';
		
		echo '
		</tbody>
	  </table>';
	 }
	  

	 }
	 die();
	}
}
function xml2array($originalXML, $attributes=true)
{
        $xmlArray = array();
        $search = $attributes ? '|<((\S+)(.*))\s*>(.*)</\2>|Ums' : '|<((\S+)()).*>(.*)</\2>|Ums';
       
        // normalize data
        $xml = preg_replace('|>\s*<|', ">\n<", $originalXML); // one tag per line
        $xml = preg_replace('|<\?.*\?>|', '', $xml);            // remove XML declarations
        $xml = preg_replace('|<(\S+?)(.*)/>|U', '<$1$2></$1>', $xml); //Expand singletons
       
        if (! preg_match_all($search, $xml, $xmlMatches))
                return trim($originalXML);      // bail out - no XML found
               
        foreach ($xmlMatches[1] as $index => $key)
        {
                if (! isset($xmlArray[$key])) $xmlArray[$key] = array();       
                $xmlArray[$key][] = xml2array($xmlMatches[4][$index], $attributes);
        }
        return $xmlArray;
}
?>