<?php
/**
 * 訊息處理類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class LMM_Message_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_ajax_lmm_send_customer_message', array($this, 'handle_customer_message'));
        add_action('wp_ajax_nopriv_lmm_send_customer_message', array($this, 'handle_customer_message'));
        
        add_action('wp_ajax_lmm_send_admin_reply', array($this, 'handle_admin_reply'));
        add_action('wp_ajax_lmm_get_messages', array($this, 'get_conversation_messages'));
        add_action('wp_ajax_lmm_get_new_messages', array($this, 'get_new_messages'));
        add_action('wp_ajax_lmm_mark_as_read', array($this, 'mark_as_read'));
    }
    
    /**
     * 處理客戶訊息
     */
    public function handle_customer_message() {
        // 驗證nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lmm_frontend_nonce')) {
            wp_send_json_error(array('message' => '安全驗證失敗'));
            return;
        }
        
        // 獲取並驗證數據
        $customer_name = sanitize_text_field($_POST['customer_name'] ?? '');
        $customer_email = sanitize_email($_POST['customer_email'] ?? '');
        $customer_phone = sanitize_text_field($_POST['customer_phone'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        
        // 驗證必填字段
        if (empty($message)) {
            wp_send_json_error(array('message' => '請輸入訊息內容'));
            return;
        }
        
        // 如果沒有conversation_id，生成新的
        if (empty($conversation_id)) {
            $conversation_id = 'conv_' . time() . '_' . wp_generate_password(8, false);
        }
        
        // 獲取IP地址和User Agent
        $ip_address = $this->get_client_ip();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // 插入訊息到數據庫
        $message_id = LMM_Database::insert_message(array(
            'conversation_id' => $conversation_id,
            'customer_name' => $customer_name,
            'customer_email' => $customer_email,
            'customer_phone' => $customer_phone,
            'message_type' => 'customer',
            'message' => $message,
            'status' => 'unread',
            'ip_address' => $ip_address,
            'user_agent' => $user_agent
        ));
        
        if ($message_id) {
            // 發送Discord通知
            LMM_Discord_Notifier::send_notification(array(
                'conversation_id' => $conversation_id,
                'customer_name' => $customer_name,
                'customer_email' => $customer_email,
                'customer_phone' => $customer_phone,
                'message' => $message
            ));
            
            wp_send_json_success(array(
                'message' => '訊息已送出，我們會盡快回覆您！',
                'conversation_id' => $conversation_id,
                'message_id' => $message_id
            ));
        } else {
            wp_send_json_error(array('message' => '訊息發送失敗，請稍後再試'));
        }
    }
    
    /**
     * 處理管理員回覆
     */
    public function handle_admin_reply() {
        // 驗證權限
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '沒有權限'));
            return;
        }
        
        // 驗證nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'lmm_admin_nonce')) {
            wp_send_json_error(array('message' => '安全驗證失敗'));
            return;
        }
        
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        $message = sanitize_textarea_field($_POST['message'] ?? '');
        
        if (empty($conversation_id) || empty($message)) {
            wp_send_json_error(array('message' => '缺少必要參數'));
            return;
        }
        
        // 獲取對話的客戶資訊
        $customer_info = $this->get_customer_info($conversation_id);
        
        // 插入管理員回覆
        $message_id = LMM_Database::insert_message(array(
            'conversation_id' => $conversation_id,
            'customer_name' => $customer_info['customer_name'] ?? '',
            'customer_email' => $customer_info['customer_email'] ?? '',
            'customer_phone' => $customer_info['customer_phone'] ?? '',
            'message_type' => 'admin',
            'message' => $message,
            'admin_user_id' => get_current_user_id(),
            'status' => 'read'
        ));
        
        if ($message_id) {
            // 更新該對話中所有客戶訊息的狀態為已回覆
            $this->update_conversation_status($conversation_id, 'replied');
            
            wp_send_json_success(array(
                'message' => '回覆已發送',
                'message_id' => $message_id
            ));
        } else {
            wp_send_json_error(array('message' => '回覆發送失敗'));
        }
    }
    
    /**
     * 獲取對話訊息
     */
    public function get_conversation_messages() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '沒有權限'));
            return;
        }
        
        $conversation_id = sanitize_text_field($_GET['conversation_id'] ?? '');
        
        if (empty($conversation_id)) {
            wp_send_json_error(array('message' => '缺少對話ID'));
            return;
        }
        
        $messages = LMM_Database::get_messages(array(
            'conversation_id' => $conversation_id,
            'orderby' => 'created_at',
            'order' => 'ASC',
            'limit' => 500
        ));
        
        wp_send_json_success(array('messages' => $messages));
    }
    
    /**
     * 獲取新訊息（用於前台輪詢）
     */
    public function get_new_messages() {
        $conversation_id = sanitize_text_field($_GET['conversation_id'] ?? '');
        $last_message_id = intval($_GET['last_message_id'] ?? 0);
        
        if (empty($conversation_id)) {
            wp_send_json_error(array('message' => '缺少對話ID'));
            return;
        }
        
        global $wpdb;
        $table_messages = $wpdb->prefix . 'lmm_messages';
        
        $messages = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_messages 
            WHERE conversation_id = %s AND id > %d AND message_type = 'admin'
            ORDER BY created_at ASC",
            $conversation_id,
            $last_message_id
        ));
        
        wp_send_json_success(array('messages' => $messages));
    }
    
    /**
     * 標記為已讀
     */
    public function mark_as_read() {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => '沒有權限'));
            return;
        }
        
        $conversation_id = sanitize_text_field($_POST['conversation_id'] ?? '');
        
        if (empty($conversation_id)) {
            wp_send_json_error(array('message' => '缺少對話ID'));
            return;
        }
        
        $this->update_conversation_status($conversation_id, 'read');
        
        wp_send_json_success(array('message' => '已標記為已讀'));
    }
    
    /**
     * 獲取客戶資訊
     */
    private function get_customer_info($conversation_id) {
        global $wpdb;
        $table_messages = $wpdb->prefix . 'lmm_messages';
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT customer_name, customer_email, customer_phone 
            FROM $table_messages 
            WHERE conversation_id = %s 
            LIMIT 1",
            $conversation_id
        ), ARRAY_A);
    }
    
    /**
     * 更新對話狀態
     */
    private function update_conversation_status($conversation_id, $status) {
        global $wpdb;
        $table_messages = $wpdb->prefix . 'lmm_messages';
        
        return $wpdb->update(
            $table_messages,
            array('status' => $status),
            array(
                'conversation_id' => $conversation_id,
                'message_type' => 'customer'
            ),
            array('%s'),
            array('%s', '%s')
        );
    }
    
    /**
     * 獲取客戶端IP地址
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR');
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return 'UNKNOWN';
    }
}
