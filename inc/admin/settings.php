<?php
if (!defined('ABSPATH')) {
    exit;
}
add_filter('wpcf7_editor_panels', 'cf7mls_admin_settings');
function cf7mls_admin_settings($panels)
{
    $panels['cf7mls-settings-panel'] = array(
        'title' => __('Multi-Step Settings', 'cf7mls'),
        'callback' => 'cf7mls_settings_func'
    );
    return $panels;
}
function cf7mls_settings_func($post)
{
    cf7mls_premium_only();
}
