<?php
/**
 * Discord 通知類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class LMM_Discord_Notifier {
    
    /**
     * 發送Discord通知
     */
    public static function send_notification($message_data) {
        $webhook_url = get_option('lmm_discord_webhook_url', '');
        
        if (empty($webhook_url)) {
            return false;
        }
        
        $enable_notifications = get_option('lmm_discord_notifications', 'yes');
        
        if ($enable_notifications !== 'yes') {
            return false;
        }
        
        // 準備Discord訊息內容
        $embed = array(
            'title' => '📨 新的客戶訊息',
            'description' => self::truncate_message($message_data['message'], 500),
            'color' => 5814783, // 藍色
            'fields' => array(
                array(
                    'name' => '客戶姓名',
                    'value' => !empty($message_data['customer_name']) ? $message_data['customer_name'] : '未提供',
                    'inline' => true
                ),
                array(
                    'name' => '聯絡方式',
                    'value' => self::get_contact_info($message_data),
                    'inline' => true
                ),
                array(
                    'name' => '對話ID',
                    'value' => $message_data['conversation_id'],
                    'inline' => false
                )
            ),
            'timestamp' => current_time('c'),
            'footer' => array(
                'text' => 'LINE訊息管理系統'
            )
        );
        
        // 添加管理後台連結
        $admin_url = admin_url('admin.php?page=line-message-manager&conversation_id=' . $message_data['conversation_id']);
        
        $payload = array(
            'content' => '**有新訊息需要回覆！**',
            'embeds' => array($embed),
            'components' => array(
                array(
                    'type' => 1,
                    'components' => array(
                        array(
                            'type' => 2,
                            'style' => 5,
                            'label' => '前往後台回覆',
                            'url' => $admin_url
                        )
                    )
                )
            )
        );
        
        // 發送到Discord
        $response = wp_remote_post($webhook_url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($payload),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            error_log('LMM Discord Notification Error: ' . $response->get_error_message());
            return false;
        }
        
        return true;
    }
    
    /**
     * 截斷訊息內容
     */
    private static function truncate_message($message, $length = 500) {
        if (mb_strlen($message) > $length) {
            return mb_substr($message, 0, $length) . '...';
        }
        return $message;
    }
    
    /**
     * 獲取聯絡資訊
     */
    private static function get_contact_info($message_data) {
        $contact_parts = array();
        
        if (!empty($message_data['customer_email'])) {
            $contact_parts[] = '📧 ' . $message_data['customer_email'];
        }
        
        if (!empty($message_data['customer_phone'])) {
            $contact_parts[] = '📱 ' . $message_data['customer_phone'];
        }
        
        if (empty($contact_parts)) {
            return '未提供';
        }
        
        return implode("\n", $contact_parts);
    }
    
    /**
     * 測試Discord連接
     */
    public static function test_connection($webhook_url) {
        $payload = array(
            'content' => '✅ LINE訊息管理系統測試訊息',
            'embeds' => array(
                array(
                    'title' => 'Discord Webhook 測試成功',
                    'description' => 'Discord通知已成功設置，您將在此頻道收到新訊息通知。',
                    'color' => 5763719, // 綠色
                    'timestamp' => current_time('c')
                )
            )
        );
        
        $response = wp_remote_post($webhook_url, array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => json_encode($payload),
            'timeout' => 15
        ));
        
        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'message' => $response->get_error_message()
            );
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        
        if ($response_code >= 200 && $response_code < 300) {
            return array(
                'success' => true,
                'message' => 'Discord連接測試成功！'
            );
        } else {
            return array(
                'success' => false,
                'message' => 'Discord連接失敗，HTTP狀態碼：' . $response_code
            );
        }
    }
}
