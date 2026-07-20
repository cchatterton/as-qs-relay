<?php

if (!defined('ABSPATH')) {
    exit;
}

function asqr_is_reset_command(): bool
{
    return isset($_GET['asqr_reset']) || 'reset' === sanitize_key((string) ($_GET['as_qs_relay'] ?? ''));
}

function asqr_is_ia_hydrate_command(): bool
{
    return isset($_GET['asqr_from_ia']) || 'ia' === sanitize_key((string) ($_GET['as_qs_relay'] ?? ''));
}

function asqr_cookie_payload(): array
{
    $raw = isset($_COOKIE[ASQR_COOKIE_NAME]) ? (string) wp_unslash($_COOKIE[ASQR_COOKIE_NAME]) : '';
    $payload = $raw ? asqr_decode_cookie_json($raw) : array();

    if (!is_array($payload)) {
        $payload = array();
    }

    return asqr_simplify_payload($payload);
}

function asqr_add_touch(array $payload, array $touch): array
{
    $query = asqr_normalise_query_values((array) ($touch['query'] ?? $touch['utm'] ?? array()));
    if (empty($query)) {
        return $payload;
    }

    $payload = asqr_simplify_payload($payload);
    $now = asqr_unique_timestamp($payload, gmdate('c'));
    $payload[$now] = $query;

    return asqr_fit_payload_to_cookie($payload);
}

function asqr_decode_cookie_json(string $raw): array
{
    $candidates = array($raw, rawurldecode($raw), rawurldecode(rawurldecode($raw)));

    foreach ($candidates as $candidate) {
        $decoded = json_decode($candidate, true);
        if (is_array($decoded)) {
            return $decoded;
        }
    }

    return array();
}

function asqr_simplify_payload(array $payload): array
{
    if (isset($payload['touches']) && is_array($payload['touches'])) {
        $simple = array();

        foreach ($payload['touches'] as $touch) {
            if (!is_array($touch)) {
                continue;
            }

            $timestamp = (string) ($touch['first_seen_at'] ?? $touch['last_seen_at'] ?? '');
            $values = asqr_normalise_query_values((array) ($touch['query'] ?? $touch['utm'] ?? array()));

            if ('' !== $timestamp && !empty($values)) {
                $simple[$timestamp] = $values;
            }
        }

        return asqr_fit_payload_to_cookie($simple);
    }

    $simple = array();

    foreach ($payload as $timestamp => $values) {
        if (!is_string($timestamp) || !is_array($values)) {
            continue;
        }

        $values = asqr_normalise_query_values($values);
        if (!empty($values)) {
            $simple[$timestamp] = $values;
        }
    }

    ksort($simple);
    return array_slice($simple, -ASQR_MAX_TOUCHES, null, true);
}

function asqr_normalise_query_values(array $values): array
{
    $normalised = array();

    foreach (asqr_tracked_query_keys() as $key) {
        $value = isset($values[$key]) ? sanitize_text_field((string) $values[$key]) : '';
        $value = trim($value);
        if ('' !== $value) {
            $normalised[$key] = substr($value, 0, 160);
        }
    }

    return $normalised;
}

function asqr_unique_timestamp(array $payload, string $timestamp): string
{
    if (!isset($payload[$timestamp])) {
        return $timestamp;
    }

    $index = 2;
    do {
        $candidate = $timestamp . '.' . $index;
        $index++;
    } while (isset($payload[$candidate]));

    return $candidate;
}

function asqr_tracked_values_from_query(array $query): array
{
    $values = array();

    foreach (asqr_tracked_query_keys() as $key) {
        if (isset($query[$key]) && !is_array($query[$key])) {
            $values[$key] = sanitize_text_field((string) wp_unslash($query[$key]));
        }
    }

    return asqr_normalise_query_values($values);
}

function asqr_tracked_values_from_url(string $url): array
{
    $query = wp_parse_url($url, PHP_URL_QUERY);
    if (!$query) {
        return array();
    }

    parse_str($query, $params);
    return asqr_tracked_values_from_query(is_array($params) ? $params : array());
}

function asqr_default_query_keys(): array
{
    return array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'utm_id');
}

function asqr_default_options(): array
{
    return array(
        'tracked_query_keys' => asqr_default_query_keys(),
    );
}

function asqr_get_options(): array
{
    $options = get_option(ASQR_OPTION_NAME, array());
    $options = wp_parse_args(is_array($options) ? $options : array(), asqr_default_options());
    $options['tracked_query_keys'] = asqr_sanitize_query_keys((array) ($options['tracked_query_keys'] ?? array()));

    if (empty($options['tracked_query_keys'])) {
        $options['tracked_query_keys'] = asqr_default_query_keys();
    }

    return $options;
}

function asqr_tracked_query_keys(): array
{
    $options = asqr_get_options();
    return (array) $options['tracked_query_keys'];
}

function asqr_sanitize_query_keys(array $keys): array
{
    $clean = array();

    foreach ($keys as $key) {
        $key = strtolower(trim(sanitize_text_field((string) $key)));
        $key = preg_replace('/[^a-z0-9_\\-]/', '', $key);

        if (is_string($key) && '' !== $key && !in_array($key, $clean, true)) {
            $clean[] = substr($key, 0, 80);
        }
    }

    return array_values($clean);
}

function asqr_write_cookie(array $payload): void
{
    $payload = asqr_simplify_payload($payload);
    $json = wp_json_encode($payload, JSON_UNESCAPED_SLASHES);
    if (!is_string($json)) {
        return;
    }

    setrawcookie(ASQR_COOKIE_NAME, $json, asqr_cookie_options(time() + ASQR_COOKIE_TTL));
    $_COOKIE[ASQR_COOKIE_NAME] = $json;
    $GLOBALS['asqr_qs_relay_payload'] = $payload;
}

function asqr_delete_cookie(): void
{
    setrawcookie(ASQR_COOKIE_NAME, '', asqr_cookie_options(time() - HOUR_IN_SECONDS));
    unset($_COOKIE[ASQR_COOKIE_NAME]);
    $GLOBALS['asqr_qs_relay_payload'] = array();
}

function asqr_cookie_options(int $expires): array
{
    $path = defined('COOKIEPATH') && COOKIEPATH ? COOKIEPATH : '/';
    $domain = defined('COOKIE_DOMAIN') ? COOKIE_DOMAIN : '';

    $options = array(
        'expires' => $expires,
        'path' => $path,
        'secure' => is_ssl(),
        'httponly' => false,
        'samesite' => 'Lax',
    );

    if (!empty($domain)) {
        $options['domain'] = $domain;
    }

    return $options;
}

function asqr_fit_payload_to_cookie(array $payload): array
{
    while (strlen((string) wp_json_encode($payload, JSON_UNESCAPED_SLASHES)) > 3500 && count($payload) > 1) {
        array_shift($payload);
    }

    return $payload;
}

function asqr_current_url(): string
{
    $scheme = is_ssl() ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field((string) wp_unslash($_SERVER['HTTP_HOST'])) : wp_parse_url(home_url(), PHP_URL_HOST);
    $uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field((string) wp_unslash($_SERVER['REQUEST_URI'])) : '/';
    return esc_url_raw($scheme . $host . $uri);
}

function asqr_current_payload(): array
{
    if (isset($GLOBALS['asqr_qs_relay_payload']) && is_array($GLOBALS['asqr_qs_relay_payload'])) {
        return $GLOBALS['asqr_qs_relay_payload'];
    }

    return asqr_cookie_payload();
}

function asqr_ia_table(string $name): string
{
    global $wpdb;
    return $wpdb->prefix . 'independent_analytics_' . $name;
}

function asqr_table_exists(string $table): bool
{
    global $wpdb;
    return (bool) $wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table));
}

function asqr_column_exists(string $table, string $column): bool
{
    global $wpdb;
    return (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
}

function asqr_current_independent_analytics_touch(): array
{
    $session_id = asqr_current_independent_analytics_session_id();
    if ($session_id <= 0) {
        return array();
    }

    $query = asqr_independent_analytics_session_query_values($session_id);
    if (empty($query)) {
        return array();
    }

    return array(
        'source' => 'independent_analytics',
        'url' => asqr_current_url(),
        'query' => $query,
    );
}

function asqr_current_independent_analytics_session_id(): int
{
    if (!class_exists('\\IAWP\\Models\\Visitor')) {
        return 0;
    }

    try {
        $visitor = \IAWP\Models\Visitor::fetch_current_visitor();
        return method_exists($visitor, 'most_recent_session_id') ? (int) $visitor->most_recent_session_id() : 0;
    } catch (Throwable $exception) {
        return 0;
    }
}

function asqr_independent_analytics_session_query_values(int $session_id): array
{
    global $wpdb;

    $sessions = asqr_ia_table('sessions');
    $campaigns = asqr_ia_table('campaigns');

    if (!asqr_table_exists($sessions) || !asqr_table_exists($campaigns) || !asqr_column_exists($sessions, 'campaign_id')) {
        return asqr_independent_analytics_url_fallback($session_id);
    }

    $joins = asqr_campaign_joins();
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT
            " . asqr_campaign_select('source') . " AS utm_source,
            " . asqr_campaign_select('medium') . " AS utm_medium,
            " . asqr_campaign_select('campaign') . " AS utm_campaign,
            " . asqr_campaign_select('term') . " AS utm_term,
            " . asqr_campaign_select('content') . " AS utm_content
        FROM {$sessions} s
        LEFT JOIN {$campaigns} c ON c.campaign_id = s.campaign_id
        {$joins}
        WHERE s.session_id = %d
        LIMIT 1",
        $session_id
    ), ARRAY_A);

    $query = asqr_normalise_query_values(is_array($row) ? $row : array());
    return !empty($query) ? $query : asqr_independent_analytics_url_fallback($session_id);
}

function asqr_independent_analytics_url_fallback(int $session_id): array
{
    global $wpdb;

    $views = asqr_ia_table('views');
    $resources = asqr_ia_table('resources');

    if (!asqr_table_exists($views) || !asqr_table_exists($resources)) {
        return array();
    }

    $url = (string) $wpdb->get_var($wpdb->prepare(
        "SELECT r.cached_url
        FROM {$views} v
        JOIN {$resources} r ON r.id = v.resource_id
        WHERE v.session_id = %d
        ORDER BY v.viewed_at ASC, v.id ASC
        LIMIT 1",
        $session_id
    ));

    return $url ? asqr_tracked_values_from_url($url) : array();
}

function asqr_campaign_select(string $field): string
{
    $campaigns = asqr_ia_table('campaigns');
    $legacy_column = array(
        'source' => 'utm_source',
        'medium' => 'utm_medium',
        'campaign' => 'utm_campaign',
        'term' => 'utm_term',
        'content' => 'utm_content',
    )[$field];
    $expressions = array();

    if ('source' === $field && asqr_table_exists(asqr_ia_table('utm_sources')) && asqr_column_exists($campaigns, 'utm_source_id')) {
        $expressions[] = 'utm_sources.utm_source';
    }

    if ('medium' === $field && asqr_table_exists(asqr_ia_table('utm_mediums')) && asqr_column_exists($campaigns, 'utm_medium_id')) {
        $expressions[] = 'utm_mediums.utm_medium';
    }

    if ('campaign' === $field && asqr_table_exists(asqr_ia_table('utm_campaigns')) && asqr_column_exists($campaigns, 'utm_campaign_id')) {
        $expressions[] = 'utm_campaigns.utm_campaign';
    }

    if (asqr_column_exists($campaigns, $legacy_column)) {
        $expressions[] = 'c.' . $legacy_column;
    }

    if (empty($expressions)) {
        return "''";
    }

    return count($expressions) > 1 ? 'COALESCE(' . implode(', ', $expressions) . ')' : $expressions[0];
}

function asqr_campaign_joins(): string
{
    $campaigns = asqr_ia_table('campaigns');
    $joins = array();

    if (asqr_table_exists(asqr_ia_table('utm_sources')) && asqr_column_exists($campaigns, 'utm_source_id')) {
        $joins[] = 'LEFT JOIN ' . asqr_ia_table('utm_sources') . ' utm_sources ON utm_sources.id = c.utm_source_id';
    }

    if (asqr_table_exists(asqr_ia_table('utm_mediums')) && asqr_column_exists($campaigns, 'utm_medium_id')) {
        $joins[] = 'LEFT JOIN ' . asqr_ia_table('utm_mediums') . ' utm_mediums ON utm_mediums.id = c.utm_medium_id';
    }

    if (asqr_table_exists(asqr_ia_table('utm_campaigns')) && asqr_column_exists($campaigns, 'utm_campaign_id')) {
        $joins[] = 'LEFT JOIN ' . asqr_ia_table('utm_campaigns') . ' utm_campaigns ON utm_campaigns.id = c.utm_campaign_id';
    }

    return implode("\n", $joins);
}
