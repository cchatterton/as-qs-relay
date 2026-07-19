<?php

if (!defined('ABSPATH')) {
    exit;
}

function asqr_boot(): void
{
    add_action('init', 'asqr_process_request', 1);
    add_action('wp_enqueue_scripts', 'asqr_enqueue_assets');
    add_action('admin_enqueue_scripts', 'asqr_enqueue_admin_assets');
    asqr_register_github_updater();
}

function asqr_process_request(): void
{
    if (headers_sent()) {
        return;
    }

    if (asqr_is_reset_command()) {
        asqr_delete_cookie();
        return;
    }

    $payload = asqr_cookie_payload();

    if (asqr_is_ia_hydrate_command()) {
        $ia_touch = asqr_current_independent_analytics_touch();
        if (!empty($ia_touch)) {
            asqr_write_cookie(asqr_add_touch($payload, $ia_touch));
        }
        return;
    }

    $query = asqr_tracked_values_from_query($_GET);
    if (empty($query)) {
        return;
    }

    asqr_write_cookie(asqr_add_touch($payload, array(
        'source' => 'query_string',
        'url' => asqr_current_url(),
        'query' => $query,
    )));
}
