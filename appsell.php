<?php

/**
 * @package: appsell-plugin
 */

/**
 * Plugin Name: AppSell
 * Description: Use AppSell to increase sales on your Woocommerce store using Upsell, Cross Sell, Frequently Bought Together, Discounts, Coupons, Bundles and More!
 * Version: 1.0.1
 * Author: AppSell
 * Author URI: https://appsell.io
 * License: GPLv3 or later
 * Text Domain: appsell-plugin
 */

if (!defined('ABSPATH')) {
  die;
}

define("APPSELL_API_URL", "https://app.appsell.io");
define('APPSELL_VERSION', '1.0');
define('APPSELL_PATH', dirname(__FILE__));
define('APPSELL_FOLDER', basename(APPSELL_PATH));
define('APPSELL_URL', plugins_url() . '/' . APPSELL_FOLDER);
define('APPSELL_API_KEY', get_option('appsell_api_key'));
define("APPSELL_DEVELOPMENT", (stripos(APPSELL_API_URL, "dev.appsell") !== false ? "dev" : ""));
define("APPSELL_DEBUG", true);

register_activation_hook(__FILE__, 'appsell_activation_hook');
register_deactivation_hook(__FILE__, 'appsell_deactivation_hook');
register_uninstall_hook(__FILE__, 'appsell_uninstall_hook');
add_action('admin_enqueue_scripts', 'appsell_add_admin_css_js');
add_action('admin_menu', 'appsell_admin_menu');
add_action('wp_head', 'appsell_script');
add_action("wp_ajax_nopriv_appsell_installation","appsell_installation");
add_action("wp_ajax_appsell_installation","appsell_installation");

function appsell_activation_hook()
{
	$data = array(
		'store' => get_site_url(),
    'email' => get_option('admin_email'),
		'event' => 'install'
	);

	$response = appsell_send_request('/Woocommerce/state', $data);

	if ($response)
	{
		if ($response['success'] > 0)
	 	{
	 		appsell_log('api key: '.$response['api_key'], get_site_url());
      appsell_log((!get_option('appsell_api_key') ? "yes" : "no"), get_site_url());

	 		if (!get_option('appsell_api_key'))
	 		{
	 			add_option('appsell_api_key',$response['api_key']);

        appsell_log($response['app_name']." ".$response['user_id']." ".$response['scope'], get_site_url());
        
        if (class_exists("WC_Auth"))
        {
          class AppSell_AuthCustom extends WC_Auth 
          {
            public function getKeys($app_name, $user_id, $scope)
            {
              return parent::create_keys($app_name, $user_id, $scope);
            }
          }

          $auth = new AppSell_AuthCustom();
          $keys = $auth->getKeys($response['app_name'], $response['user_id'], $response['scope']);
          $data = array(
            'store' => get_site_url(),
            'keys' => $keys,
            'user_id' => $response['user_id'],
            'event' => 'update_keys'
          );
          $keys_response = appsell_send_request('/Woocommerce/state', $data);

          if ($keys_response && $keys_response['success'] == 0)
          {
            add_option('appsell_error', 'yes');
            add_option('appsell_error_message', $keys_response['message']);
          }
        }

        appsell_log('after auth');
	 		}
	 		else 
	 		{	 			
        update_option('appsell_api_key', $response['api_key']);
	 		}
		}
		else
		{
			appsell_log('invalid response - api key', get_site_url());
      
      if (!get_option('appsell_error'))
      {
        add_option('appsell_error', 'yes');
        add_option('appsell_error_message', 'Error activation plugin!');
      }
		}
	}
	else
	{
		appsell_log('error getting response - api key', get_site_url());

    if (!get_option('appsell_error'))
    {
      add_option('appsell_error', 'yes');
      add_option('appsell_error_message', 'Error activation plugin!');
    }
	}
}

function appsell_deactivation_hook()
{
  if(!current_user_can('activate_plugins')) 
  {
    return;
  }
  $data = array(
    'store' => get_site_url(),
    'event' => 'deactivated',
  );
  return appsell_send_request('/Woocommerce/state', $data);
}

function appsell_uninstall_hook() 
{
  if(!current_user_can('activate_plugins')) 
  {
    return;
  }

  delete_option('appsell_api_key');

  if (get_option('appsell_error'))
  {
    delete_option('appsell_error');
  }

  if (get_option('appsell_error_message'))
  {
    delete_option('appsell_error_message');
  }

  appsell_clear_all_caches();

  $data = array(
  	'store' => get_site_url(),
    'event' => 'uninstall',
  );
  return appsell_send_request('/Woocommerce/state', $data);
}

function appsell_script()
{
	if (strlen(APPSELL_API_KEY) > 0)
	{
    $attributes = array(
      'id'    => APPSELL_DEVELOPMENT.'appsellScript',
      'async' => true,
      'src'   => esc_url(APPSELL_API_URL."/api/js/upsaleWoo.js?key=".APPSELL_API_KEY),
    );
    wp_print_script_tag($attributes);
	}
}

function appsell_add_admin_css_js()
{
	wp_register_style('appsell_style', APPSELL_URL.'/assets/css/style.css');
  wp_enqueue_style('appsell_style');
  wp_register_script('appsell-admin', APPSELL_URL.'/assets/js/script.js', array('jquery'), '1.0.0');
  wp_enqueue_script('appsell-admin');
}

function appsell_admin_menu()
{
  add_menu_page('AppSell Settings', 'AppSell', 'manage_options', 'appsell', 'appsell_admin_menu_page_html', APPSELL_URL.'/assets/images/appsell_icon.png');
}

function appsell_has_woocommerce() 
{
  return in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')));
}

function appsell_admin_menu_page_html()
{
	include_once APPSELL_PATH.'/views/appsell_admin_page.php';
}

function appsell_send_request($path, $data) 
{
  try 
  {
		$headers = array(
		  'Content-Type' => 'application/json',
		  'x-plugin-version' => APPSELL_VERSION,
		  'x-site-url' => get_site_url(),
		  'x-wp-version' => get_bloginfo('version'),
		);

    if (appsell_has_woocommerce()) 
    {
      $headers['x-woo-version'] = WC()->version;
    }

    $url = APPSELL_API_URL.$path;
    $data = array(
      'headers' => $headers,
      'body' => json_encode($data),
    );
    appsell_log('sending request', $url);
    $response = wp_remote_post($url, $data);
    appsell_log('got response', $url);
   
   	if (!is_wp_error($response)) 
		{
	  	$decoded_response = json_decode(wp_remote_retrieve_body($response), true);

	  	return $decoded_response;
	  }

	  return 0;
  } 
  catch(Exception $err) 
  {
    appsell_handle_error('failed sending request', $err, $data);
  }
}

function appsell_log($message, $data = null)
{
  $log = null;

  if (isset($data)) 
  {
    $log = "\n[AppSell] " . $message . ":\n" . print_r($data, true);
  } 
  else 
  {
    $log = "\n[AppSell] " . $message;
  }
  error_log($log);

  if (appsell_DEBUG) 
  {
    $plugin_log_file = plugin_dir_path(__FILE__).'debug.log';
    error_log($log."\n", 3, $plugin_log_file);
  }
}

function appsell_handle_error($message, $err, $data = null)
{
  appsell_log($message, $err);
}

function appsell_plugin_redirect()
{
  exit(wp_redirect("admin.php?page=AppSell"));
}

function appsell_clear_all_caches()
{
  try 
  {
    global $wp_fastest_cache;

    if (function_exists('w3tc_flush_all')) 
    {
      w3tc_flush_all();                
    } 

    if (function_exists('wp_cache_clean_cache')) 
    {
      global $file_prefix, $supercachedir;

      if (empty($supercachedir) && function_exists('get_supercache_dir')) 
      {
        $supercachedir = get_supercache_dir();
      }
      wp_cache_clean_cache($file_prefix);
    } 
    
    if (method_exists('WpFastestCache', 'deleteCache') && !empty($wp_fastest_cache)) 
    {
      $wp_fastest_cache->deleteCache();
    } 

    if (function_exists('rocket_clean_domain')) 
    {
      rocket_clean_domain();
      // Preload cache.
      if (function_exists('run_rocket_sitemap_preload')) {
        run_rocket_sitemap_preload();
      }
    } 
    
    if (class_exists("autoptimizeCache") && method_exists("autoptimizeCache", "clearall")) 
    {
      autoptimizeCache::clearall();
    }
    
    if (class_exists("LiteSpeed_Cache_API") && method_exists("autoptimizeCache", "purge_all")) 
    {
      LiteSpeed_Cache_API::purge_all();
    }
    
    if (class_exists('\Hummingbird\Core\Utils')) 
    {
      $modules= \Hummingbird\Core\Utils::get_active_cache_modules();
      foreach ($modules as $module => $name) 
      {
        $mod = \Hummingbird\Core\Utils::get_module( $module );

        if ($mod->is_active()) 
        {
          if ('minify' === $module) 
          {
            $mod->clear_files();
          } 
          else 
          {
            $mod->clear_cache();
          }
        }
      } 
    }
  } 
  catch (Exception $e) 
  {
    return 1;
  }
}

function appsell_installation()
{
  if (in_array('woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins'))) && !get_option('appsell_error')) 
  {
    $json['success'] = 1;
    $json['api_key'] = get_option('appsell_api_key');
  }
  else
  {
    $json["success"] = 0;
  }

  wp_send_json($json);
  wp_die();
}

?>