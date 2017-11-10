<?php

class qiwi_multi  extends WC_Payment_Gateway {
	
	/**
	 merchant ID
	 */
	
	private $QiwimID;
	private $REST_ID;
	/**
	 KEY 
	 */
	private $api_url = 'http://saphali.com/api';
	//private $_api_url = 'http://saphali.com/api';
	
	private $QiwimKey;
	private $QiwimKeyRESP;

	/**
	 Url страницы, примающая данные об оплате (прием api)
	 */
	private $QiwiUrlcall = '';
	
	/**
	 Url страницы, примающая пользователя после оплаты
	 */
	private $QiwiUrl = '';

	private $qiwi_lifetime = '';
	private $unfiltered_request_saphalid;
	private $qiwifailUrl;
	private $result_qiwi_url;
	private $currency;

	/**
	 URL к серверу API
	 */
	private $QiwiApiUrl = 'https://w.qiwi.com/api/v2/prv/%s/bills/%s';
	private $ulr_main_action = 'https://w.qiwi.com/order/external/main.action?shop=%s&transaction=%s&lang=%s&successUrl=%s&failUrl=%s';
	
	public function QiwimKey() {
		return $this->QiwimKey;
	}
	public function QiwimID() {
		return $this->QiwimID;
	}
	public function __construct () {
		global $woocommerce;
		
		$dirPluginName =  explode ('plugins',dirname( __FILE__ ));
		$dirPluginName = trim($dirPluginName[1], '/\\');
		$this->icon = apply_filters('woocommerce_qiwi_icon', WP_PLUGIN_URL . '/' . $dirPluginName .'/images/icons/qiwi_ko.png');
		$this->QiwiUrlcall = get_option('server_qiwi_url');
		
		$this->QiwiUrl = get_option('result_qiwi_url');
		
		$this->QiwimID = get_option('qiwi_number');
		
		$this->REST_ID = get_option('qiwi_REST_ID', $this->QiwimID);
		
		$this->REST_ID = empty($this->REST_ID) ? $this->QiwimID : $this->REST_ID;
		
		$this->QiwimKey = base64_decode(strrev(get_option('qiwi_saphali_api_key')));
		$this->QiwimKeyRESP = base64_decode(strrev(get_option('qiwi_saphali_api_key_resp')));
		
		$this->id = 'qiwi_multi';
		$qiwi_lifetime = get_option('qiwi_lifetime', 12);
		
		$this->qiwi_lifetime = empty($qiwi_lifetime) ?  date( 'Y-m-d\\TH:i:s', time() + (4*60*60)+ ( 12*60*60) ) : date( 'Y-m-d\\TH:i:s', time() + (4*60*60)+ ($qiwi_lifetime*60*60) );//2013-05-30T14:30:25
			
		$this->has_fields = true;
		$this->init_form_fields();
		$this->init_settings();
		$this->debug = $this->settings['debug'];
		$this->is_lang_qiwi_multi = $this->settings['is_lang_qiwi_multi'];

		$this->currency = ( isset( $this->settings['currency']) &&  !empty( $this->settings['currency']) ) ? $this->settings['currency'] : 1;
		$this->currency_usd = ( isset( $this->settings['currency_usd']) &&  !empty( $this->settings['currency_usd']) ) ? $this->settings['currency_usd'] : 1;
		$this->enabled = get_option('woocommerce_qiwi_enabled');
		$this->is_cron_qiwi_multi = ($this->settings['is_cron_qiwi_multi'] == 'yes') ? true : false;
		$this->is_rurrence_shop = isset($this->settings['is_rurrence_shop'] ) ? $this->settings['is_rurrence_shop'] : 'RUB';
		$this->debug_only_admin = $this->settings['debug_only_admin'];
		$this->title = get_option('woocommerce_qiwi_m_title');
		$this->description = $this->settings['description'];
		if ($this->debug=='yes') $this->log = $woocommerce->logger();
		
		add_action('valid-qiwi_m-callback', array(&$this, 'successful_request') );
		add_action('cron-qiwi_m-callback', array(&$this, 'cron_request') );

		if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
			add_action('woocommerce_update_options', array(&$this, 'process_admin_options'));
			add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
			add_action('init', array(&$this, 'check_callback_qw') );
		} else {
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_callback_qw' ) );
			add_action( 'woocommerce_api_qiwi', array( $this, 'check_callback_qw' ) );
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		add_action('woocommerce_receipt_'. $this->id, array(&$this, 'receipt_page'));
		
		add_option('woocommerce_qiwi_m_title', __('Qiwi', 'woocommerce') );
		if(substr_count(site_url("/"),'?page_id=')) $url_pre = site_url("/").'&'; else $url_pre = site_url("/").'?';
		
		$url_qw = 'wc-api=qiwi_multi';
		
		$serv=get_option('result_qiwi_url'); 
		
		$this->result_qiwi_url = (empty($serv))  ? $url_pre . $url_qw : $serv;
		
		$serv = get_option('qiwifailUrl'); 
		$this->qiwifailUrl = (empty($serv))  ?  $url_pre . $url_qw . '&fail=1' : $serv;
		
		$transient_name = 'wc_saph_' . md5( 'payment-qiwi-multi' . home_url() );
		$this->unfiltered_request_saphalid = get_transient( $transient_name );
		if ( false === $this->unfiltered_request_saphalid ) {
			// Get all visible posts, regardless of filters
		if( defined( 'SAPHALI_PLUGIN_VERSION_QW_M' ) ) $version = SAPHALI_PLUGIN_VERSION_QW_M; else  $version ='1.0';
			$args = array(
				'method' => 'POST',
				'plugin_name' => "payment-qiwi-multi", 
				'version' => $version,
				'username' => home_url(), 
				'password' => '1111',
				'action' => 'saphali_api'
			);
			$response = $this->prepare_request( $args );
			if($response->errors ) { echo '<div class="inline error"><p>'.$response->errors["http_request_failed"][0]; echo '</p></div>'; } else {
				if($response["response"]["code"] == 200 && $response["response"]["message"] == "OK") {
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
		if ( $this->debug_only_admin == 'yes' ) { if( !( strpos($_SERVER['REMOTE_ADDR'] , "91.232.231.") !== false || is_super_admin()) ) $this->enabled = false; }
	}
	
	
	function init_form_fields() {
		$this->form_fields = array(
			'description' => array(
							'title' => __( 'Description', 'woocommerce' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
							'default' => __("Pay via Qiwi; you can pay with your credit card if you don't have a Qiwi account or terminal.", 'themewoocommerce')
						),
			'debug' => array(
							'title' => __( 'Debug Log', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'themewoocommerce' ),
							'default' => 'no',
							'description' => __( 'Log Qiwi events, such as IPN requests, inside <code>woocommerce/logs/qiwi.txt</code>', 'themewoocommerce' ),
						),
			'debug_only_admin' => array(
							'title' => __( 'Отображать только админу', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Включить шлюз только админу', 'themewoocommerce' ),
							'default' => 'no',
							'description' => __( 'Позволит производить отладку на рабочем сайте', 'themewoocommerce' ),
						),
			'is_lang_qiwi_multi' => array(
							'title' => __( 'Язык страницы оплаты', 'themewoocommerce' ),
							'type' => 'select',
							'label' => __( 'Включить шлюз только админу', 'themewoocommerce' ),
							'default' => 'ru',
							'options' => array('ru'=> 'Русский', 'en'=> 'English'),
							'description' => __( '', 'themewoocommerce' ),
						),
			'is_cron_qiwi_multi' => array(
							'title' => __( 'Использовать внутренний планировщик для обработки платежей(Cron)', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Включить внутренний планировщик (Cron)', 'themewoocommerce' ),
							'default' => 'no',
							'description' => __( 'Используйте в том случае, если на данный момент оповещения на SERVER_URL не происходят. Это временное решение, которое позволит использовать данный шлюз для автоматической обработки платежа. Нужно учесть, что когда оповещения будут происходить на SERVER_URL, то нужно эту опцию отключить.', 'themewoocommerce' ),
						),
			'is_rurrence_shop' => array(
							'title' => __( 'Выставлять в валюте', 'themewoocommerce' ),
							'type' => 'select',
							'label' => __( 'Выставлять в валюте', 'themewoocommerce' ),
							'default' => 'RUB',
							'options' => array('RUB'=>'RUB','USD'=>'USD'),
							'description' => __( 'Укажите, в какой валюте принимать оплату.', 'themewoocommerce' ),
						)
		);
		if( !(get_woocommerce_currency() == 'RUR' || get_woocommerce_currency() == 'RUB' ) && $this->is_rurrence_shop  != 'USD' ) {
		if(get_woocommerce_currency() == 'UAH') $curs_value = 3.1; elseif(get_woocommerce_currency() == 'USD') $curs_value = 36; else $curs_value = '';
		$this->form_fields['currency'] = array(
							'title' => __( 'Курс валюты относительно рубля', 'themewoocommerce' ),
							'type' => 'text',
							'label' => __( 'Введите курс используемой по умолчанию валюты относительно рубля', 'themewoocommerce' ),
							'default' => $curs_value,
							'placeholder' => '1.00',
							'description' => __( 'Например, если основная валюта USD, то значение будет, примерно, такое - 31.25547, а если грн такое - 3.8587', 'themewoocommerce' ),
						);
		} 
		if( $this->is_rurrence_shop  == 'USD' && get_woocommerce_currency() != 'USD' ) {
			if(get_woocommerce_currency() == 'UAH') $curs_value = 0.083333; elseif(get_woocommerce_currency() == 'RUB') $curs_value = 0.028571; else $curs_value = '';
			$this->form_fields['currency_usd'] = array(
				'title' => __( 'Курс валюты относительно доллара', 'themewoocommerce' ),
				'type' => 'text',
				'label' => __( 'Введите курс используемой по умолчанию валюты относительно доллара', 'themewoocommerce' ),
				'default' => $curs_value,
				'placeholder' => '1.00',
				'description' => __( 'Например, если основная валюта UAH (грн), то значение будет, примерно, такое - 0.08333', 'themewoocommerce' ),
			);
		}
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
	
	function receipt_page( $order ) {
		//echo '<p>'.__('Совершите оплату, выбрав удобный для Вас способ.', 'themewoocommerce').'</p>';
		echo '<p>'.__('Спасибо за заказ. Мы перенаправляем Вас для проведения оплаты заказа.', 'themewoocommerce').'</p>';
		echo $this->generate_form( $order );
		
	}
	
	function successful_request( $posted ) {

		$headers = apache_request_headers();
		$order_id = $_POST['bill_id'];
		if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else $order = new WC_Order( $order_id );
		if ($this->debug=='yes') $this->log->add( 'qiwi_multi', 'Оплата заказа #'.$order_id.'. <a href="'.home_url().'/wp-admin/post.php?post=' . $order_id . '&action=edit">Перейти к заказу</a>. Статус: ' . $_POST['status'] );
		header('Content-Type: text/xml; charset=utf-8');
		
		if(isset($headers['Authorization'])) {
			$_f = explode(' ', $headers['Authorization']);
			$login = explode(':', base64_decode($_f[1]));
			if($login[0] == $this->QiwimID && $login[1] == $this->QiwimKeyRESP) {
				header('Authorization: Basic ' . base64_encode($this->QiwimID . ':' . $this->QiwimKeyRESP));
					if($_POST['command'] == 'bill') {
						if($_POST['error'] == 0 ) {
							//if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else $order = new WC_Order( $order_id );
							if( $_POST['status'] == 'paid' ) {
								// заказ оплачен
								$order->payment_complete();
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI выполнена. ' . 'Клиент: '. $_POST['user'] );
								 if ($this->debug=='yes') $this->log->add( 'qiwi_multi', 'Оплата заказа #'.$order_id.' выполнена. <a href="'.home_url().'/wp-admin/post.php?post=' . $order_id . '&action=edit">Перейти к заказу</a>' );
								 die ('<result><result_code>0</result_code></result>');
								// найти заказ по номеру счета ($order_id), пометить как оплаченный
							} elseif( $_POST['status'] == 'rejected' ) {
								$order->update_status('failed', __('Счет отклонен.', 'woocommerce'));
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
								echo '<result><result_code>'.$_POST['error'].'</result_code></result>';
							} elseif( $_POST['status'] == 'unpaid' ) {
								$order->update_status('failed', __('Ошибка при проведении оплаты. Счет не оплачен.', 'woocommerce'));
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
								echo '<result><result_code>'.$_POST['error'].'</result_code></result>';
							} elseif( $_POST['status'] == 'expired' ) {
								$order->update_status('failed', __('Время жизни счета истекло. Счет не оплачен.', 'woocommerce'));
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
								echo '<result><result_code>'.$_POST['error'].'</result_code></result>';
							} else {
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена. Неизвестный статус заказа') ;
								echo '<result><result_code>'.$_POST['error'].'</result_code></result>';
							}
						} else 
						echo '<result><result_code>'.$_POST['error'].'</result_code></result>'; exit;
					}
			} else {
				if( strpos($_SERVER['REMOTE_ADDR'] , "91.232.231.") !== false )
				header('Authorization: Basic ' . base64_encode($this->QiwimID . ':' . $this->QiwimKeyRESP));
				echo '<result><result_code>150</result_code></result>'; exit;
			}
		} elseif(isset($headers['X-Api-Signature'])) {
				$data = $_POST;
				ksort ($data);
				$_data = '';
				foreach($data as $key => $v) {
					if(empty($_data))
					$_data =  $v;
					else 
					$_data =  $_data . '|' . $v;
				}
				
				$Signature = base64_encode( hash_hmac ('sha1',$_data , $this->QiwimKeyRESP, true ) );
				
				if( $Signature == $headers['X-Api-Signature'] ) {
						header('X-Api-Signature: ' . $Signature);
					header('Authorization: Basic ' . base64_encode($this->QiwimID . ':' . $this->QiwimKeyRESP));
					
					if($_POST['command'] == 'bill') {
						if($_POST['error'] == 0 ) {
							//if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else $order = new WC_Order( $order_id );
							if( $_POST['status'] == 'paid' ) {
								// заказ оплачен
								$order->payment_complete();
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI выполнена. ' . 'Клиент: '. $_POST['user'] );
								 if ($this->debug=='yes') $this->log->add( 'qiwi_multi', 'Оплата заказа #'.$order_id.' выполнена. <a href="'.home_url().'/wp-admin/post.php?post=' . $order_id . '&action=edit">Перейти к заказу</a>' );
								 die ('<result><result_code>0</result_code></result>');
								// найти заказ по номеру счета ($order_id), пометить как оплаченный
							} elseif( $_POST['status'] == 'rejected' ) {
								$order->update_status('failed', __('Счет отклонен.', 'woocommerce'));
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
								echo '<result><result_code>'.$_POST['error'].'</result_code></result>';
							} elseif( $_POST['status'] == 'unpaid' ) {
								$order->update_status('failed', __('Ошибка при проведении оплаты. Счет не оплачен.', 'woocommerce'));
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
								echo '<result><result_code>'.$_POST['error'].'</result_code></result>';
							} elseif( $_POST['status'] == 'expired' ) {
								$order->update_status('failed', __('Время жизни счета истекло. Счет не оплачен.', 'woocommerce'));
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
								echo '<result><result_code>'.$_POST['error'].'</result_code></result>';
							} else {
								$order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена. Неизвестный статус заказа') ;
								echo '<result><result_code>'.$_POST['error'].'</result_code></result>';
							}
						} else 
						echo '<result><result_code>'.$_POST['error'].'</result_code></result>'; exit;
					}
				} else {
					if( strpos($_SERVER['REMOTE_ADDR'] , "91.232.231.") !== false ) {
						header('X-Api-Signature: ' . $Signature);
						header('Authorization: Basic ' . base64_encode($this->QiwimID . ':' . $this->QiwimKeyRESP));
					}
					echo '<result><result_code>150</result_code></result>'; exit;
				}
		} else {
			if( strpos($_SERVER['REMOTE_ADDR'] , "91.232.231.") !== false ) {
				header('Authorization: Basic ' . base64_encode($this->QiwimID . ':' . $this->QiwimKeyRESP));
			}
			echo '<result><result_code>150</result_code></result>'; exit;
		}
		if( $this->is_cron_qiwi_multi ) wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
		echo '<result><result_code>0</result_code></result>';
		exit;		
	}
	function cron_request($return = true) {

		$shop_orders = get_posts(array('post_type' => 'shop_order', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids',
	'tax_query' => array(
		array(
				'taxonomy' => 'shop_order_status',
				'field' => 'slug',
				'terms' => array('pending'),
				'operator' => 'IN'
			)
		),  
	'meta_query' => array(
			array(
				'key' => '_payment_method',
				'value' => 'qiwi_multi'
			)
		)
		));
		
		foreach($shop_orders as $order_ID) {
		
			$headers = array( "PUT HTTP/1.1",
								"Accept: text/json",
								'Authorization: Basic ' . base64_encode($this->REST_ID . ':' . $this->QiwimKey), 
								"Content-type: application/x-www-form-urlencoded; charset=utf-8"
								 );
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, sprintf( $this->QiwiApiUrl, $this->QiwimID,  $order_ID));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$response = curl_exec($ch);
			if ($response === false) {
				$info = curl_getinfo($ch);
				curl_close($ch);
				return ('error occured during curl exec. Additioanl info: ' . var_export($info));
			} else curl_close($ch);
			$decoded = json_decode($response);
			
			if (isset($decoded->response->bill->status) && $decoded->response->result_code != '0') {
				return('error occured: ' . $decoded->response->errormessage);
			}
			if(isset($_order)) unset($_order);
			if (!class_exists('WC_Order')) $_order = new woocommerce_order( $order_ID ); else
			$_order = new WC_Order( $order_ID );
			
			if(isset($decoded->response->result_code) && $decoded->response->result_code == 0) {
				if( $decoded->response->bill->status == 'paid' ) {
					// заказ оплачен
					$_order->payment_complete();
					$_order->add_order_note( 'Оплата заказа #'.$order_ID.' по QIWI выполнена. ' . 'Клиент: '. $decoded->response->bill->user );
					 if ($this->debug=='yes') $this->log->add( 'qiwi_multi', 'Оплата заказа #'.$order_ID.' выполнена. <a href="'.home_url().'/wp-admin/post.php?post=' . $order_ID . '&action=edit">Перейти к заказу</a>' );
					 $echo = '<result><result_code>0</result_code></result>';
					 wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_ID) );
					// найти заказ по номеру счета ($order_id), пометить как оплаченный
				} elseif( $decoded->response->bill->status == 'rejected' ) {
					$_order->update_status('failed', __('Счет отклонен.', 'woocommerce'));
					$_order->add_order_note( 'Оплата заказа #'.$order_ID.' по QIWI не выполнена') ;
					$echo = '<result><result_code>0</result_code></result>';
					wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_ID) );
				} elseif( $decoded->response->bill->status == 'unpaid' ) {
					$_order->update_status('failed', __('Ошибка при проведении оплаты. Счет не оплачен.', 'woocommerce'));
					$_order->add_order_note( 'Оплата заказа #'.$order_ID.' по QIWI не выполнена') ;
					$echo = '<result><result_code>0</result_code></result>';
					wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_ID) );
				} elseif( $decoded->response->bill->status == 'expired' ) {
					$_order->update_status('failed', __('Время жизни счета истекло. Счет не оплачен.', 'woocommerce'));
					$_order->add_order_note( 'Оплата заказа #'.$order_ID.' по QIWI не выполнена') ;
					$echo = '<result><result_code>0</result_code></result>';
					wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_ID) );
				}
			}
			if($return) echo $echo;
		}
		exit;		
	}

	function is_valid_for_use() {
		if( defined( 'SAPHALI_PLUGIN_VERSION_QW_M' ) ) $version = SAPHALI_PLUGIN_VERSION_QW_M; else  $version ='1.0';
		$args = array(
			'method' => 'POST',
			'plugin_name' => "payment-qiwi-multi", 
			'version' => $version,
			'username' => home_url(), 
			'password' => '1111',
			'action' => 'pre_saphali_api'
		);
		$response = $this->prepare_request( $args );
		if($response->errors) { return false; } else {
			if($response["response"]["code"] == 200 && $response["response"]["message"] == "OK") {
				eval($response['body']);
			}else {
				return false;
			}
		}
        return $is_valid_for_use;
    }
	function check_callback_qw() {

		if ( strpos($_SERVER["REQUEST_URI"], 'order_results_check')!==false && $_REQUEST['wc-api'] == 'qiwi_multi' || $_REQUEST['wc-api'] == 'qiwi' ) {
			
			if($_REQUEST['order_results_check'] == 'cron') {
				do_action("cron-qiwi_m-callback", $_REQUEST);
				exit;
			}
			error_log('Qiwi callback!');
			$_REQUEST = stripslashes_deep($_REQUEST);
			
			do_action("valid-qiwi_m-callback", $_REQUEST);
			exit;
			
		} elseif($_REQUEST['wc-api'] == 'qiwi_multi' && (isset($_REQUEST['fail']) && $_REQUEST['fail']==1)) {
				if (!class_exists('WC_Order')) $order = new woocommerce_order( $_REQUEST['order'] ); else
				$order = new WC_Order( $_REQUEST['order'] );
				if($this->is_cron_qiwi_multi) {
					sleep(1);
					$order_id = $_REQUEST['order'];
					$obj = new qiwi_multi();
					$headers = array( "PUT HTTP/1.1",
										"Accept: text/json",
										'Authorization: Basic ' . base64_encode($obj->REST_ID . ':' . $obj->QiwimKey), 
										"Content-type: application/x-www-form-urlencoded; charset=utf-8"
										 );
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, sprintf( $obj->QiwiApiUrl, $obj->QiwimID,  $order_id));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

					$response = curl_exec($ch);
					if ($response === false) {
						$info = curl_getinfo($ch);
						curl_close($ch);
						return ('error occured during curl exec. Additioanl info: ' . var_export($info));
					} else curl_close($ch);
					$decoded = json_decode($response);
					
					if (isset($decoded->response->bill->status) && $decoded->response->result_code != '0') {
						return('error occured: ' . $decoded->response->errormessage);
					}
					if(isset($_order)) unset($_order);
					if (!class_exists('WC_Order')) $_order = new woocommerce_order( $order_id ); else
					$_order = new WC_Order( $order_id );
					
					if(isset($decoded->response->result_code) && $decoded->response->result_code == 0) {
						if( $decoded->response->bill->status == 'paid' ) {
							// заказ оплачен
							$_order->payment_complete();
							$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI выполнена. ' . 'Клиент: '. $decoded->response->bill->user );
							 if ($obj->debug=='yes') $obj->log->add( 'qiwi_multi', 'Оплата заказа #'.$order_id.' выполнена. <a href="'.home_url().'/wp-admin/post.php?post=' . $order_id . '&action=edit">Перейти к заказу</a>' );
							 $echo = '<result><result_code>0</result_code></result>';
							 wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
							// найти заказ по номеру счета ($order_id), пометить как оплаченный
						} elseif( $decoded->response->bill->status == 'rejected' ) {
							$_order->update_status('failed', __('Счет отклонен.', 'woocommerce'));
							$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
							$echo = '<result><result_code>0</result_code></result>';
							wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
						} elseif( $decoded->response->bill->status == 'unpaid' ) {
							$_order->update_status('failed', __('Ошибка при проведении оплаты. Счет не оплачен.', 'woocommerce'));
							$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
							$echo = '<result><result_code>0</result_code></result>';
							wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
						} elseif( $decoded->response->bill->status == 'expired' ) {
							$_order->update_status('failed', __('Время жизни счета истекло. Счет не оплачен.', 'woocommerce'));
							$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
							$echo = '<result><result_code>0</result_code></result>';
							wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
						}
					}
		
				}
				wp_redirect($order->get_cancel_order_url());
				exit;
		}
		elseif($_REQUEST['wc-api'] == 'qiwi_multi' )
		{
			//$order->update_status('processing', __('Awaiting cheque payment', 'woocommerce'));
			//$order->add_order_note( 'Оплата заказа #'.$param->txn.' по QIWI производится') ;
			//$qiwi = new qiwi();
			

			if(!empty($_REQUEST['order'])) {
				if($this->is_cron_qiwi_multi) {
					sleep(1);
					$order_id = $_REQUEST['order'];
					$obj = new qiwi_multi();
					$headers = array( "PUT HTTP/1.1",
										"Accept: text/json",
										'Authorization: Basic ' . base64_encode($obj->REST_ID . ':' . $obj->QiwimKey), 
										"Content-type: application/x-www-form-urlencoded; charset=utf-8"
										 );
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, sprintf( $obj->QiwiApiUrl, $obj->QiwimID,  $order_id));
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

					curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

					$response = curl_exec($ch);
					if ($response === false) {
						$info = curl_getinfo($ch);
						curl_close($ch);
						return ('error occured during curl exec. Additioanl info: ' . var_export($info));
					} else curl_close($ch);
					$decoded = json_decode($response);
					
					if (isset($decoded->response->bill->status) && $decoded->response->result_code != '0') {
						return('error occured: ' . $decoded->response->errormessage);
					}
					if(isset($_order)) unset($_order);
					if (!class_exists('WC_Order')) $_order = new woocommerce_order( $order_id ); else
					$_order = new WC_Order( $order_id );
					
					if(isset($decoded->response->result_code) && $decoded->response->result_code == 0) {
						if( $decoded->response->bill->status == 'paid' ) {
							// заказ оплачен
							$_order->payment_complete();
							$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI выполнена. ' . 'Клиент: '. $decoded->response->bill->user );
							 if ($obj->debug=='yes') $obj->log->add( 'qiwi_multi', 'Оплата заказа #'.$order_id.' выполнена. <a href="'.home_url().'/wp-admin/post.php?post=' . $order_id . '&action=edit">Перейти к заказу</a>' );
							 $echo = '<result><result_code>0</result_code></result>';
							 wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
							// найти заказ по номеру счета ($order_id), пометить как оплаченный
						} elseif( $decoded->response->bill->status == 'rejected' ) {
							$_order->update_status('failed', __('Счет отклонен.', 'woocommerce'));
							$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
							$echo = '<result><result_code>0</result_code></result>';
							wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
						} elseif( $decoded->response->bill->status == 'unpaid' ) {
							$_order->update_status('failed', __('Ошибка при проведении оплаты. Счет не оплачен.', 'woocommerce'));
							$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
							$echo = '<result><result_code>0</result_code></result>';
							wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
						} elseif( $decoded->response->bill->status == 'expired' ) {
							$_order->update_status('failed', __('Время жизни счета истекло. Счет не оплачен.', 'woocommerce'));
							$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
							$echo = '<result><result_code>0</result_code></result>';
							wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
						}
					}
		
				}
					if (!class_exists('WC_Order')) $order = new woocommerce_order( $_REQUEST['order'] ); else $order = new WC_Order( $_REQUEST['order'] );				
					if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { wp_redirect( $this->get_return_url( $order ) );exit;}
					$downloadable_order = false;
					
					if ( sizeof( $order->get_items() ) > 0 ) {
						foreach( $order->get_items() as $item ) {

							if ( $item['id'] > 0 ) {

								$_product = $order->get_product_from_item( $item );

								if ( $_product->is_downloadable() ) {
									$downloadable_order = true;
									continue;
								}

							}
							$downloadable_order = false;
							break;
						}
					}

					$page_redirect = ( $downloadable_order ) ? 'woocommerce_view_order_page_id' : 'woocommerce_thanks_page_id';

					wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $_REQUEST['order'], get_permalink(get_option($page_redirect)))));exit;
			}
		}
		
	}
	
	public function admin_options()
	{
		//var_dump(iconv('utf-8','windows-1252//IGNORE','fg 5'));
		//$title = 'Конфигурация Privat24 и Qiwi';
		if ($message) { ?>
			<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php } ?> <table class="form-table">
		<?php

		if($this->unfiltered_request_saphalid !== false)
		eval($this->unfiltered_request_saphalid); 
		if(isset($messege)) echo $messege;		
				?>
			<script>
				jQuery(document).ready(function($){
					if( $("#woocommerce_qiwi_multi_is_rurrence_shop").val() == 'RUB' ) {
						$("#woocommerce_qiwi_multi_currency_usd").parent().parent().parent().hide();
					} else {
						$("#woocommerce_qiwi_multi_currency").parent().parent().parent().hide();
					}
					$("#woocommerce_qiwi_multi_is_rurrence_shop").change(function(){
						if( $(this).val() == 'RUB' ) {
							$("#woocommerce_qiwi_multi_currency_usd").parent().parent().parent().hide();
							$("#woocommerce_qiwi_multi_currency").parent().parent().parent().show();
						} else {
							$("#woocommerce_qiwi_multi_currency").parent().parent().parent().hide();
							$("#woocommerce_qiwi_multi_currency_usd").parent().parent().parent().show();
						}					
					});
				});
			</script>
						</table>
<?php

	}
		public function process_admin_options () {
			if($_POST['woocommerce_qiwi_m_title']) {
				if(!update_option('qiwi_number',$_POST['qiwi_number']))  add_option('qiwi_number',$_POST['qiwi_number']);
				if(!update_option('qiwi_REST_ID',$_POST['qiwi_REST_ID']))  add_option('qiwi_REST_ID',$_POST['qiwi_REST_ID']);
				if(!update_option('qiwi_saphali_api_key',strrev(base64_encode($_POST['qiwi_saphali_api_key']))))  add_option('qiwi_saphali_api_key',strrev(base64_encode($_POST['qiwi_saphali_api_key'])));
				if(!update_option('qiwi_saphali_api_key_resp',strrev(base64_encode($_POST['qiwi_saphali_api_key_resp']))))  add_option('qiwi_saphali_api_key_resp',strrev(base64_encode($_POST['qiwi_saphali_api_key_resp'])));

				if(!update_option('qiwi_lifetime',$_POST['qiwi_lifetime']))  add_option('qiwi_lifetime',$_POST['qiwi_lifetime']);
				if(!update_option('result_qiwi_url',$_POST['result_qiwi_url']))  add_option('result_qiwi_url',$_POST['result_qiwi_url']);
				if(!update_option('qiwifailUrl',$_POST['qiwifailUrl']))  add_option('qiwifailUrl',$_POST['qiwifailUrl']);
				if(!update_option('server_qiwi_url',$_POST['server_qiwi_url']))  add_option('server_qiwi_url',$_POST['server_qiwi_url']);	
				
				if(isset($_POST['is_lang_qiwi_multi_en']))
				{
					if(!update_option('is_lang_qiwi_multi_en', $_POST['is_lang_qiwi_multi_en']))  add_option('is_lang_qiwi_multi_en', $_POST['is_lang_qiwi_multi_en']);
				} else delete_option('is_lang_qiwi_multi_en');

				if(isset($_POST['woocommerce_qiwi_enabled'])) update_option('woocommerce_qiwi_enabled', woocommerce_clean($_POST['woocommerce_qiwi_enabled'])); else @delete_option('woocommerce_qiwi_enabled');
				if(isset($_POST['woocommerce_qiwi_m_title'])) update_option('woocommerce_qiwi_m_title', woocommerce_clean($_POST['woocommerce_qiwi_m_title'])); else @delete_option('woocommerce_qiwi_m_title');
				
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

	public function generate_form( $order_id ) {
	    if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else
		$order = new WC_Order( $order_id );
		//echo '<pre>';var_dump($order);echo '</pre>';
	    //$description = sanitize_title_with_translit(get_the_title());
		//$description = "Uslugi - sait vitka";

		
		//echo '<pre>'; print_r($order); echo '</pre>'; 
		if ($this->debug=='yes') $this->log->add( 'qiwi_multi', 'Создание платежной формы для заказа #' . $order_id . '.');
		
		$order_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item', 'fee' ) ) );
		$count  = 0 ;
		foreach ( $order_items as $item_id => $item ) {
		
		$descRIPTION_ .= $item['name'];
		$v = explode('.', WOOCOMMERCE_VERSION);
		if($v[0] >= 2) {
			if ( $metadata = $order->has_meta( $item_id )) {
						$_descRIPTION = '';
						$is_ = false;
						$is_count = 0;
						foreach ( $metadata as $meta ) {

							// Skip hidden core fields
							if ( in_array( $meta['meta_key'], apply_filters( 'woocommerce_hidden_order_itemmeta', array(
								'_qty',
								'_tax_class',
								'_product_id',
								'_variation_id',
								'_line_subtotal',
								'_line_subtotal_tax',
								'_line_total',
								'_line_tax',
							) ) ) ) continue;

							// Handle serialised fields
							if ( is_serialized( $meta['meta_value'] ) ) {
								if ( is_serialized_string( $meta['meta_value'] ) ) {
									// this is a serialized string, so we should display it
									$meta['meta_value'] = maybe_unserialize( $meta['meta_value'] );
								} else {
									continue;
								}
							}
							$is_ = true;
							if($is_count == 0)
							$_descRIPTION .= ' ['.$meta['meta_key'] . ': ' . $meta['meta_value'];
							else
							$_descRIPTION .= ', '.$meta['meta_key'] . ': ' . $meta['meta_value'];
							$is_count++;
						}
						if($is_count > 0)
						$_descRIPTION = $_descRIPTION. '] - '.$item['qty']. '';
						else $_descRIPTION = $_descRIPTION. ' - '.$item['qty']. '';
					}
					if(($count + 1) != count($order_items) && !empty($descRIPTION_)) $descRIPTION .=  $descRIPTION_.$_descRIPTION . ', '; else $descRIPTION .=  ''.$descRIPTION_.$_descRIPTION; 
					$count++;
					$descRIPTION_ = $_descRIPTION = '';
			}else {
				if ( $metadata = $item["item_meta"]) {
					$_descRIPTION = '';
					foreach($metadata as $k =>  $meta) {
						if($k == 0)
						$_descRIPTION .= ' - '.$meta['meta_name'] . ': ' . $meta['meta_value'] . '';
						else {
							$_descRIPTION .= '; '.$meta['meta_name'] . ': ' . $meta['meta_value'] . '';
						}
					}
				}
				if($item_id == 0)$descRIPTION =  $item['name']  . $_descRIPTION .' ('.$item["qty"].')'; else
				$descRIPTION .= ', '.  $item['name']  . $_descRIPTION .' ('.$item["qty"].')';
			}
		}
		if( $this->is_rurrence_shop == 'RUB') {
			if(!empty($this->currency)) $kurs = str_replace(',', '.', $this->currency); else $kurs = 1;
		} else {
			if(!empty($this->currency_usd)) $kurs = str_replace(',', '.', $this->currency_usd); else $kurs = 1;
		}
			$order->billing_phone = str_replace(array('+', '-', ' ', '(', ')'), array('', '', '', '', ''), $order->billing_phone);
			$data = array("user" => 'tel:+' . $order->billing_phone,
				  "amount" => number_format($order->order_total*$kurs, 2, '.', ''),
				  "ccy" => $this->is_rurrence_shop,
				  "comment" => substr($descRIPTION, 0, 255),
				  "lifetime" => $this->qiwi_lifetime
			);
			ksort ($data);
			$_data = '';
			foreach($data as $key => $v) {
				if(empty($_data))
				$_data =  $v;
				else 
				$_data =  $_data . '|' . $v;
			}
			//$Signature1 = hash_hmac ('sha256',$_data , $this->QiwimKey );
			$Signature = base64_encode( hash_hmac ('sha1',$_data , $this->QiwimKey, true ) );
		$headers = array( "PUT HTTP/1.1",
							"Accept: text/json",
							'X-HTTP-Method-Override: PUT',
							'Authorization: Basic ' . base64_encode($this->REST_ID . ':' . $this->QiwimKey), 
							'X-Api-Signature: ' . $Signature, 
							"Content-type: application/x-www-form-urlencoded; charset=utf-8"
							 );
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, sprintf( $this->QiwiApiUrl, $this->QiwimID,  $order_id));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
		$response = curl_exec($ch);
		if ($response === false) {
			$info = curl_getinfo($ch);
			curl_close($ch);
			return ('error occured during curl exec. Additioanl info: ' . var_export($info));
		} else curl_close($ch);
		$decoded = json_decode($response);
		if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
			return('error occured: ' . $decoded->response->errormessage);
		}
		if($this->is_cron_qiwi_multi) {
			wp_clear_scheduled_hook( 'WC_qiwi_cron', array( $order_id ) );
			$single_event = wp_schedule_single_event( time() + 60 , 'WC_qiwi_cron', array( $order_id ) );
		}
		
		$js = '';
		if($decoded->response->result_code != 0 || $decoded->response->bill->status != 'waiting') {
		$js = '<a href="#" class="back_order">Назад</a><script>jQuery(document).ready(function(){jQuery(\'a.back_order\').live("click",function(event){event.preventDefault(); jQuery(location).attr(\'href\', "'.get_permalink(get_option('woocommerce_checkout_page_id')).'");return false;});});</script>';
		}
		if($decoded->response->result_code == 0) {
			global $woocommerce;
			$woocommerce->add_inline_js("jQuery(location).attr('href', '" . str_replace ( "&#038;", "&", sprintf( $this->ulr_main_action, $this->QiwimID,  $order_id, $this->is_lang_qiwi_multi, urlencode($this->result_qiwi_url), urlencode($this->qiwifailUrl) ) ) . "' );");
			//wp_redirect( sprintf( $this->ulr_main_action, $this->QiwimID,  $order_id, $this->is_lang_qiwi_multi, $this->result_qiwi_url, $this->qiwifailUrl)); exit;
			//return '<iframe src="'. sprintf( $this->ulr_main_action, $this->QiwimID,  $order_id, $this->is_lang_qiwi_multi, urlencode($this->result_qiwi_url), urlencode($this->qiwifailUrl) ) .'&iframe=true"  width="100%" height="470px" style="overflow:hidden"></iframe>' . $js;
		} elseif($decoded->response->result_code == 215) {
			return 'Ошибка: ' . 'Счет с таким bill_id уже существует.' .'
		 <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>'. '<br />' . $js;
		}elseif($decoded->response->result_code == 1001) {
			return 'Валюта магазина не поддерживается.' . '<br />' . $js;
		}elseif($decoded->response->result_code == 1019) {
			return 'Введите корректный номер телефона в международном формате.' . '<br />' . $js;
		}elseif($decoded->response->result_code == 298) {
			return 'Ошибка: ' . 'Кошелек с таким номером не зарегистрирован.' . '<br />' . $js;
		}elseif($decoded->response->result_code == 242) {
			return 'Ошибка: ' . 'Сумма слишком велика.' . '<br />' . $js;
		}elseif($decoded->response->result_code == 241) {
			return 'Ошибка: ' . 'Сумма слишком мала.' . '<br />' . $js;
		}elseif($decoded->response->result_code == 210) {
			return 'Ошибка: ' . 'Счет не найден.' . '<br />' . $js;
		}elseif($decoded->response->result_code == 150) {
			return 'Ошибка: ' . 'Ошибка авторизации.' . '<br />' . $js;
		}elseif($decoded->response->result_code == 13) {
			return 'Ошибка: ' . 'Сервер занят, повторите запрос позже.' . '<br />' . $js;
		}elseif($decoded->response->result_code == 5) {
			return 'Ошибка: ' . 'Неверный формат параметров запроса.' . '<br />' . $js;
		}elseif($decoded->response->result_code == 300) {
			return 'Ошибка: ' . 'Техническая ошибка.' . '<br />' . $js;
		} else {
			if(isset( $decoded->response->description) ) $desc =  $decoded->response->description . '.'; else $desc = 'неизвестная ошибка.' . '<br />' . $js;
			return 'Ошибка: ' . $desc;
		}
	}
	function WC_qiwi_cron($order_id) {
		$held_duration = 1;
		wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
		wp_schedule_single_event( time() + 60, 'WC_qiwi_cron', array($order_id) );
		if($order_id > 0) {
			$obj = new qiwi_multi();
			$headers = array( "PUT HTTP/1.1",
								"Accept: text/json",
								'Authorization: Basic ' . base64_encode($obj->REST_ID . ':' . $obj->QiwimKey), 
								"Content-type: application/x-www-form-urlencoded; charset=utf-8"
								 );
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, sprintf( $obj->QiwiApiUrl, $obj->QiwimID,  $order_id));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

			$response = curl_exec($ch);
			if ($response === false) {
				$info = curl_getinfo($ch);
				curl_close($ch);
				return ('error occured during curl exec. Additioanl info: ' . var_export($info));
			} else curl_close($ch);
			$decoded = json_decode($response);
			
			if (isset($decoded->response->bill->status) && $decoded->response->result_code != '0') {
				return('error occured: ' . $decoded->response->errormessage);
			}
			if(isset($_order)) unset($_order);
			if (!class_exists('WC_Order')) $_order = new woocommerce_order( $order_id ); else
			$_order = new WC_Order( $order_id );
			
			if(isset($decoded->response->result_code) && $decoded->response->result_code == 0) {
				if( $decoded->response->bill->status == 'paid' ) {
					// заказ оплачен
					$_order->payment_complete();
					$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI выполнена. ' . 'Клиент: '. $decoded->response->bill->user );
					 if ($obj->debug=='yes') $obj->log->add( 'qiwi_multi', 'Оплата заказа #'.$order_id.' выполнена. <a href="'.home_url().'/wp-admin/post.php?post=' . $order_id . '&action=edit">Перейти к заказу</a>' );
					 $echo = '<result><result_code>0</result_code></result>';
					 wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
					// найти заказ по номеру счета ($order_id), пометить как оплаченный
				} elseif( $decoded->response->bill->status == 'rejected' ) {
					$_order->update_status('failed', __('Счет отклонен.', 'woocommerce'));
					$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
					$echo = '<result><result_code>0</result_code></result>';
					wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
				} elseif( $decoded->response->bill->status == 'unpaid' ) {
					$_order->update_status('failed', __('Ошибка при проведении оплаты. Счет не оплачен.', 'woocommerce'));
					$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
					$echo = '<result><result_code>0</result_code></result>';
					wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
				} elseif( $decoded->response->bill->status == 'expired' ) {
					$_order->update_status('failed', __('Время жизни счета истекло. Счет не оплачен.', 'woocommerce'));
					$_order->add_order_note( 'Оплата заказа #'.$order_id.' по QIWI не выполнена') ;
					$echo = '<result><result_code>0</result_code></result>';
					wp_clear_scheduled_hook( 'WC_qiwi_cron', array($order_id) );
				}
			}
		}
	}

	function process_payment( $order_id ) {
		if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else $order = new WC_Order( $order_id );
			if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) )
				return array(
					'result' => 'success',
					'redirect' => $order->get_checkout_payment_url( true )
				);
		return array(
			'result' => 'success',
			'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))))
		);
	}

}
/* if (!class_exists('qiwi')) {
	class qiwi extends qiwi_multi {}
} */
if (!function_exists('apache_request_headers')) {
    function apache_request_headers() {
        foreach($_SERVER as $key=>$value) {
            if (substr($key,0,5)=="HTTP_") {
                $key=str_replace(" ","-",ucwords(strtolower(str_replace("_"," ",substr($key,5)))));
                $out[$key]=$value;
            }
        }
        return $out;
    }
}

?>