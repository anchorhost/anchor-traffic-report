<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class ATR_Schema {

    public static function install(): void {
        global $wpdb;
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $reports = atr_table( 'reports' );
        $traffic = atr_table( 'traffic_hourly' );
        $dim     = atr_table( 'dimensions' );
        $plat    = atr_table( 'platforms' );
        $comm    = atr_table( 'comments' );
        $press   = atr_table( 'press_pickups' );
        $tl      = atr_table( 'timeline' );
        $snaps   = atr_table( 'snapshots' );

        $sql = [];

        $sql[] = "CREATE TABLE {$snaps} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            captured_at DATETIME NOT NULL,
            label VARCHAR(190) NULL,
            note VARCHAR(255) NULL,
            payload LONGTEXT NOT NULL,
            position INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY report_captured (report_id, captured_at)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$reports} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            slug VARCHAR(190) NOT NULL,
            title VARCHAR(255) NOT NULL,
            post_url TEXT NULL,
            post_published_at DATETIME NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            kicker VARCHAR(120) NULL,
            headline_html TEXT NULL,
            hero_subtitle_html TEXT NULL,
            hero_stats LONGTEXT NULL,
            totals LONGTEXT NULL,
            context_callout_html TEXT NULL,
            section_ledes LONGTEXT NULL,
            config LONGTEXT NULL,
            refreshed_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY slug (slug)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$traffic} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            hour_utc DATETIME NOT NULL,
            visits INT UNSIGNED NOT NULL DEFAULT 0,
            pageviews INT UNSIGNED NOT NULL DEFAULT 0,
            partial TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            UNIQUE KEY report_hour (report_id, hour_utc),
            KEY report_id (report_id)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$dim} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            dim_kind VARCHAR(40) NOT NULL,
            d_key VARCHAR(190) NOT NULL,
            label VARCHAR(190) NULL,
            note VARCHAR(255) NULL,
            visits INT UNSIGNED NOT NULL DEFAULT 0,
            pageviews INT UNSIGNED NOT NULL DEFAULT 0,
            position INT NOT NULL DEFAULT 0,
            meta LONGTEXT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY report_kind_key (report_id, dim_kind, d_key),
            KEY report_kind (report_id, dim_kind)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$plat} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            kind VARCHAR(40) NOT NULL,
            label VARCHAR(190) NOT NULL,
            badge VARCHAR(8) NULL,
            accent VARCHAR(20) NULL,
            url TEXT NULL,
            posted_at DATETIME NULL,
            posted_label VARCHAR(120) NULL,
            headline_html TEXT NULL,
            stats LONGTEXT NULL,
            meta_html TEXT NULL,
            position INT NOT NULL DEFAULT 0,
            size VARCHAR(20) NOT NULL DEFAULT 'standard',
            PRIMARY KEY (id),
            KEY report_id (report_id, position)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$comm} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            source_kind VARCHAR(40) NOT NULL,
            author VARCHAR(120) NOT NULL,
            handle_html VARCHAR(255) NULL,
            role_label VARCHAR(40) NULL,
            score INT NULL,
            body_html TEXT NOT NULL,
            url TEXT NULL,
            source_label VARCHAR(190) NULL,
            posted_at DATETIME NULL,
            featured TINYINT(1) NOT NULL DEFAULT 0,
            avatar VARCHAR(8) NULL,
            avatar_style VARCHAR(40) NULL,
            position INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY report_id (report_id, position)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$press} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            outlet VARCHAR(120) NOT NULL,
            author VARCHAR(120) NULL,
            url TEXT NOT NULL,
            published_at DATETIME NULL,
            notes_html TEXT NULL,
            position INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY report_id (report_id, position)
        ) {$charset};";

        $sql[] = "CREATE TABLE {$tl} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            report_id BIGINT(20) UNSIGNED NOT NULL,
            event_at DATETIME NOT NULL,
            label VARCHAR(190) NOT NULL,
            description_html TEXT NULL,
            marker VARCHAR(20) NOT NULL DEFAULT 'info',
            chart_marker TINYINT(1) NOT NULL DEFAULT 0,
            chart_label VARCHAR(190) NULL,
            position INT NOT NULL DEFAULT 0,
            PRIMARY KEY (id),
            KEY report_id (report_id, position)
        ) {$charset};";

        foreach ( $sql as $q ) dbDelta( $q );

        update_option( 'atr_db_version', ATR_DB_VERSION );
    }

    public static function maybe_upgrade(): void {
        $current = (int) get_option( 'atr_db_version', 0 );
        if ( $current < ATR_DB_VERSION ) self::install();
    }
}
