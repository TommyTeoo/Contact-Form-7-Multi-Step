<?php
/**
Plugin Name: Contact Form 7 Multi Step
Plugin URI: http://ninjateam.org
Description: Contact Form 7 Multi Step is a plugin that allows you create multi steps on only one form.
Version: 1.0
Author: NinjaTeam
Author URI: http://ninjateam.org
 */
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WPCF7_AUTOP')) {
    define('WPCF7_AUTOP', false);
}
define('CF7MLS_PLUGIN_DIR', dirname(__FILE__));
define('CF7MLS_PLUGIN_URL', plugins_url('', __FILE__));

require_once CF7MLS_PLUGIN_DIR . '/inc/admin/init.php';
require_once CF7MLS_PLUGIN_DIR . '/inc/admin/settings.php';

require_once CF7MLS_PLUGIN_DIR . '/inc/frontend/init.php';

/*
 * Languages
 */
add_action('plugins_loaded', 'cf7mlsLoadTextdomain');
function cf7mlsLoadTextdomain()
{
    load_plugin_textdomain('cf7mls', false, plugin_basename(CF7MLS_PLUGIN_DIR) . '/languages/');
}
function cf7mls_is_active_cf7db()
{
    return defined('CF7D_FILE');
}
function cf7mls_sanitize_posted_data($value)
{
    if (is_array($value)) {
        $value = array_map('cf7mls_sanitize_posted_data', $value);
    } elseif (is_string($value)) {
        $value = wp_check_invalid_utf8($value);
        $value = wp_kses_no_null($value);
    }
    return $value;
}
function cf7mls_cf7d_add_more_fields($posted_data)
{
    //time
    $posted_data['submit_time'] = date('Y-m-d H:i:s');
    //ip
    $posted_data['submit_ip'] = (isset($_SERVER['X_FORWARDED_FOR'])) ? $_SERVER['X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
    //user id
    $posted_data['submit_user_id'] = 0;
    if (function_exists('is_user_logged_in') && is_user_logged_in()) {
        $current_user = wp_get_current_user(); // WP_User
        $posted_data['submit_user_id'] = $current_user->ID;
    }
    return $posted_data;
}
function cf7mls_premium_only()
{
    _e('Premium Only', 'cf7mls');
}
if (cf7mls_is_active_cf7db()) {
    add_filter('cf7d_no_save_fields', 'cf7mls_cf7d_no_save_fields');
    function cf7mls_cf7d_no_save_fields($fields)
    {
        //$fields[] = '_cf7mls_db_form_data_id';
        $fields[] = '_wpnonce';
        $fields[] = 'cf7mls_back';
        $fields[] = 'cf7mls_next';
        
        return $fields;
    }

    /*
     * Remove user's informations every steps
     */
    add_action('cf7d_after_insert_db', 'cf7mls_cf7d_after_insert_db', 10, 3);
    function cf7mls_cf7d_after_insert_db($contact_form, $form_id, $data_id)
    {
        global $wpdb;
        $data_id_be_delete = $wpdb->get_results("SELECT `value` FROM ".$wpdb->prefix."cf7_data_entry WHERE `cf7_id` = '".$form_id."' AND `name` = '_cf7mls_db_form_data_id'");
        if (isset($data_id_be_delete[0])) {
            $data_id_be_delete = $data_id_be_delete[0]->value;
            //delele data_id
            $wpdb->delete($wpdb->prefix."cf7_data", array('id' => $data_id_be_delete));
            //delete entry
            $wpdb->delete($wpdb->prefix."cf7_data_entry", array('cf7_id' => $form_id, 'data_id' => $data_id_be_delete));
            $wpdb->delete($wpdb->prefix."cf7_data_entry", array('cf7_id' => $form_id, 'name' => '_cf7mls_db_form_data_id'));
        }
    }
}
/*
 * Ajax
 */
add_action('wp_ajax_cf7mls_validation', 'cf7mls_validation_callback');
add_action('wp_ajax_nopriv_cf7mls_validation', 'cf7mls_validation_callback');

function cf7mls_validation_callback()
{
    global $wpdb;
    if (isset($_POST['_wpcf7'])) {
        $id = (int) $_POST['_wpcf7'];
        $unit_tag = wpcf7_sanitize_unit_tag($_POST['_wpcf7_unit_tag']);

        $spam = false;
        if ($contact_form = wpcf7_contact_form($id)) {
            if (WPCF7_VERIFY_NONCE && ! wpcf7_verify_nonce($_POST['_wpnonce'], $contact_form->id())) {
                $spam = true;
                exit(__('Spam detected'));
            } else {
                $items = array(
                    'mailSent' => false,
                    'into' => '#' . $unit_tag,
                    'captcha' => null );
                /* Begin validation */
                require_once WPCF7_PLUGIN_DIR . '/includes/validation.php';
                $result = new WPCF7_Validation();

                $tags = $contact_form->form_scan_shortcode();

                foreach ($tags as $tag) {
                    $result = apply_filters('wpcf7_validate_' . $tag['type'], $result, $tag);
                }
                $result = apply_filters('wpcf7_validate', $result, $tags);

                $invalid_fields = $result->get_invalid_fields();
                $return = array('success' => $result->is_valid(), 'invalid_fields' => $invalid_fields);
                if ($return['success'] == false) {
                    $return['message'] = $contact_form->messages['validation_error'];
                    if (empty($return['message'])) {
                        $default_messages = wpcf7_messages();
                        $return['message'] = $default_messages['validation_error']['default'];
                    }
                } else {
                    $return['message'] = '';
                }
                $json = json_encode($return);
                exit($json);
            }
        }
    }
}
