<?php
/**
 * PropertyKoi Live Data Sync Bridge
 * Drop this file into your WordPress root directory (same folder as wp-config.php and wp-load.php).
 * Allows secure export of properties, users, leads, and demands for Laravel migration.
 */

if (!defined('ABSPATH')) {
    if (file_exists(__DIR__ . '/wp-load.php')) {
        require_once __DIR__ . '/wp-load.php';
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'wp-load.php not found. Ensure this file is placed in your WordPress root directory.']);
        exit;
    }
}

header('Content-Type: application/json; charset=UTF-8');
header('X-Robots-Tag: noindex, nofollow');

// Check authentication via Application Password or Admin Session
$user = wp_get_current_user();
if (!$user || !$user->ID || !current_user_can('manage_options')) {
    // Check Basic Auth header for Application Password
    if (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
        $authUser = wp_authenticate_application_password(null, $_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
        if (is_a($authUser, 'WP_User')) {
            wp_set_current_user($authUser->ID);
            $user = $authUser;
        }
    }
}

if (!$user || !current_user_can('manage_options')) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Provide valid WordPress Administrator credentials or Application Password.',
    ]);
    exit;
}

global $wpdb;

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'ping';

if ($action === 'ping') {
    $table_props = $wpdb->prefix . 'adm_properties';
    $table_leads = $wpdb->prefix . 'adm_leads';
    $table_demands = $wpdb->prefix . 'adm_buyer_requirements';

    echo json_encode([
        'success' => true,
        'site_name' => get_bloginfo('name'),
        'site_url' => home_url(),
        'authenticated_user' => $user->user_login,
        'user_display_name' => $user->display_name,
        'wp_version' => get_bloginfo('version'),
        'counts' => [
            'properties' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_props}"),
            'leads' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_leads}"),
            'demands' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_demands}"),
            'users' => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->users}"),
        ],
    ]);
    exit;
}

if ($action === 'export') {
    $limit = max(1, min(500, isset($_GET['limit']) ? (int) $_GET['limit'] : 100));
    $offset = max(0, isset($_GET['offset']) ? (int) $_GET['offset'] : 0);

    $table_props   = $wpdb->prefix . 'adm_properties';
    $table_land    = $wpdb->prefix . 'adm_property_land_details';
    $table_flat    = $wpdb->prefix . 'adm_property_flat_details';
    $table_comm    = $wpdb->prefix . 'adm_property_commercial_details';
    $table_share   = $wpdb->prefix . 'adm_property_share_details';
    $table_sources = $wpdb->prefix . 'adm_property_direct_sources';
    $table_media   = $wpdb->prefix . 'adm_property_media';
    $table_verif   = $wpdb->prefix . 'adm_property_verifications';
    $table_leads   = $wpdb->prefix . 'adm_leads';
    $table_visits  = $wpdb->prefix . 'adm_visit_bookings';
    $table_demands = $wpdb->prefix . 'adm_buyer_requirements';
    $table_reviews = $wpdb->prefix . 'adm_reviews';

    $response = [
        'success' => true,
        'generated_at' => current_time('mysql'),
        'properties' => [],
        'leads' => [],
        'visit_bookings' => [],
        'demands' => [],
        'users' => [],
        'reviews' => [],
    ];

    $prop_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_props} ORDER BY id ASC LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A);

    if (!empty($prop_rows)) {
        foreach ($prop_rows as $prop) {
            $pid = (int) $prop['id'];
            $prop['land_details'] = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_land} WHERE property_id = %d", $pid), ARRAY_A);
            $prop['flat_details'] = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_flat} WHERE property_id = %d", $pid), ARRAY_A);
            $prop['commercial_details'] = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_comm} WHERE property_id = %d", $pid), ARRAY_A);
            $prop['share_details'] = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table_share} WHERE property_id = %d", $pid), ARRAY_A);
            $prop['direct_sources'] = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_sources} WHERE property_id = %d", $pid), ARRAY_A);
            $prop['media'] = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_media} WHERE property_id = %d", $pid), ARRAY_A);
            $prop['verifications'] = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table_verif} WHERE property_id = %d", $pid), ARRAY_A);

            $response['properties'][] = $prop;
        }
    }

    $response['leads'] = $wpdb->get_results("SELECT * FROM {$table_leads} ORDER BY id ASC LIMIT 200", ARRAY_A) ?: [];
    $response['visit_bookings'] = $wpdb->get_results("SELECT * FROM {$table_visits} ORDER BY id ASC LIMIT 200", ARRAY_A) ?: [];
    $response['demands'] = $wpdb->get_results("SELECT * FROM {$table_demands} ORDER BY id ASC LIMIT 200", ARRAY_A) ?: [];
    $response['reviews'] = $wpdb->get_results("SELECT * FROM {$table_reviews} ORDER BY id ASC LIMIT 200", ARRAY_A) ?: [];

    $users = get_users(['number' => 200]);
    if (!empty($users)) {
        foreach ($users as $u) {
            $response['users'][] = [
                'ID' => $u->ID,
                'user_login' => $u->user_login,
                'user_email' => $u->user_email,
                'display_name' => $u->display_name,
                'phone' => get_user_meta($u->ID, 'phone', true) ?: (get_user_meta($u->ID, 'adm_phone', true) ?: ''),
                'whatsapp_number' => get_user_meta($u->ID, 'whatsapp_number', true) ?: (get_user_meta($u->ID, 'adm_whatsapp', true) ?: ''),
                'role' => in_array('administrator', (array) $u->roles) ? 'admin' : (in_array('agent', (array) $u->roles) ? 'agent' : 'user'),
                'created_at' => $u->user_registered,
            ];
        }
    }

    echo json_encode($response);
    exit;
}

http_response_code(400);
echo json_encode(['error' => 'Invalid action parameter']);
exit;
