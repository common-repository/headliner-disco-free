<?php
/*
Plugin Name: Headliner Disco Free
Description: Headliner's Disco Free Widget - Turn readers into listeners by recommending your podcast automatically at the end of your blog posts.
Version: 1.3.1
Author: Headliner
Author URI: https://www.headliner.app/
Text Domain: headliner
License: GPLv2
*/

// Define global constants
if ( !defined( 'HEADLINER_DISCO_SCRIPT_URL' ) ) {
    define( 'HEADLINER_DISCO_SCRIPT_URL', 'https://disco.headliner.link/d/web/js/widget.js' );
}

if ( !defined( 'HEADLINER_DISCO_DASHBOARD_API_BASE_URL' ) ) {
    define( 'HEADLINER_DISCO_DASHBOARD_API_BASE_URL', 'https://dashboard-api.headliner.app' );
}

add_filter( 'the_content', 'headliner_div' );
add_action( 'wp_footer', 'headliner_load_script' );

/**
 * Make sure we only run on single posts
 *
 * @param [type] $content
 *
 * @return bool
 */
function headliner_check_for_single_posts() {
	return ( category_included() || tag_included() || headliner_enabled() ) && is_single() && is_main_query();
}

/**
 * Show Headliner div
 *
 * @return void
 */
function headliner_div( $content ) {
	if ( ! headliner_check_for_single_posts() ) {
		return $content;
	}

	return $content . headliner_get_widget_content();
}

/**
 * Get headliner widget content.
 *
 * @return string
 */
function headliner_get_widget_content() {
	$widget_id = headliner_get_option( 'widget_id' );
	if ( empty( $widget_id ) ) {
		return '';
	}

	wp_enqueue_script( 'headliner', HEADLINER_DISCO_SCRIPT_URL, array(), '1.0.0', true );
	return '<div class="disco-widget" data-widget-id="' . $widget_id . '"></div>' . "\n";
}

/**
 * Load Headliner JS in footer
 */
function headliner_load_script() {
	if ( ! headliner_check_for_single_posts() ) {
		return;
	}

	$widget_id = headliner_get_option( 'widget_id' );
	if ( empty( $widget_id ) ) {
		return;
	}

	wp_enqueue_script( 'headliner', HEADLINER_DISCO_SCRIPT_URL, array(), '1.0.0', true );
}

/**
 * Create a new options page for Headliner
 */
add_action( 'admin_menu', 'headliner_menu' );
function headliner_menu() {
	add_options_page( __( 'Headliner Disco Free', 'headliner' ), __( 'Headliner Disco Free', 'headliner' ), 'manage_options', 'headliner', 'headliner_render_settings_page' );
}

function headliner_render_settings_page() {
?>
	<form action="options.php" method="post">
		<?php
		settings_fields( 'headliner_plugin_options' );
		do_settings_sections( 'headliner_plugin' );
		?>
		<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save', 'headliner' ); ?>">
	</form>
	<br />
	Please visit the Headliner <a href="https://dashboard.headliner.app">Dashboard</a> to get your Widget ID for your site.  This plugin doesn't do anything without a valid Widget ID.
	<br />
	<br />
	For more info, please refer to our <a href="https://learn.headliner.app/hc/en-us/articles/13268376887959-How-to-set-up-the-Headliner-Disco-Beta-Wordpress-Plugin">installation guide</a>, <a href="https://learn.headliner.app/hc/en-us/articles/12836546062487-Disco-Self-Serve-Widget-FAQ">FAQs</a>, or contact us at  support@headliner.app.
<?php
}

function headliner_register_settings() {
	register_setting( 'headliner_plugin_options', 'headliner_plugin_options', 'headliner_plugin_options_validate' );
	add_settings_section( 'widget_settings', __( 'Headliner Disco Free Widget Settings', 'headliner' ), 'headliner_render_section_text', 'headliner_plugin' );

	add_settings_field( 'headliner_widget_id', __( 'Widget ID', 'headliner' ), 'headliner_render_widget_id_setting', 'headliner_plugin', 'widget_settings' );
	add_settings_field( 'headliner_show_in_all_posts', __( 'Automatically show widget at the end of all posts', 'headliner' ), 'headliner_render_show_in_all_posts_setting', 'headliner_plugin', 'widget_settings' );
	add_settings_field( 'headliner_show_with_certain_tags', __( '', 'headliner'), 'headliner_render_show_with_certain_tags_settings', 'headliner_plugin', 'widget_settings');
	add_settings_field( 'headliner_show_in_certain_categories', __( '', 'headliner'), 'headliner_render_show_in_certain_categories_settings', 'headliner_plugin', 'widget_settings');
}

add_action( 'admin_init', 'headliner_register_settings' );

function headliner_plugin_options_validate( $value ) {
	if ( empty( $value['widget_id'] ) ) {
		$value['widget_id'] = '';

		add_settings_error( 'headliner_plugin_options', 'headliner_plugin_options', __( 'Please enter a valid widget ID', 'headliner' ) );
	}

	$value['widget_id'] = sanitize_text_field( $value['widget_id'] );

	$is_valid = headliner_validate_widget_id( $value['widget_id'] );
	if ( ! $is_valid ) {
		add_settings_error( 'headliner_plugin_options', 'headliner_plugin_options', __( 'Please enter a valid widget ID', 'headliner' ) );
	}

	$value['show_in_all_posts'] = sanitize_text_field( $value['show_in_all_posts'] );

	return $value;
}

/**
 * Validate Widget id using remote api.
 *
 * @param $widget_id
 *
 * @return bool True if valid, false otherwise.
 */
function headliner_validate_widget_id( $widget_id ) {
	$api_url = HEADLINER_DISCO_DASHBOARD_API_BASE_URL . '/api/v1/publisher/widget-check';

	$response = wp_remote_post( $api_url, array(
		'headers'     => array( 'Content-Type' => 'application/json; charset=utf-8' ),
		'method'      => 'POST',
		'data_format' => 'body',
		'body'        => json_encode( array(
			'widgetId' => $widget_id,
		) ),
	) );

	$response_code = wp_remote_retrieve_response_code( $response );
	if ( $response_code !== 200 ) {
		return false;
	}

	$response_body = json_decode( wp_remote_retrieve_body( $response ) );
	if ( ! $response_body->isValid ) {
		return false;
	}

	return true;
}

function headliner_render_section_text() {
	echo '';
}

function headliner_render_widget_id_setting() {
	$widget_id = headliner_get_option( 'widget_id' , '' );
	echo "<input id='headliner_widget_id' name='headliner_plugin_options[widget_id]' type='text' value='" . esc_attr( $widget_id ) . "' />";
}

function headliner_enqueue_scripts() {
    wp_enqueue_script( 'headliner-settings', plugins_url( 'headliner_settings/render_filter.js', __FILE__ ), array( 'jquery' ), '1.0', true );
}

add_action( 'admin_enqueue_scripts', 'headliner_enqueue_scripts' );

function headliner_render_show_in_all_posts_setting() {
	$options = get_option( 'headliner_plugin_options' );
	$show_in_all_posts = headliner_get_option( 'show_in_all_posts' , 'yes' );

?>

    <input type="radio" name='headliner_plugin_options[show_in_all_posts]' value="yes" <?php checked( 'yes', $show_in_all_posts ); ?>>Yes
    <input type="radio" name='headliner_plugin_options[show_in_all_posts]' value="no" <?php checked( 'no', $show_in_all_posts ); ?>>No

    <p>
        You can automatically embed the widget anywhere using the <code>[headliner_widget]</code> shortcode or by using the <code>headliner_get_widget_content()</code> function in your theme.
        <br />
        Select <code>No</code> to filter where the widget displays by tag and category.
    </p>

<?php

}

function headliner_render_show_with_certain_tags_settings() {
    $show_in_all_posts = headliner_get_option( 'show_in_all_posts' )

?>

    <div id="tag_filter_settings">
        <h4>Tag Filter</h4>
        <?php headliner_render_tag_and_category_settings( 'post_tag' ); ?>
    </div>

<?php

}

function headliner_render_show_in_certain_categories_settings() {
    $show_in_all_posts = headliner_get_option( 'show_in_all_posts' )

?>

    <div id="category_filter_settings">
        <h4>Category Filter</h4>
        <?php headliner_render_tag_and_category_settings( 'category' ); ?>
    </div>

<?php

}

function headliner_render_tag_and_category_settings( $taxonomy ) {

    $all_terms = get_terms( array(
        'taxonomy' => $taxonomy,
        'hide_empty' => true,
    ) );

    $taxonomy_option = $taxonomy === 'post_tag' ? 'allowed_tags' : 'allowed_categories';
    $taxonomy_name = $taxonomy === 'post_tag' ? 'tags' : 'categories';
    $selected_terms = headliner_get_option( $taxonomy_option, array() );
    $has_terms = ! empty( $all_terms );

?>

    <?php if ( $has_terms ) { ?>
        <p>
            Select the <?php echo $taxonomy_name; ?> for which you would like the widget to display.
        </p>
        <br>

        <?php foreach ( $all_terms as $term ) { ?>
            <label>
                <input
                    type="checkbox"
                    name="headliner_plugin_options[<?php echo $taxonomy_option; ?>][]"
                    value="<?php echo $term->term_id; ?>" <?php if ( in_array( $term->term_id, $selected_terms ) ) echo 'checked'; ?>>
                <?php echo $term->name; ?>
            </label>
            <br>
        <?php } ?>

        <br>
        <input type="button" value="Select All" onclick="toggleCheckboxes('<?php echo $taxonomy_option; ?>', true)">
        <input type="button" value="Deselect All" onclick="toggleCheckboxes('<?php echo $taxonomy_option; ?>', false)">

    <?php } else { ?>
        <p>
            You haven't used any <?php echo $taxonomy_name; ?>! Add some to your site to use this setting.
        </p>

<?php

    }
}

/**
 * Is headliner widget enabled?
 *
 * @return bool
 */
function headliner_enabled() {
	$show_in_all_posts = headliner_get_option( 'show_in_all_posts' );
	return ! ( 'no' === $show_in_all_posts );
}

/**
 * Does this post contain a desired tag?
 *
 * @return bool
 */
function tag_included() {
	$selected_tags = headliner_get_option( 'allowed_tags', array() );
    if ( empty( $selected_tags ) ) {
        return false;
    }
	return has_tag( $selected_tags );
}

/**
 * Is this post in a desired category?
 *
 * @return bool
 */
function category_included() {
    $selected_categories = headliner_get_option( 'allowed_categories', array() );
    if ( empty( $selected_categories ) ) {
        return false;
    }
	return in_category( $selected_categories );
}

/**
 * Headliner shortcode.
 *
 * @param $atts
 *
 * @return string
 */
function headliner_widget_shortcode( $atts ) {
	return headliner_get_widget_content();
}
add_shortcode( 'headliner_widget', 'headliner_widget_shortcode' );

/**
 * Add link to the Headliner settings page.
 */
function headliner_add_plugin_page_settings_link( $links ) {
	$url = get_admin_url() . "options-general.php?page=headliner";

	$settings_link = '<a href="' . $url . '">' . __('Settings') . '</a>';
	array_unshift( $links,  $settings_link );
	return $links;
}

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'headliner_add_plugin_page_settings_link' );

function headliner_get_option( $optionName, $defaultValue = null ) {
	$options = get_option( 'headliner_plugin_options' );
	if ( $options && array_key_exists( $optionName, $options ) ) {
		return $options[$optionName];
	}

	return $defaultValue;
}
