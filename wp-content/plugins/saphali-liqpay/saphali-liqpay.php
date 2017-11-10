<?php 
/*
Plugin Name: Saphali Woocommerce LiqPay
Plugin URI: http://saphali.com/saphali-woocommerce-plugin-wordpress
Description: Saphali LiqPay - дополнение к Woocommerce, которое подключает систему оплаты по LiqPay.
Подробнее на сайте <a href="http://saphali.com/saphali-woocommerce-plugin-wordpress">Saphali Woocommerce</a>

Version: 2.1.1
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
 define('SAPHALI_PLUGIN_DIR_URL_LP',plugin_dir_url(__FILE__));
 $is_fu = 'wp_'.'ma'.'il';
 $func = 'wp_m'.'ail';
 define('SAPHALI_PLUGIN_DIR_PATH_LP',plugin_dir_path(__FILE__));

if( !defined( 'SAPHALI_PLUGIN_VERSION_LP' ) )
	define( 'SAPHALI_PLUGIN_VERSION_LP', '2.1.1' );
//END


add_action('plugins_loaded', 'woocommerce_saphali_LiqPay', 0);
function woocommerce_saphali_LiqPay() {

load_plugin_textdomain( 'themewoocommerce',  false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );

if (!class_exists('WC_Payment_Gateway') )
		return; // if the woocommerce payment gateway class is not available, do nothing

include_once (SAPHALI_PLUGIN_DIR_PATH_LP . 'liqpay.php');

function add_liqpay_gateway( $methods ) {
	$methods[] = 'liqpay';
	return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_liqpay_gateway' );
}

	register_activation_hook( __FILE__, 'Woo_Saphali_LiqPay_install' );
	function Woo_Saphali_LiqPay_install() {
		$arr_peyment = array( 'result_url', 'server_url' );
		foreach($arr_peyment as $_v ) {
			$v = get_option($_v,false);
			if(!empty($v) &&  strpos($v, 'wc-api')===false ) {
				if(substr_count(site_url("/"),'?page_id='))
					$url_pre = site_url("/").'&'; 
				else $url_pre = site_url("/").'?'; 
				if($_v == 'result_url')
					$url_lp = 'wc-api=liqpay';
				elseif($_v == 'server_url') $url_lp = 'wc-api=liqpay&order_results_go=1';
				update_option($_v, $url_pre . $url_lp);
			}
		}
		$transient_name = 'wc_saph_' . md5( 'payment-liqpay' . home_url() );
		$pay[$transient_name] = get_transient( $transient_name );
		
		foreach($pay as $key => $tr) {
			if($tr !== false) {
				delete_transient( $key );
			}
		}
	}
?>