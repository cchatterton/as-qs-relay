<?php

if (!defined('ABSPATH')) {
    exit;
}

add_action('admin_menu', 'asqr_add_admin_page');
add_action('admin_init', 'asqr_register_settings');

function asqr_add_admin_page(): void
{
    add_options_page(
        __('AS QS Relay', 'as-qs-relay'),
        __('QS Relay', 'as-qs-relay'),
        'manage_options',
        'as-qs-relay',
        'asqr_render_admin_page'
    );
}

function asqr_register_settings(): void
{
    register_setting('asqr_settings', ASQR_OPTION_NAME, array(
        'type' => 'array',
        'sanitize_callback' => 'asqr_sanitize_options',
        'default' => asqr_default_options(),
    ));
}

function asqr_sanitize_options(array $input): array
{
    $keys = asqr_sanitize_query_keys((array) ($input['tracked_query_keys'] ?? array()));

    return array(
        'tracked_query_keys' => !empty($keys) ? $keys : asqr_default_query_keys(),
    );
}

function asqr_render_admin_page(): void
{
    if (!current_user_can('manage_options')) {
        return;
    }

    $options = asqr_get_options();
    $keys = (array) ($options['tracked_query_keys'] ?? array());
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('AS QS Relay', 'as-qs-relay'); ?></h1>
        <form method="post" action="options.php">
            <?php settings_fields('asqr_settings'); ?>
            <h2><?php echo esc_html__('Tracked query string keys', 'as-qs-relay'); ?></h2>
            <p><?php echo esc_html__('Add the query-string keys that should be captured into the relay cookie over time.', 'as-qs-relay'); ?></p>
            <table class="widefat striped asqr-key-table" style="max-width:640px;">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Query key', 'as-qs-relay'); ?></th>
                        <th style="width:110px;"><?php echo esc_html__('Action', 'as-qs-relay'); ?></th>
                    </tr>
                </thead>
                <tbody data-asqr-key-list>
                    <?php foreach ($keys as $key) : ?>
                        <?php asqr_render_key_row((string) $key); ?>
                    <?php endforeach; ?>
                    <?php if (empty($keys)) : ?>
                        <?php asqr_render_key_row(''); ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <p>
                <button type="button" class="button" data-asqr-add-key><?php echo esc_html__('Add query key', 'as-qs-relay'); ?></button>
            </p>
            <?php submit_button(); ?>
        </form>
        <template data-asqr-key-template>
            <?php asqr_render_key_row(''); ?>
        </template>
    </div>
    <?php
}

function asqr_render_key_row(string $key): void
{
    ?>
    <tr>
        <td>
            <input class="regular-text" type="text" name="<?php echo esc_attr(ASQR_OPTION_NAME); ?>[tracked_query_keys][]" value="<?php echo esc_attr($key); ?>" placeholder="<?php echo esc_attr__('utm_source', 'as-qs-relay'); ?>">
        </td>
        <td>
            <button type="button" class="button-link-delete" data-asqr-remove-key><?php echo esc_html__('Remove', 'as-qs-relay'); ?></button>
        </td>
    </tr>
    <?php
}
