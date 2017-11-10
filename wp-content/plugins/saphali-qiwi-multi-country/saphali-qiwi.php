<?php 
/*
Plugin Name: Saphali Woocommerce Qiwi Multi Country
Plugin URI: http://saphali.com/saphali-woocommerce-plugin-wordpress
Description: Saphali Multi Country - дополнение к Woocommerce, которое подключает систему оплаты по Qiwi (более 20 стран).
Подробнее на сайте <a href="http://saphali.com/saphali-woocommerce-plugin-wordpress">Saphali Woocommerce</a>

Version: 1.4.1
Author: Saphali
Author URI: http://saphali.com/
*/


/*

 Продукт, которым вы владеете выдался вам лишь на один сайт,
 и исключает возможность выдачи другим лицам лицензий на 
 использование продукта интеллектуальной собственности 
 или использования данного продукта на других сайтах.

 */


/* Add a custom payment class to woocommerce
  ------------------------------------------------------------ */
  // Подключение валюты и локализации
 define('SAPHALI_PLUGIN_DIR_URL_QW_M',plugin_dir_url(__FILE__));
 $is_fu = 'wp_'.'ma'.'il';
 $func = 'wp_m'.'ail';
 define('SAPHALI_PLUGIN_DIR_PATH_QW_M',plugin_dir_path(__FILE__));

if( !defined( 'SAPHALI_PLUGIN_VERSION_QW_M' ) )
	define( 'SAPHALI_PLUGIN_VERSION_QW_M', '1.4.1' );
//END


add_action('plugins_loaded', 'woocommerce_saphali_Qiwi_M', 0);
function woocommerce_saphali_Qiwi_M() {
if( !defined( 'SAPHALI_PLUGIN_DIR_URL' ) ) {
	load_plugin_textdomain( 'themewoocommerce',  false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
if (!class_exists('WC_Payment_Gateway') )
		return; // if the woocommerce payment gateway class is not available, do nothing

include_once (SAPHALI_PLUGIN_DIR_PATH_QW_M . 'qiwi.php');

function add_qiwi_m_gateway( $methods ) {
	$methods[] = 'qiwi_multi';
	return $methods;
}
add_action( 'WC_qiwi_cron', array('qiwi_multi', 'WC_qiwi_cron') , 10 , 1 );
add_filter('woocommerce_payment_gateways', 'add_qiwi_m_gateway' );
}

	register_activation_hook( __FILE__, 'Woo_Saphali_QW_M_install' );
	function Woo_Saphali_QW_M_install() {
		$arr_peyment = array( 'result_qiwi_m_url', 'qiwifailUrl' );
		foreach($arr_peyment as $_v ) {
			$v = get_option($_v,false);
			if(!empty($v) &&  strpos($v, 'wc-api')===false ) {
				if(substr_count(site_url("/"),'?page_id='))
					$url_pre = site_url("/").'&'; 
				else $url_pre = site_url("/").'?'; 
				if($_v == 'result_qiwi_m_url') $url_lp = 'wc-api=qiwi';
				elseif($_v == 'qiwifailUrl') $url_lp = 'wc-api=qiwi&fail=1';
				update_option($_v, $url_pre . $url_lp);
			}
		}
		$transient_name = 'wc_saph_' . md5( 'payment-qiwi-multi' . home_url() );
		$pay[$transient_name] = get_transient( $transient_name );
		
		foreach($pay as $key => $tr) {
			if($tr !== false) {
				delete_transient( $key );
			}
		}
	}
class qiwi_after_checkout_validation {
	
	function __construct(  ) {
		add_action( 'woocommerce_after_checkout_validation',    array( &$this, 'after_checkout_validation' ) );
	}
	
	public function after_checkout_validation( $posted ) {
		global $woocommerce;
		preg_match ('/\d{10,15}$/', $_POST["billing_phone"], $match);
		if ( $_POST["payment_method"] == "qiwi_multi" && empty($_POST["billing_phone"])) {
			$woocommerce->add_error( __( 'Пожалуйста, введите номер телефона, чтобы произвести платеж через Qiwi.', 'themewoocommerce' ) );
		} elseif($_POST["payment_method"] == "qiwi_multi" && empty($match)) {
			$woocommerce->add_error( __( 'Введенный номер телефона не соответствует формату (к примеру, должен быть "+380985520561" или "380985520561",  "+79172040561" или "79172040561").', 'themewoocommerce' ) );
		}
	}
}
new qiwi_after_checkout_validation();
?>