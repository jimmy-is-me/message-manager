<?php
/**
 * 插件卸載腳本
 * 
 * 當插件被刪除時執行
 */

// 如果不是透過WordPress執行，則退出
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// 刪除選項
$options = array(
    'lmm_discord_webhook_url',
    'lmm_discord_notifications',
    'lmm_chat_title',
    'lmm_chat_placeholder',
    'lmm_require_name',
    'lmm_require_email',
    'lmm_require_phone',
    'lmm_chat_position',
    'lmm_primary_color',
    'lmm_db_version'
);

foreach ($options as $option) {
    delete_option($option);
}

// 刪除資料表（注意：這會永久刪除所有訊息記錄）
$table_messages = $wpdb->prefix . 'lmm_messages';
$wpdb->query("DROP TABLE IF EXISTS $table_messages");

// 清除任何暫存的快取
wp_cache_flush();
