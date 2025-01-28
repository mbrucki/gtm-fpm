<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
Plugin Name: GTM First-Party Mode
Description: Routes requests through WordPress backend to fps.goog and inserts GTM script in the header.
Version: 1.20
Author: MeasureLake
Author URI: https://measurelake.com
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
*/

// Add settings menu
add_action('admin_menu', 'gtmfpm_gtm_fpm_menu');

function gtmfpm_gtm_fpm_menu() {
    add_options_page('GTM First-Party Mode Settings', 'GTM First-Party Mode', 'manage_options', 'gtm-fpm-settings', 'gtmfpm_gtm_fpm_settings_page');
}

// Enqueue styles
add_action('admin_enqueue_scripts', 'gtmfpm_gtm_fpm_enqueue_styles');

function gtmfpm_gtm_fpm_enqueue_styles($hook) {
    if ($hook != 'settings_page_gtm-fpm-settings') {
        return;
    }
    wp_register_style('gtm_fpm_admin_css', plugin_dir_url(__FILE__) . 'css/admin-style.css', array(), '1.20');
    wp_enqueue_style('gtm_fpm_admin_css');
}

// Register settings
add_action('admin_init', 'gtmfpm_gtm_fpm_settings_init');

function gtmfpm_gtm_fpm_settings_init() {
    register_setting('gtmfpm_gtm_fpm_settings_group', 'gtmfpm_gtm_id', 'gtmfpm_gtm_fpm_validate_settings');
    register_setting('gtmfpm_gtm_fpm_settings_group', 'gtmfpm_gtm_path', 'gtmfpm_gtm_fpm_validate_settings');

    add_settings_section('gtmfpm_gtm_fpm_settings_section', 'Settings', 'gtmfpm_gtm_fpm_settings_section_cb', 'gtm-fpm-settings');

    add_settings_field('gtmfpm_gtm_id', 'GTM ID', 'gtmfpm_gtm_id_render', 'gtm-fpm-settings', 'gtmfpm_gtm_fpm_settings_section');
    add_settings_field('gtmfpm_gtm_path', 'Tag Serving Path', 'gtmfpm_gtm_path_render', 'gtm-fpm-settings', 'gtmfpm_gtm_fpm_settings_section');
}

function gtmfpm_gtm_fpm_settings_section_cb() {
    echo '<p>Enter your GTM ID and Tag Serving Path below. First-party mode lets you deploy GTM using your own first-party infrastructure, hosted on your website\'s domain. This infrastructure sits between your website and Google\'s services, making your first-party infrastructure the only technology to interact directly with your website users.</p>';
    echo '<p>Note that the use of this plugin does not exempt you from the responsibility of safeguarding user privacy. Collecting or processing user data without explicit consent is subject to legal penalties.</p>';
}

function gtmfpm_gtm_id_render() {
    $gtm_id = get_option('gtmfpm_gtm_id');
    echo '<input type="text" name="gtmfpm_gtm_id" value="' . esc_attr($gtm_id) . '" required />';
    echo '<p class="description">Enter your GTM ID. This field is required.</p>';
}

function gtmfpm_gtm_path_render() {
    $gtm_path = get_option('gtmfpm_gtm_path');
    echo '<input type="text" name="gtmfpm_gtm_path" value="' . esc_attr($gtm_path) . '" required />';
    echo '<p class="description">Enter the path for serving tags. This field is required. Caution: This setup reroutes all traffic with the chosen path. To avoid affecting your website, choose a path that\'s not already in use.</p>';
}

function gtmfpm_gtm_fpm_validate_settings($input) {
    if (empty($input)) {
        add_settings_error(
            'gtmfpm_gtm_fpm_settings_group',
            'gtmfpm_gtm_fpm_settings_error',
            'This field is required',
            'error'
        );
        return ''; // Return an empty string to avoid breaking the saving process
    }

    return sanitize_text_field($input); // Always sanitize user input
}

function gtmfpm_gtm_fpm_settings_page() {
    ?>
    <div class="wrap">
        <h1>GTM First-Party Mode Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('gtmfpm_gtm_fpm_settings_group');
            do_settings_sections('gtm-fpm-settings');
            submit_button();
            ?>
        </form>
    </div>
    <div class="credits-container">
        <div class="credits">
            <h2>Credits</h2>
            <p>Plugin developed by <a href="https://measurelake.com" target="_blank">MeasureLake - analytics tech hub</a></p>
            <p><a href="https://www.linkedin.com/company/measurelake" target="_blank">Follow us on LinkedIn to stay up to date</a></p>
            <p>If you encounter any issues, feel free to <a href="mailto:welcome@measurelake.com">email us</a>.</p>
        </div>
    </div>
    <?php
}

// Add REST API endpoint
add_action('rest_api_init', function () {
    $gtm_id = get_option('gtmfpm_gtm_id');
    $path = trim(get_option('gtmfpm_gtm_path'), '/');
    if ($gtm_id && $path) {
        register_rest_route('gtm/v1', '/' . $path . '(?:/(?P<rest>.*))?', array(
            'methods' => 'GET, POST, PUT, DELETE',
            'callback' => 'gtmfpm_gtm_metrics_callback',
        ));
    }
});

function gtmfpm_gtm_metrics_callback(WP_REST_Request $request) {
    try {
        $gtm_id = get_option('gtmfpm_gtm_id');
        $path = trim(get_option('gtmfpm_gtm_path'), '/');

        if (!$gtm_id || !$path) {
            return new WP_REST_Response('Configuration error', 500);
        }

        $rest = $request->get_param('rest') ? $request->get_param('rest') : '';
        $query_params = $request->get_query_params();
        $query_string = http_build_query($query_params);
        $url = 'https://' . $gtm_id . '.fps.goog/' . $path . '/' . $rest;
        if ($query_string) {
            $url .= '?' . $query_string;
        }

        // NEW: Append IP
        $url = gtmfpm_append_request_ip($url);

        $args = array(
            'method' => $request->get_method(),
            'headers' => array(
                'Host' => $gtm_id . '.fps.goog',
                'Accept' => 'application/javascript',
                'User-Agent' => $request->get_header('user-agent'),
                'Accept-Language' => $request->get_header('accept-language'),
                'Accept-Encoding' => '', // Indicating no encoding preference
            ),
        );

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return new WP_REST_Response('Error forwarding request', 500);
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_headers = wp_remote_retrieve_headers($response);

        foreach ($response_headers as $key => $value) {
            header("$key: $value");
        }
        header('Content-Type: application/javascript; charset=UTF-8');
        echo $response_body;
        exit;
    } catch (Exception $e) {
        return new WP_REST_Response('Internal Server Error', 500);
    }
}

// Add the rewrite rule
add_action('init', function() {
    $path = trim(get_option('gtmfpm_gtm_path'), '/');
    if ($path) {
        add_rewrite_rule('^' . $path . '(?:/(.*))?', 'index.php?rest_route=/gtm/v1/' . $path . '/$matches[1]', 'top');
    }
});

// Flush rewrite rules on activation
register_activation_hook(__FILE__, 'gtmfpm_gtm_fpm_plugin_activation');
function gtmfpm_gtm_fpm_plugin_activation() {
    flush_rewrite_rules();
}

// Flush rewrite rules on deactivation
register_deactivation_hook(__FILE__, 'gtmfpm_gtm_fpm_plugin_deactivation');
function gtmfpm_gtm_fpm_plugin_deactivation() {
    flush_rewrite_rules();
}

// Flush rewrite rules after settings are updated
add_action('admin_init', 'gtmfpm_maybe_flush_rewrite_rules');
function gtmfpm_maybe_flush_rewrite_rules() {
    if (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true') {
        flush_rewrite_rules();
    }
}

// Add GTM script to head
add_action('wp_head', 'gtmfpm_insert_gtm_script');

function gtmfpm_insert_gtm_script() {
    $gtm_id = get_option('gtmfpm_gtm_id');
    $path = trim(get_option('gtmfpm_gtm_path'), '/');

    if ($gtm_id && $path) {
        echo "<!-- Google Tag Manager -->
<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
'/" . esc_js($path) . "/?id='+i+dl;f.parentNode.insertBefore(j,f);
})(window,document,'script','dataLayer','');</script>
<!-- End Google Tag Manager -->";
    } else {
        wp_register_script('gtmfpm-inline-script', '');
        wp_enqueue_script('gtmfpm-inline-script');
        wp_add_inline_script('gtmfpm-inline-script', "console.log('GTM script not inserted. GTM ID or Path is missing.');");
    }
}

/**
 * NEW FUNCTION:
 * Appends client IP (or X-Forwarded-For) to the query:
 * - If /g/collect is detected, it uses _uip=...
 * - Otherwise, it uses uip=...
 */
function gtmfpm_append_request_ip($url) {
    // Retrieve IP address
    $requestIP = $_SERVER['REMOTE_ADDR'] ?? '';
    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $requestIP = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }

    // Validate IP address
    if (!filter_var($requestIP, FILTER_VALIDATE_IP)) {
        $requestIP = '';
    }

    if ($requestIP) {
        // If there's already a ?_uip= or &uip=, replace it
        if (preg_match('/([?&])_?uip=([^&]*)/', $url)) {
            // Replace existing _uip or uip param
            $url = preg_replace('/([?&])_?uip=([^&]*)/', '${1}_uip=' . urlencode($requestIP), $url);
        } else {
            // Otherwise, append
            $separator = (strpos($url, '?') !== false) ? '&' : '?';
            if (strpos($url, '/g/collect') !== false) {
                $url .= $separator . '_uip=' . urlencode($requestIP);
            } else {
                $url .= $separator . 'uip=' . urlencode($requestIP);
            }
        }
    }

    return $url;
}
