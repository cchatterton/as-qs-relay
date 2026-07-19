<?php

if (!defined('ABSPATH')) {
    exit;
}

function asqr_enqueue_assets(): void
{
    wp_enqueue_script(
        'asqr-relay',
        ASQR_PLUGIN_URL . 'scripts/as-qs-relay.js',
        array(),
        ASQR_VERSION,
        true
    );

    wp_add_inline_script(
        'asqr-relay',
        'window.ASQR_QS_RELAY = ' . wp_json_encode(asqr_current_payload()) . ';',
        'before'
    );
}

function asqr_enqueue_admin_assets(string $hook): void
{
    if ('settings_page_as-qs-relay' !== $hook) {
        return;
    }

    wp_enqueue_script(
        'asqr-admin',
        ASQR_PLUGIN_URL . 'scripts/as-qs-relay-admin.js',
        array(),
        ASQR_VERSION,
        true
    );
}
