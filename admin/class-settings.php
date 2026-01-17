<?php
/**
 * 設定管理類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class LMM_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_lmm_test_discord', array($this, 'test_discord_webhook'));
    }
    
    /**
     * 註冊設定
     */
    public function register_settings() {
        // Discord 設定
        register_setting('lmm_settings', 'lmm_discord_webhook_url', array(
            'type' => 'string',
            'sanitize_callback' => 'esc_url_raw'
        ));
        
        register_setting('lmm_settings', 'lmm_discord_notifications', array(
            'type' => 'string',
            'default' => 'yes'
        ));
        
        // 前台設定
        register_setting('lmm_settings', 'lmm_chat_title', array(
            'type' => 'string',
            'default' => '線上客服',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('lmm_settings', 'lmm_chat_placeholder', array(
            'type' => 'string',
            'default' => '請輸入您的問題...',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('lmm_settings', 'lmm_require_name', array(
            'type' => 'string',
            'default' => 'yes'
        ));
        
        register_setting('lmm_settings', 'lmm_require_email', array(
            'type' => 'string',
            'default' => 'no'
        ));
        
        register_setting('lmm_settings', 'lmm_require_phone', array(
            'type' => 'string',
            'default' => 'no'
        ));
        
        register_setting('lmm_settings', 'lmm_chat_position', array(
            'type' => 'string',
            'default' => 'bottom-right'
        ));
        
        register_setting('lmm_settings', 'lmm_primary_color', array(
            'type' => 'string',
            'default' => '#06c755',
            'sanitize_callback' => 'sanitize_hex_color'
        ));
    }
    
    /**
     * 測試Discord Webhook
     */
    public function test_discord_webhook() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '沒有權限'));
            return;
        }
        
        check_ajax_referer('lmm_admin_nonce', 'nonce');
        
        $webhook_url = sanitize_text_field($_POST['webhook_url'] ?? '');
        
        if (empty($webhook_url)) {
            wp_send_json_error(array('message' => '請輸入Webhook URL'));
            return;
        }
        
        $result = LMM_Discord_Notifier::test_connection($webhook_url);
        
        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }
}
