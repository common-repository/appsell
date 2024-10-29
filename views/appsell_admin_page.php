<?php

// Prevent direct file access
if (!defined('ABSPATH')) {
  exit;
}

if (!current_user_can('manage_options')) {
  wp_die(__('You do not have sufficient permissions to access this page.'));
}

$api_key = get_option('appsell_api_key');
$error = get_option('appsell_error');
$error_message = get_option('appsell_error_message');

?>
<div class="appsell-page">
	<div class="appsell-logo">
		<img style="margin-bottom: 34px;" src="<?php echo esc_url(APPSELL_URL);?>/assets/images/appsell_logo.png" />
	</div>
	<?php

	if (!$api_key || ($api_key && strlen($api_key) < 1) || $api_key == null)
	{
		?>
		<div class="appsell-alert">Invalid API key!</div>
		<?php
	}
	else
	{
		$page_target = "_blank";

		if (isset($_GET['autologin']) && $_GET['autologin'])
		{
			$page_target = "_self";
		}

		$button = '<button id="appsellLoginBtn" class="appsell-btn-login-me cbutton" style="text-transform: none; font-size: 25px;" onclick="window.open(\''.esc_url(APPSELL_API_URL).'/Woocommerce/loginByToken/'.$api_key.'\', \''.$page_target.'\');">Go to your dashboard <img class="btnArrow" src="'.esc_url(APPSELL_URL).'/assets/images/polygon.svg"></button>';

		if ($error && $error == "yes")
		{
			$button = '<button id="appsellLoginBtn" class="appsell-btn-login-me cbutton" style="text-transform: none; font-size: 25px;" disabled>Go to your dashboard <img class="btnArrow" src="'.esc_url(APPSELL_URL).'/assets/images/polygon.svg"></button>';

			?>
			<div class="appsell-alert"><?php echo esc_html($error_message);?></div>
			<div class="appsell-clearfix"></div><br />
			<?php
		}

		?>
		<div class="appsell-login-url" style="margin-top:-40px; line-height: 30px;">
			<div class="title">You’re All Set 🎉 </div>
			<h4 class="description">AppSell is installed on your website </h4>
			<div class="appsell-clearfix"></div>
			<?php echo $button;?>
			<div class="appsell-clearfix"></div>
		</div>
		<div class="appsell-clearfix"></div>
		<?php
	}
	?>
	<div class="appsell-clearfix"></div>
  <div class="other-links d-flex justify-center">
 		<span>Click on the button to create and manage your funnels </span>
  </div>
</div>

