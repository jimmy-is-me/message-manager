<?php
/**
 * 插件啟用處理類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class LMM_Activator {
    
    /**
     * 插件啟用時執行
     */
    public static function activate() {
        // 建立資料表
        LMM_Database::create_tables();
        
        // 設定預設選項
        self::set_default_options();
        
        // 清除重寫規則
        flush_rewrite_rules();
        
        // 記錄啟用時間
        update_option('lmm_activated_time', current_time('timestamp'));
    }
    
    /**
     * 設定預設選項
     */
    private static function set_default_options() {
        $defaults = array(
            'lmm_chat_title' => '線上客服',
            'lmm_chat_placeholder' => '請輸入您的問題...',
            'lmm_require_name' => 'no',
            'lmm_require_email' => 'no',
            'lmm_require_phone' => 'no',
            'lmm_chat_position' => 'bottom-right',
            'lmm_primary_color' => '#06c755',
            'lmm_discord_notifications' => 'yes'
        );
        
        foreach ($defaults as $key => $value) {
            if (get_option($key) === false) {
                update_option($key, $value);
            }
        }
    }
    
    /**
     * 檢查並修復資料表
     */
    public static function check_and_repair_tables() {
        global $wpdb;
        
        $tables = array(
            $wpdb->prefix . 'lmm_messages',
            $wpdb->prefix . 'lmm_quick_replies'
        );
        
        $missing_tables = array();
        
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") !== $table) {
                $missing_tables[] = $table;
            }
        }
        
        if (!empty($missing_tables)) {
            LMM_Database::create_tables();
            return $missing_tables;
        }
        
        return false;
    }
}
