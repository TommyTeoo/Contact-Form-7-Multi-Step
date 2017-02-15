<?php
if (!defined('ABSPATH')) {
    exit;
}


//add js, css
add_action('wp_enqueue_scripts', 'cf7mls_frontend_scripts_callback');
function cf7mls_frontend_scripts_callback()
{
    $cf7d_messages_error = '';
    wp_register_script('cf7mls', CF7MLS_PLUGIN_URL . '/assets/frontend/js/cf7mls.js', array('jquery', 'jquery-form'), '1.0', true);
    wp_enqueue_script('cf7mls');
    if (apply_filters('is_using_cf7mls_css', true)) {
        wp_register_style('cf7mls', CF7MLS_PLUGIN_URL . '/assets/frontend/css/cf7mls.css');
        wp_enqueue_style('cf7mls');
    }
    wp_localize_script('cf7mls', 'cf7mls_object',
        array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'cf7mls_error_message' => $cf7d_messages_error,
            'scroll_step' => apply_filters('cf7mls-scroll-step', "false"),
        )
    );
}

/**
 * Wpcf7 shortcode.
 */
function cf7mls_add_shortcode_step()
{
    wpcf7_add_form_tag(array('cf7mls_step', 'cf7mls_step*'), 'cf7mls_multistep_shortcode_callback', true);
}
add_action('wpcf7_init', 'cf7mls_add_shortcode_step');
function cf7mls_multistep_shortcode_callback($tag)
{
    $tag = new WPCF7_Shortcode($tag);
    $found = 0;
    $html = '';
    if ($contact_form = wpcf7_get_current_contact_form()) {
        $form_tags = $contact_form->scan_form_tags();
        foreach ($form_tags as $k => $v) {
            if ($v['type'] == $tag->type) {
                $found++;
            }
            if ($v['name'] == $tag->name) {
                if ($found <= 2) {
                    $html = '<button type="button" class="cf7mls_back action-button" name="cf7mls_back">Back</button>';
                    $html .= '<button type="button" class="cf7mls_next cf7mls_btn action-button" name="cf7mls_next">Next</button>';
                    $html .= '</fieldset><fieldset class="fieldset-cf7mls">';
                }
                break;
            }
        }
    }

    return $html;
}

/**
 * Wrap form
 */
add_filter('wpcf7_form_elements', 'cf7mls_wrap_form_elements_func', 10);
function cf7mls_wrap_form_elements_func($code)
{
    /* If the form has multistep's shortcode */
    if (strpos($code, '<fieldset class="fieldset-cf7mls')) {
        if (defined('WPCF7_AUTOP') && (WPCF7_AUTOP == true)) {
            $code = preg_replace('#<p>(.*?)<\/fieldset><fieldset class=\"fieldset-cf7mls\"><\/p>#', '$1</fieldset><fieldset class="fieldset-cf7mls">', $code);
        }
        $code = '<fieldset class="fieldset-cf7mls">' . $code;
        if (apply_filters('wpcf7-auto-insert-back-button-on-last-step', true)) {
            $code .= '<input type="button" value="Back" class="cf7mls_back action-button" name="cf7mls_back">';
        }
        $code .= '</fieldset>';
    }
    return $code;
}
