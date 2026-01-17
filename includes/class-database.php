<?php
/**
 * 數據庫管理類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class LMM_Database {
    
    /**
     * 創建數據庫表
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // 訊息表
        $table_messages = $wpdb->prefix . 'lmm_messages';
        // 快速回覆範本表
        $table_templates = $wpdb->prefix . 'lmm_quick_replies';
        
        $sql = "CREATE TABLE IF NOT EXISTS $table_messages (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            conversation_id varchar(255) NOT NULL,
            customer_name varchar(255) DEFAULT '',
            customer_email varchar(255) DEFAULT '',
            customer_phone varchar(100) DEFAULT '',
            message_type enum('customer','admin') NOT NULL DEFAULT 'customer',
            message text NOT NULL,
            admin_user_id bigint(20) UNSIGNED DEFAULT NULL,
            status enum('unread','read','replied') NOT NULL DEFAULT 'unread',
            ip_address varchar(100) DEFAULT '',
            user_agent text DEFAULT '',
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY conversation_id (conversation_id),
            KEY status (status),
            KEY created_at (created_at),
            KEY message_type (message_type)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // 快速回覆範本表
        $sql_templates = "CREATE TABLE IF NOT EXISTS $table_templates (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content text NOT NULL,
            shortcut varchar(50) DEFAULT '',
            category varchar(100) DEFAULT 'general',
            sort_order int(11) DEFAULT 0,
            created_by bigint(20) UNSIGNED NOT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY category (category),
            KEY sort_order (sort_order)
        ) $charset_collate;";
        
        dbDelta($sql_templates);
        
        // 新增預設快速回覆範本
        self::insert_default_templates();
        
        // 儲存插件版本號
        update_option('lmm_db_version', LMM_VERSION);
    }
    
    /**
     * 插入預設快速回覆範本
     */
    private static function insert_default_templates() {
        global $wpdb;
        $table_templates = $wpdb->prefix . 'lmm_quick_replies';
        
        // 檢查是否已有範本
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_templates");
        if ($count > 0) {
            return;
        }
        
        $current_user_id = get_current_user_id() ?: 1;
        
        $default_templates = array(
            array(
                'title' => '歡迎訊息',
                'content' => '您好！感謝您的聯繫，我們已收到您的訊息，會盡快為您處理。',
                'shortcut' => '/hello',
                'category' => 'greeting',
                'sort_order' => 1
            ),
            array(
                'title' => '營業時間',
                'content' => '我們的營業時間是週一至週五 09:00-18:00，週末及國定假日休息。',
                'shortcut' => '/hours',
                'category' => 'info',
                'sort_order' => 2
            ),
            array(
                'title' => '感謝訊息',
                'content' => '非常感謝您的詢問！如果還有其他問題，歡迎隨時聯繫我們。',
                'shortcut' => '/thanks',
                'category' => 'greeting',
                'sort_order' => 3
            ),
            array(
                'title' => '稍後回覆',
                'content' => '您好，我們已收到您的訊息。由於目前詢問較多，我們會在24小時內回覆您，謝謝耐心等待。',
                'shortcut' => '/later',
                'category' => 'general',
                'sort_order' => 4
            ),
            array(
                'title' => '聯絡資訊',
                'content' => '您可以透過以下方式聯繫我們：\nEmail: info@example.com\n電話: 0912-345-678\n地址: 台灣台北市',
                'shortcut' => '/contact',
                'category' => 'info',
                'sort_order' => 5
            )
        );
        
        foreach ($default_templates as $template) {
            $wpdb->insert(
                $table_templates,
                array(
                    'title' => $template['title'],
                    'content' => $template['content'],
                    'shortcut' => $template['shortcut'],
                    'category' => $template['category'],
                    'sort_order' => $template['sort_order'],
                    'created_by' => $current_user_id
                ),
                array('%s', '%s', '%s', '%s', '%d', '%d')
            );
        }
    }
    
    /**
     * 刪除數據庫表（用於完全卸載）
     */
    public static function drop_tables() {
        global $wpdb;
        
        $table_messages = $wpdb->prefix . 'lmm_messages';
        $table_templates = $wpdb->prefix . 'lmm_quick_replies';
        
        $wpdb->query("DROP TABLE IF EXISTS $table_messages");
        $wpdb->query("DROP TABLE IF EXISTS $table_templates");
        
        delete_option('lmm_db_version');
    }
    
    /**
     * 獲取快速回覆範本
     */
    public static function get_quick_replies($category = '') {
        global $wpdb;
        
        $table_templates = $wpdb->prefix . 'lmm_quick_replies';
        
        $where = '1=1';
        if (!empty($category)) {
            $where .= $wpdb->prepare(' AND category = %s', $category);
        }
        
        $sql = "SELECT * FROM $table_templates WHERE $where ORDER BY sort_order ASC, id ASC";
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * 插入快速回覆範本
     */
    public static function insert_quick_reply($data) {
        global $wpdb;
        
        $table_templates = $wpdb->prefix . 'lmm_quick_replies';
        
        $result = $wpdb->insert(
            $table_templates,
            array(
                'title' => $data['title'],
                'content' => $data['content'],
                'shortcut' => $data['shortcut'] ?? '',
                'category' => $data['category'] ?? 'general',
                'sort_order' => $data['sort_order'] ?? 0,
                'created_by' => get_current_user_id()
            ),
            array('%s', '%s', '%s', '%s', '%d', '%d')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * 更新快速回覆範本
     */
    public static function update_quick_reply($id, $data) {
        global $wpdb;
        
        $table_templates = $wpdb->prefix . 'lmm_quick_replies';
        
        return $wpdb->update(
            $table_templates,
            $data,
            array('id' => $id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * 刪除快速回覆範本
     */
    public static function delete_quick_reply($id) {
        global $wpdb;
        
        $table_templates = $wpdb->prefix . 'lmm_quick_replies';
        
        return $wpdb->delete(
            $table_templates,
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * 獲取訊息
     */
    public static function get_messages($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'conversation_id' => '',
            'status' => '',
            'limit' => 50,
            'offset' => 0,
            'orderby' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_messages = $wpdb->prefix . 'lmm_messages';
        
        $where = array('1=1');
        
        if (!empty($args['conversation_id'])) {
            $where[] = $wpdb->prepare('conversation_id = %s', $args['conversation_id']);
        }
        
        if (!empty($args['status'])) {
            $where[] = $wpdb->prepare('status = %s', $args['status']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM $table_messages WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $args['limit'], $args['offset']));
    }
    
    /**
     * 插入新訊息
     */
    public static function insert_message($data) {
        global $wpdb;
        
        $table_messages = $wpdb->prefix . 'lmm_messages';
        
        $defaults = array(
            'conversation_id' => '',
            'customer_name' => '',
            'customer_email' => '',
            'customer_phone' => '',
            'message_type' => 'customer',
            'message' => '',
            'admin_user_id' => null,
            'status' => 'unread',
            'ip_address' => '',
            'user_agent' => ''
        );
        
        $data = wp_parse_args($data, $defaults);
        
        $result = $wpdb->insert(
            $table_messages,
            array(
                'conversation_id' => $data['conversation_id'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'message_type' => $data['message_type'],
                'message' => $data['message'],
                'admin_user_id' => $data['admin_user_id'],
                'status' => $data['status'],
                'ip_address' => $data['ip_address'],
                'user_agent' => $data['user_agent']
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s')
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * 更新訊息狀態
     */
    public static function update_message_status($message_id, $status) {
        global $wpdb;
        
        $table_messages = $wpdb->prefix . 'lmm_messages';
        
        return $wpdb->update(
            $table_messages,
            array('status' => $status),
            array('id' => $message_id),
            array('%s'),
            array('%d')
        );
    }
    
    /**
     * 獲取對話列表
     */
    public static function get_conversations($limit = 50, $offset = 0) {
        global $wpdb;
        
        $table_messages = $wpdb->prefix . 'lmm_messages';
        
        $sql = "SELECT 
                    conversation_id,
                    MAX(customer_name) as customer_name,
                    MAX(customer_email) as customer_email,
                    MAX(customer_phone) as customer_phone,
                    MAX(created_at) as last_message_time,
                    COUNT(*) as message_count,
                    SUM(CASE WHEN status = 'unread' AND message_type = 'customer' THEN 1 ELSE 0 END) as unread_count
                FROM $table_messages
                GROUP BY conversation_id
                ORDER BY last_message_time DESC
                LIMIT %d OFFSET %d";
        
        return $wpdb->get_results($wpdb->prepare($sql, $limit, $offset));
    }
    
    /**
     * 獲取未讀訊息數量
     */
    public static function get_unread_count() {
        global $wpdb;
        
        $table_messages = $wpdb->prefix . 'lmm_messages';
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM $table_messages WHERE status = 'unread' AND message_type = 'customer'"
        );
    }
}
