<?php

if (!defined('ABSPATH')) {
    exit;
}

//add js, css
add_action('admin_enqueue_scripts', 'cf7mls_admin_scripts_callback');
function cf7mls_admin_scripts_callback($hook)
{
    if ($hook == 'toplevel_page_wpcf7') {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');
        
        wp_register_script('cf7mls', CF7MLS_PLUGIN_URL . '/assets/admin/js/cf7mls.js', array('jquery'));
        wp_enqueue_script('cf7mls');
        
        wp_register_style('cf7mls', CF7MLS_PLUGIN_URL . '/assets/admin/css/cf7mls.css');
        wp_enqueue_style('cf7mls');
    }
}

/**
 * Add step buttin to the wpcf7 tag generator.
 */
function cf7mls_add_tag_generator_multistep()
{
    if (class_exists('WPCF7_TagGenerator')) {
        $tag = WPCF7_TagGenerator::get_instance();
        $tag->add('cf7mls_step', __('Step', 'cf7mls'), 'cf7mls_multistep_tag_generator_callback');
    }
}
add_action('admin_init', 'cf7mls_add_tag_generator_multistep', 30);
/**
 * [cf7mls_multistep_tag_generator_callback description]
 */
function cf7mls_multistep_tag_generator_callback($contact_form, $args = '')
{
    $args = wp_parse_args($args, array());
?>
<div class="control-box">
    <fieldset>
        <legend><?php _e('Generate buttons for form\'s steps.', 'cf7mls');?></legend>
        <table class="form-table cf7mls-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="tag-generator-panel-cf7mls_step-name"><?php _e('Name', 'cf7mls'); ?></label></th>
                    <td><input type="text" id="tag-generator-panel-cf7mls_step-name" class="tg-name oneline" name="name"></td>
                </tr>
                <tr>
                    <th scope="row">                        
                        <label for="tag-generator-panel-cf7mls_step-btns-title"><?php _e('Back, Next Buttons Title', 'cf7mls'); ?></label>
                    </th>
                    <td>
                        <?php cf7mls_premium_only(); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </fieldset>
</div>
<div class="insert-box">
    
    <input type="text" name="cf7mls_step" class="tag code" readonly="readonly" onfocus="this.select()" />

    <div class="submitbox">
        <input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr(__('Insert Tag', 'cf7mls')); ?>" />
    </div>

    <br class="clear" />

    <p class="description mail-tag"><label><?php echo esc_html(__("This field should not be used on the Mail tab.", 'cf7mls')); ?></label>
    </p>
</div>
<?php
}
