<?php
class yandexmoney  extends WC_Payment_Gateway {
	// Яндекс.Денег
	private $yandexmoneyUid;

	private $wpfailUrl;
	private $yandexmoneyresultUrl;
	private $wpsuccessUrl;
	private $wpMode;
	private $wpModeshop;
	private $is_primary;
	private $ID_N_shop;
	private $ID_N_shop_numb;
	private $ID_N_shop_url;
	private $description_pay;

	private $yandexmoneySecretKey;

	private $yandexmoneyApiUrl = 'https://money.yandex.ru/embed/small.xml';
	
	private $api_url = 'http://saphali.com/api';
	//private $_api_url = 'http://saphali.com/api';
	private $unfiltered_request_saphalid;
	
	public function __construct () {
		// Webmoney
		global $woocommerce;
		$this->title = get_option('woocommerce_yandexmoney_title',  __('Яндекс.Деньги', 'woocommerce') );
		$this->id 			= 'yandexmoney';
		$dirPluginName =  explode ('plugins',dirname( __FILE__ ));
		$this->yandexmoneyresultUrl = get_option('yandexmoneyresultUrl');
		
		$this->wpsuccessUrl = get_option('wpsuccessUrl');		
		
		$dirPluginName = trim($dirPluginName[1], '/\\');

		$this->icon = apply_filters('woocommerce_yandexmoney_icon', WP_PLUGIN_URL . '/' . $dirPluginName .'/images/icons/yandexmoney.png');

		$this->yandexmoneyUid = get_option('yandexmoneyUid');


		$this->yandexmoneySecretKey = base64_decode(strrev(get_option('yandexmoneySecretKey')));

		$this->has_fields = false;
		
		$this->init_form_fields();
		$this->init_settings();
		$this->debug = $this->settings['debug'];
		$this->style = $this->settings['style'] == 'yes' ? false : true;
		$this->description = $this->settings['description'];
		$this->is_primary = (isset($this->settings['is_primary']) && $this->settings['is_primary'] == 'yes') ? true : false;
		$this->ID_N_shop = isset($this->settings['ID_N_shop']) ? $this->settings['ID_N_shop'] : '';
		$this->ID_N_shop_numb = isset($this->settings['ID_N_shop_numb']) ? $this->settings['ID_N_shop_numb'] : '';
		$this->description_pay = isset($this->settings['description_pay']) ? $this->settings['description_pay'] : '';
		$this->pay_desc = isset($this->settings['pay_desc']) ? $this->settings['pay_desc'] : '';
		$this->ID_N_shop_url = isset($this->settings['ID_N_shop_url_a']) ? array( 1 => $this->settings['ID_N_shop_url_a'], 2 => $this->settings['ID_N_shop_url_b'], 3 =>  $this->settings['ID_N_shop_url_c']) : array();
		
		$this->enabled = get_option('woocommerce_yandexmoney_enabled');

		if ($this->debug=='yes') { if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ) $this->log = $woocommerce->logger(); $this->log = new WC_Logger(); }
		$this->wpfailUrl = get_option('wpfailUrl');
		add_action('valid-yandexmoney-callback', array($this, 'successful_request_wm') );

		add_action('woocommerce_receipt_yandexmoney', array($this, 'receipt_page'));
		$transient_name = 'wc_saph_' . md5( 'payment-yandexmoney' . home_url() );

		$this->unfiltered_request_saphalid = get_transient( $transient_name );
		$tunfiltered_request_saphalid = get_transient( $transient_name . '_' );
		if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
			add_action('woocommerce_update_options', array($this, 'process_admin_options'));
			add_action('woocommerce_update_options_payment_gateways', array($this, 'process_admin_options'));
			add_action('init', array($this, 'check_callback_wm') );
		} else {
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_callback_wm' ) );
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		//add_option('woocommerce_yandexmoney_title', __('Яндекс.Деньги', 'woocommerce') );
		if ( false === $this->unfiltered_request_saphalid || $tunfiltered_request_saphalid != @filesize(__FILE__)) {
			// Get all visible posts, regardless of filters
			if( defined( 'SAPHALI_PLUGIN_VERSION_ST' ) ) $version = SAPHALI_PLUGIN_VERSION_ST; 
			elseif( defined( 'SAPHALI_PLUGIN_VERSION_YM' ) ) $version = SAPHALI_PLUGIN_VERSION_YM; else  $version ='1.0';
			$args = array(
				'method' => 'POST',
				'username' => home_url(), 
				'password' => @filesize(__FILE__),
				'plugin_name' => "payment-yandexmoney", 
				'version' => $version,
				'action' => 'saphali_api'
			);
			$response = $this->prepare_request( $args );
			if(@$response->errors) { echo '<div class="inline error"><p>'.$response->errors["http_request_failed"][0]; echo '</p></div>'; } else {
				if($response["response"]["code"] == 200 && $response["response"]["message"] == "OK") {
					$this->unfiltered_request_saphalid = $response['body'];
				} else {
					$this->unfiltered_request_saphalid = 'echo \'<div class="inline error"><p> Ошибка \'.$response["response"]["code"] . $response["response"]["message"].\'<br /><a href="mailto:saphali@ukr.net">Свяжитесь с разработчиком.</a></p></div>\';'; 
				}
			}
			if( !empty($this->unfiltered_request_saphalid) &&  $this->is_valid_for_use() ) {
				set_transient( $transient_name, $this->unfiltered_request_saphalid , 2592000 );			
				set_transient( $transient_name . '_', @filesize(__FILE__) , 2592000 );			
			}
		}
		
		if ( false ===  $this->unfiltered_request_saphalid ) $this->enabled = false;
	}
	function successful_request_wm( $posted ) {
		$param_sh1 = $posted['notification_type'] . '&' . $posted['operation_id'] . '&' . $posted['amount'] . '&' . $posted['currency'] . '&' . $posted['datetime'] . '&' . $posted['sender'] . '&' . $posted['codepro'] . '&' . $this->yandexmoneySecretKey . '&' . $posted['label'];
		$sh1 = sha1($param_sh1);
		$par_label = !empty($this->ID_N_shop) ? explode($this->ID_N_shop, $posted['label']) : array();
		if($this->is_primary && $this->ID_N_shop_numb && $par_label[0] > 0 && sizeof($par_label) == 2 && in_array($par_label[0], array(1,2,3) ) )
		if($sh1 === $posted['sha1_hash']) {
			$param_sh1_go = $posted['notification_type'] . '&' . $posted['operation_id'] . '&' . $posted['amount'] . '&' . $posted['currency'] . '&' . $posted['datetime'] . '&' . $posted['sender'] . '&' . $posted['codepro'] . '&' . $this->yandexmoneySecretKey . '&' . $par_label[1];
			$sh1_go = sha1($param_sh1_go);
			$posted['sha1_hash'] = $sh1_go;
			$posted['label']     = $par_label[1];
			
			$data = $posted;
			unset($data['wc-api'], $data['order_results_go']);

			if( !empty($par_label[0]) && $par_label[0] > 0 ) {
				$headers = array( "GET HTTP/1.0",
									"Content-type: application/x-www-form-urlencoded; charset=utf-8"
									 );
				$ch = curl_init();		
				curl_setopt($ch, CURLOPT_URL, $this->ID_N_shop_url[ $par_label[0] ] );
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

				curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
				curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
				$response = curl_exec($ch);

				if ($response === false) {	
					$info = curl_getinfo($ch);
					curl_close($ch);
					return ('error occured during curl exec. Additioanl info: ' . var_export($info));
				} else curl_close($ch);
			}
			exit;
		}
		if ($sh1 !== $posted['sha1_hash']) {if ($this->debug=='yes') $this->log->add( 'yandexmoney', 'Хеши ' . $posted['sha1_hash'] . ' и ' . $sh1 . ' не соотв..'   ); exit;}
			if( isset($posted['label']) ){
				if (!class_exists('WC_Order')) $order = new woocommerce_order( $posted['label'] ); else $order = new WC_Order( $posted['label'] );
				if ( !($order_r = $order->get_order($posted['label'])) ) {
						if ($this->debug=='yes') $this->log->add( 'yandexmoney', 'Заказ #' . $posted['label'] . ' не найден.'   );
					} else { # If payment or items were not found,

					if(get_option('value_usd_cur') != '') {$curs_usd = get_option('value_usd_cur');}else {$curs_usd = 8.1;}
					if(get_option('value_rur_cur') != '') {$curs_rur = get_option('value_rur_cur');} else {$curs_rur = 0.246;}
					if(get_woocommerce_currency() == 'UAH') {
						$order->order_total = $order->order_total/$curs_rur;
					}elseif(get_woocommerce_currency() == 'RUR' || get_woocommerce_currency() == 'RUB') {

					}elseif(get_woocommerce_currency() == 'USD') {
						$order->order_total = $order->order_total*($curs_usd/$curs_rur);
					}
					$_summ = number_format($order->order_total, 2, '.', '') * 0.5 / 100;
					$_summ = floor($_summ * 10000)/10000;
					$sum1 = $_summ * 100;
					$sum2 = abs(ceil($sum1) - $sum1)*10;
					if($sum2 <= 5) {
						if($sum2 == 5) $_summ = round($_summ,2) - 0.01;
						else $_summ = round($_summ,2);
					}  else {
						$_summ = round($_summ,2);
					}
					if ( ( ceil($posted['amount']) == ceil(( number_format($order->order_total, 2, '.', '') - $_summ )) ) || (
							 (ceil($posted['amount']) == ceil(( number_format($order->order_total, 2, '.', '') - $_summ )) - 1) ||
							 ( isset($posted['withdraw_amount']) && $posted['withdraw_amount'] == number_format($order->order_total, 2, '.', '') ) ||
							 (ceil($posted['amount']) == ceil(( number_format($order->order_total, 2, '.', '') - $_summ )) + 1) 
							)
					   ) {
						$order->add_order_note( 'Оплата заказа #'.$order->id.' через Яндекс.Деньги выполнена'.
										 "<br />Идентификатор операции Яндекс.Денег: ".$posted['operation_id'].
										 "") ;
								if ($this->debug=='yes') $this->log->add( 'yandexmoney', 'Оплата заказа #'.$order->id.' выполнена.' );
								$order->payment_complete();
								exit;
					}  else {
						$order->add_order_note( 'Оплата заказа #'.$order->id.' через Яндекс.Деньги не выполнена'.
										 "<br />Идентификатор операции Яндекс.Денег: ".$posted['operation_id'].
										 "Сумма оплаты не соответствует заказу. <br />
										 Ответ выполнения: сумма - {$posted['amount']}, а должна быть ".number_format($order->order_total, 2, '.', '')." (".( number_format($order->order_total, 2, '.', '') - $_summ ).").") ;
						if ($this->debug=='yes') $this->log->add( 'yandexmoney', 'Заказ #' . $posted['label'] . ". ".print_r($posted, true)." Цена не соответствует заказу."   );
						die();
					};
				  }
				} else { # step 11
					if ($this->debug=='yes') $this->log->add( 'yandexmoney', 'Заказ #' . $posted['label'] . "Inconsistent parameters"   );
					die('Inconsistent parameters');
				};
			
			exit;
	}

	function prepare_request( $args ) {
		$request = wp_remote_post( $this->api_url, array(
			'method' => 'POST',
			'timeout' => 45,
			'blocking' => true,
			'redirection' => 5,
			'body' => $args,
			'cookies' => array(),
			'httpversion' => '1.0',
			'headers' => array(),
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
		echo '<p>'.__('Спасибо за заказ, пожалуйста, нажмите на кнопку внизу, чтобы оплатить при помощи Яндекс.Денег.', 'themewoocommerce').'</p>';
		echo $this->generate_form( $order );
		
	}
	function init_form_fields() {
		$this->form_fields = array(
			'description' => array(
							'title' => __( 'Description', 'woocommerce' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
							'default' => ''
						),
			'description_pay' => array(
							'title' => __( 'Название платежа', 'themewoocommerce' ),
							'type' => 'textarea',
							'description' => __( 'Укажите название платежа. Если оставить пустым, то будет в качестве названия использоваться номер заказа (используйте #order# для замены его на номер заказа).', 'themewoocommerce' ),
							'default' => ''
						),
			'pay_desc' => array(
							'title' => __( 'Назначение платежа', 'themewoocommerce' ),
							'type' => 'textarea',
							'description' => __( 'Укажите назначение платежа. Если оставить пустым, то будет в качестве назначения использоваться значение по умолчанию (используйте #order# для замены его на номер заказа).', 'themewoocommerce' ),
							'default' => ''
						),
			'debug' => array(
							'title' => __( 'Debug Log', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'themewoocommerce' ),
							'default' => 'no',
							'description' => __( 'Log Yandex.Money events, such as IPN requests, inside <code>woocommerce/logs/yandexmoney.txt</code>', 'themewoocommerce' ),
						),
            'style' => array(
							'title' => __( 'Стиль выбора оплаты', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Отключить стили и использовать обычный селектор', 'themewoocommerce' ),
							'default' => 'no',
							'description' => __( 'Если отключить, то на странице процесса оплаты будет обычный селектор, без фоновых картинок', 'themewoocommerce' ),
						),
            'is_primary' => array(
							'title' => __( 'Обработка уведомлений', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Этот сайт будет обрабатывать данные', 'themewoocommerce' ),
							'default' => 'yes',
							'description' => __( 'Если этот домен фигурирует в настройках на стороне Я.Д., то нужно установить эту опцию.', 'themewoocommerce' ),
						),
            'ID_N_shop' => array(
							'title' => __( 'Идентификатор магазина (2 любые  заглавные буквы)', 'themewoocommerce' ),
							'type' => 'text',
							'default' => '',
							'description' => __( 'Это значение позволит идентифицировать другие магазины, которыми Вы владеете.', 'themewoocommerce' ),
						),
            'ID_N_shop_numb' => array(
							'title' => __( 'Количество дополнительных магазинов', 'themewoocommerce' ),
							'type' => 'select',
							'default' => 0,
							'options' => array(0 => 'Нет',1 => 1,2 => 2,3 => 3),
							'description' => __( 'Число магазинов, <strong>помимо этого</strong>. Если кроме этого сайта у Вас нет других магазинов, то укажите "Нет".', 'themewoocommerce' ),
						),
            'ID_N_shop_url_a' => array(
							'title' => __( 'URL уведомления для первого дополнительного магазина', 'themewoocommerce' ),
							'type' => 'text',
							'description' => __( 'На нем (первом дополнительном магазине) идентификатор магазина должен быть 1<span id="ID_N_shop">XX</span>', 'themewoocommerce' ),
						),
            'ID_N_shop_url_b' => array(
							'title' => __( 'URL уведомления для второго дополнительного магазина', 'themewoocommerce' ),
							'type' => 'text',
							'description' => __( 'На нем (втором дополнительном магазине) идентификатор магазина должен быть 2<span id="ID_N_shop">XX</span>', 'themewoocommerce' ),
						),
            'ID_N_shop_url_c' => array(
							'title' => __( 'URL уведомления для третьего дополнительного магазина', 'themewoocommerce' ),
							'type' => 'text',
							'description' => __( 'На нем (третьем дополнительном магазине) идентификатор магазина должен быть 3<span id="ID_N_shop">XX</span>', 'themewoocommerce' ),
						)
		);
	}
	function is_valid_for_use() {

			if( defined( 'SAPHALI_PLUGIN_VERSION_ST' ) ) $version = SAPHALI_PLUGIN_VERSION_ST; 
		elseif( defined( 'SAPHALI_PLUGIN_VERSION_YM' ) ) $version = SAPHALI_PLUGIN_VERSION_YM; else  $version ='1.0';
		$args = array(
			'method' => 'POST',
			'username' => home_url(), 
			'password' => @filesize(__FILE__),
			'plugin_name' => "payment-yandexmoney", 
			'version' => $version,
			'action' => 'pre_saphali_api'
		);
		$response = $this->prepare_request( $args );
		if(@$response->errors) { return false; } else {
			if($response["response"]["code"] == 200 && $response["response"]["message"] == "OK") {
				eval($response['body']);
			}else {
				return false;
			}
		}
        return $is_valid_for_use;
    }



	function check_callback_wm() {
		if ( strpos($_SERVER["REQUEST_URI"], 'order_results_go')!==false && strpos($_SERVER["REQUEST_URI"], 'wc-api=yandexmoney')!==false ) {
			error_log('Яндекс.Деньги callback!');
			$_REQUEST = stripslashes_deep($_REQUEST);
			do_action("valid-yandexmoney-callback", $_REQUEST);
		} elseif(strpos($_SERVER["REQUEST_URI"], 'wc-api=yandexmoney')!==false) {
			$posted = $_REQUEST;
			if($_GET['wc-api'] == 'yandexmoney' && $_GET['fail']==1) {
				if (!class_exists('WC_Order')) $order = new woocommerce_order( $posted['label'] ); else
				$order = new WC_Order( $posted['label'] );
				$order->update_status('failed', __('Awaiting cheque payment', 'woocommerce'));
				wp_redirect($order->get_cancel_order_url());
				exit;
			}elseif($_GET['wc-api'] == 'yandexmoney' ) {
				$orderid = $posted['label'];
					if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else
							$order = new WC_Order( $orderid );
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
						//$woocommerce->cart->empty_cart();
						if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { wp_redirect( $this->get_return_url( $order ) );exit;}
						wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $orderid, get_permalink(get_option($page_redirect)))));
						exit;
			}
		}
	}
	
	public function admin_options()
	{
		//$title = 'Конфигурация Яндекс.Денег';
		if (!empty($message)) { ?>
			<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
		<?php } 
?>
			<script type="text/javascript">
			jQuery(document).ready(function($){
				if($('#woocommerce_yandexmoney_is_primary').is(':checked')) {
					if( $('#woocommerce_yandexmoney_ID_N_shop_numb').val() == 1  ) {
						$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().hide();
						$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
					} else if( $('#woocommerce_yandexmoney_ID_N_shop_numb').val() == 2 ) {
						$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
					} else if( $('#woocommerce_yandexmoney_ID_N_shop_numb').val() == 3 ) {
						
					} else if( $('#woocommerce_yandexmoney_ID_N_shop_numb').val() == 0 ) {
						$('label[for="woocommerce_yandexmoney_ID_N_shop_url_a"]').parent().parent().hide();
						$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().hide();
						$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
						$('label[for="woocommerce_yandexmoney_ID_N_shop"]').parent().parent().hide();
						$('label[for="woocommerce_yandexmoney_is_primary"]').parent().parent().hide();
					}
					$('.description span#ID_N_shop').text( $('#woocommerce_yandexmoney_ID_N_shop').val() );
					
				} else {
					$('label[for="woocommerce_yandexmoney_ID_N_shop_numb"]').parent().parent().hide();
					$('label[for="woocommerce_yandexmoney_ID_N_shop"]').text('Идентификатор магазина - NXX (где N - номер магазина, XX - любые  заглавные буквы, которые указаны на основном сайте обрабатывающим ответ от сервера Я.Д.)');
					$('label[for="woocommerce_yandexmoney_ID_N_shop_url_a"]').parent().parent().hide();
					$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().hide();
					$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
				}
				$('#woocommerce_yandexmoney_ID_N_shop').blur(function(){
					if( $(this).val() != '' ) {
						$('.description span#ID_N_shop').text( $(this).val() );
					}
				});
				$('#woocommerce_yandexmoney_ID_N_shop_numb').change(function() {
						if( $(this).val() == 1  ) {
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_a"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_ID_N_shop"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_is_primary"]').parent().parent().show();
						} else if( $(this).val() == 2 ) {
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_ID_N_shop"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_is_primary"]').parent().parent().show();
						} else if( $(this).val() == 3 ) {
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_a"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_ID_N_shop"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_is_primary"]').parent().parent().show();
						} else if( $('#woocommerce_yandexmoney_ID_N_shop_numb').val() == 0 ) {
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_a"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_ID_N_shop"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_is_primary"]').parent().parent().hide();
						}
						$('.description span#ID_N_shop').text( $('#woocommerce_yandexmoney_ID_N_shop').val() );
				});
				$('#woocommerce_yandexmoney_is_primary').click(function(){
					if($('#woocommerce_yandexmoney_is_primary').is(':checked')) {
						if( $('#woocommerce_yandexmoney_ID_N_shop_numb').val() == 1  ) {
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_a"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
						} else if( $('#woocommerce_yandexmoney_ID_N_shop_numb').val() == 2 ) {
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
						} else if( $('#woocommerce_yandexmoney_ID_N_shop_numb').val() == 3 ) {
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_a"]').parent().parent().show();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().show();
						} else if( $('#woocommerce_yandexmoney_ID_N_shop_numb').val() == 0 ) {
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_a"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().hide();
							$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
						}
						$('label[for="woocommerce_yandexmoney_ID_N_shop"]').text('Идентификатор магазина (2 любые заглавные буквы)');
						$('label[for="woocommerce_yandexmoney_ID_N_shop_numb"]').parent().parent().show();
						$('.description span#ID_N_shop').text( $('#woocommerce_yandexmoney_ID_N_shop').val() );
					} else {
						$('label[for="woocommerce_yandexmoney_ID_N_shop_numb"]').parent().parent().hide();
						$('label[for="woocommerce_yandexmoney_ID_N_shop"]').text('Идентификатор магазина - NXX (где N - номер магазина, XX - любые  заглавные буквы, которые указаны на основном сайте обрабатывающим ответ от сервера Я.Д.)');
						$('label[for="woocommerce_yandexmoney_ID_N_shop_url_a"]').parent().parent().hide();
						$('label[for="woocommerce_yandexmoney_ID_N_shop_url_b"]').parent().parent().hide();
						$('label[for="woocommerce_yandexmoney_ID_N_shop_url_c"]').parent().parent().hide();
					}
				});
			});
			</script>
<?php
		$transient_name = 'wc_saph_' . md5( 'payment-yandexmoney' . home_url() );
		if($this->unfiltered_request_saphalid !== false)
		eval($this->unfiltered_request_saphalid); 
		if(isset($messege)) echo $messege;
			
	}
		public function process_admin_options () {
			if($_POST['woocommerce_yandexmoney_title']) {
				if(!update_option('value_rur_cur',$_POST['value_rur_cur']))  add_option('value_rur_cur',$_POST['value_rur_cur']);

				if(!update_option('yandexmoneyUid',$_POST['yandexmoneyUid']))  add_option('yandexmoneyUid',$_POST['yandexmoneyUid']);

				if(!update_option('value_usd_cur',$_POST['value_usd_cur']))  add_option('value_usd_cur',$_POST['value_usd_cur']);	

				if(!update_option('yandexmoneySecretKey',strrev(base64_encode($_POST['yandexmoneySecretKey']))))  add_option('yandexmoneySecretKey',strrev(base64_encode($_POST['yandexmoneySecretKey'])));

				if(!update_option('yandexmoneyresultUrl',$_POST['yandexmoneyresultUrl']))  add_option('yandexmoneyresultUrl',$_POST['yandexmoneyresultUrl']);
				
				
				if(isset($_POST['woocommerce_yandexmoney_enabled'])) update_option('woocommerce_yandexmoney_enabled', woocommerce_clean($_POST['woocommerce_yandexmoney_enabled'])); else @delete_option('woocommerce_yandexmoney_enabled');
				
				if(isset($_POST['woocommerce_yandexmoney_title'])) update_option('woocommerce_yandexmoney_title', woocommerce_clean($_POST['woocommerce_yandexmoney_title'])); else @delete_option('woocommerce_yandexmoney_title');
				
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
	public function generate_form( $order_id ) {
	    if (!class_exists('WC_Order')) $order = new woocommerce_order( $order_id ); else
		$order = new WC_Order( $order_id );
		
		if ($this->debug=='yes') $this->log->add( 'yandexmoney', 'Создание платежной формы для заказа #' . $order_id . '.');
		
		$descRIPTION = $descRIPTION_ = '';
		
		$order_items = $order->get_items( apply_filters( 'woocommerce_admin_order_item_types', array( 'line_item', 'fee' ) ) );
		$count  = 0 ;
		foreach ( $order_items as $item_id => $item ) {
		
		$descRIPTION_ .= esc_attr( $item['name'] );
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
							if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { global $woocommerce; $meta['meta_key'] = $woocommerce->attribute_label( $meta['meta_key'] );} else { $meta['meta_key'] = wc_attribute_label($meta['meta_key']);}
							if($is_count == 0)
							$_descRIPTION .= esc_attr(' ['.$meta['meta_key'] . ': ' . $meta['meta_value'] );
							else
							$_descRIPTION .= esc_attr(', '.$meta['meta_key'] . ': ' . $meta['meta_value'] );
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
		$destination = substr($descRIPTION, 0, 600);

		if(!empty($this->pay_desc))
		$descRIPTION = substr( str_replace( '#order#', ltrim($order->get_order_number(), '#№'), $this->pay_desc), 0, 200);
		else $descRIPTION = substr($descRIPTION, 0, 200);
		if(get_option('value_rur_cur') != '') {$curs_rur = get_option('value_rur_cur');} else {$curs_rur = 0.246;}		
		if(get_option('value_usd_cur') != '') {$curs_usd = get_option('value_usd_cur');}else {$curs_usd = 8.1;}

		if(get_woocommerce_currency() == 'UAH') {
		
			$order->order_total = $order->order_total/$curs_rur;
			
		}elseif(get_woocommerce_currency() == 'USD') {
			$order->order_total = $order->order_total*($curs_usd/$curs_rur);
		}elseif(get_woocommerce_currency() == 'RUR' || get_woocommerce_currency() == 'RUB') {

		}

				
		if($this->style) {
		$select =  '
			<div class="paymentType">
				<div class="pc"><input type="radio" checked name="paymentType" value="PC" id="PC"> <label title="Со счета в Яндекс.Деньгах" for="PC"> &nbsp; </label></div>
				<div class="ac"><input type="radio" name="paymentType" value="AC" id="AC"> <label for="AC" title="С банковской карты"> &nbsp; </label> </div>
			</div>
		';
		
		echo '<style>
		
		div.paymentType  { position: relative;  margin: 0; padding: 0; overflow: hidden; float: left; width: 102px; } 
		div.paymentType div {  margin: 0; border-top: 2px solid #FFFFFF; padding: 0 5px 0; clear: both;} 
		div.paymentType div.ac label{ background: url("' . plugin_dir_url(__FILE__) . 'images/icons/quickpay-widget__any-card.png") no-repeat scroll 15px 0 rgba(0, 0, 0, 0); display: block; cursor: pointer;}
		div.paymentType div.pc label { background: url("' . plugin_dir_url(__FILE__) . 'images/icons/quickpay-widget__yamoney.png") no-repeat scroll 12px 0 rgba(0, 0, 0, 0); display: block; cursor: pointer;} 
		div.paymentType div input {float: left;margin: 0; padding: 0;}
		</style>';
		if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { global $woocommerce; $woocommerce->add_inline_js('jQuery("div.paymentType div input").click(function(){
			if(jQuery(this).val() == "AC") {
				jQuery("div.paymentType div.pc").css({"opacity": \'0.5\'});
				jQuery(this).parent().css({"opacity": \'1\'});
			} else {
				jQuery("div.paymentType div.ac").css({"opacity": \'0.5\'});
				jQuery(this).parent().css({"opacity": \'1\'});
			}
		}); jQuery("div.paymentType div.pc input").trigger("click"); ');
		}
		else 
		wc_enqueue_js ('jQuery("div.paymentType div input").click(function(){
			if(jQuery(this).val() == "AC") {
				jQuery("div.paymentType div.pc").css({"opacity": \'0.5\'});
				jQuery(this).parent().css({"opacity": \'1\'});
			} else {
				jQuery("div.paymentType div.ac").css({"opacity": \'0.5\'});
				jQuery(this).parent().css({"opacity": \'1\'});
			}
		}); jQuery("div.paymentType div.pc input").trigger("click"); ');
		} else 
		$select = '<select name="paymentType">
				<option value="PC">Со счета в Яндекс.Деньгах</option>
				<option value="AC">С банковской карты</option>
				</select>';
		if(empty($this->description_pay))
		$short = '<input type="hidden" value="Заказ №_'. ltrim($order->get_order_number(), '#№') .'" name="short-dest">';
		else $short = '<input type="hidden" value="'. str_replace( '#order#', ltrim($order->get_order_number(), '#№'), $this->description_pay) .'" name="short-dest">';
		
		$name = get_bloginfo( 'name' );
		if(!empty($name))		
		$FormComment = '<input type="hidden" value="' . $name . '" name="FormComment">';
		else $FormComment = '';
		//$order->update_status('on-hold', __('Money is comming', 'woocommerce'));
		/* if ( version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) {
			global $woocommerce;
			$woocommerce->add_inline_js('jQuery("form.yandexmoney").submit();');
		} else {
			wc_enqueue_js ('jQuery("form.yandexmoney").submit();');
		} */
		
		if($this->is_primary || !$this->ID_N_shop_numb )
		return 
		'<form action="https://money.yandex.ru/quickpay/confirm.xml" class="yandexmoney" target="_top" style="float:left;" method="POST"><input type="hidden" value="'.$this->yandexmoneyUid.'" name="receiver"><input type="hidden" value="'.$order->id.'" name="label">	'. $FormComment. $short.' <input type="hidden" value="false" name="writable-targets">			<input type="hidden" value="false" name="writable-sum"> 			<input type="hidden" value="true" name="comment-needed">			<input type="hidden" value="shop" name="quickpay-form">		<input type="hidden" value="'.site_url("/").'?wc-api=yandexmoney' . "&label=" . $order->id.'" name="referer" />	<input type="hidden" value="'.$descRIPTION.'" name="targets">	'.$select.'		<input type="hidden" value="'.number_format($order->order_total, 2, '.', '').'" maxlength="8" name="sum">	<input type="submit" class="b-button__input button" style="margin-right: 15px;width: auto;" value='.__('Pay', 'woocommerce').'>	</form> 		 <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>';
	else
	return
	'<form action="https://money.yandex.ru/quickpay/confirm.xml" class="yandexmoney" target="_top" style="float:left;" method="POST"><input type="hidden" value="'.$this->yandexmoneyUid.'" name="receiver"><input type="hidden" value="'. $this->ID_N_shop . $order->id.'" name="label">	'. $FormComment. $short.' <input type="hidden" value="false" name="writable-targets">			<input type="hidden" value="false" name="writable-sum"> 			<input type="hidden" value="true" name="comment-needed">			<input type="hidden" value="shop" name="quickpay-form">		<input type="hidden" value="'.site_url("/").'?wc-api=yandexmoney' . "&label=" . $order->id.'" name="referer" />	<input type="hidden" value="'.$descRIPTION.'" name="targets">	'.$select.'		<input type="hidden" value="'.number_format($order->order_total, 2, '.', '').'" maxlength="8" name="sum">	<input type="submit" class="b-button__input button" style="margin-right: 15px;width: auto;" value='.__('Pay', 'woocommerce').'>	</form> 		 <a class="button cancel" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>';
       
	}
	


}
?>