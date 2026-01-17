<?php
/**
 * 後台管理類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class LMM_Admin {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_filter('admin_footer_text', array($this, 'admin_footer_text'));
        add_action('admin_init', array($this, 'handle_admin_actions'));
    }
    
    /**
     * 處理管理員操作
     */
    public function handle_admin_actions() {
        if (isset($_GET['action']) && $_GET['action'] === 'create_tables' && current_user_can('manage_options')) {
            LMM_Database::create_tables();
            wp_redirect(admin_url('admin.php?page=line-message-manager&tables_created=1'));
            exit;
        }
    }
    
    /**
     * 添加後台選單
     */
    public function add_admin_menu() {
        $unread_count = LMM_Database::get_unread_count();
        $menu_title = $unread_count > 0 ? sprintf('LINE訊息 <span class="awaiting-mod">%d</span>', $unread_count) : 'LINE訊息';
        
        add_menu_page(
            'LINE訊息管理',
            $menu_title,
            'manage_options',
            'line-message-manager',
            array($this, 'render_messages_page'),
            'dashicons-email-alt',
            25
        );
        
        add_submenu_page(
            'line-message-manager',
            '所有對話',
            '所有對話',
            'manage_options',
            'line-message-manager',
            array($this, 'render_messages_page')
        );
        
        add_submenu_page(
            'line-message-manager',
            '快速回覆',
            '快速回覆',
            'manage_options',
            'line-message-quick-replies',
            array($this, 'render_quick_replies_page')
        );
        
        add_submenu_page(
            'line-message-manager',
            '設定',
            '設定',
            'manage_options',
            'line-message-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * 載入後台資源
     */
    public function enqueue_admin_assets($hook) {
        if (strpos($hook, 'line-message') === false) {
            return;
        }
        
        wp_enqueue_style('lmm-admin-css', LMM_PLUGIN_URL . 'admin/css/admin.css', array(), LMM_VERSION);
        
        wp_enqueue_script('lmm-admin-js', LMM_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), LMM_VERSION, true);
        
        wp_localize_script('lmm-admin-js', 'lmmAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lmm_admin_nonce'),
            'strings' => array(
                'confirmDelete' => '確定要刪除此訊息嗎？',
                'sendingReply' => '正在發送回覆...',
                'replySuccess' => '回覆已發送',
                'replyError' => '回覆發送失敗'
            )
        ));
    }
    
    /**
     * 渲染訊息管理頁面
     */
    public function render_messages_page() {
        $conversation_id = isset($_GET['conversation_id']) ? sanitize_text_field($_GET['conversation_id']) : '';
        
        if (!empty($conversation_id)) {
            $this->render_conversation_view($conversation_id);
        } else {
            $this->render_conversations_list();
        }
    }
    
    /**
     * 渲染對話列表
     */
    private function render_conversations_list() {
        // 處理搜尋
        $search_query = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        
        if (!empty($search_query)) {
            $conversations = $this->search_conversations($search_query);
        } else {
            $conversations = LMM_Database::get_conversations(50, 0);
        }
        
        ?>
        <div class="wrap lmm-admin-wrap">
            <h1 class="wp-heading-inline">LINE訊息管理</h1>
            
            <form method="get" action="" class="lmm-search-form">
                <input type="hidden" name="page" value="line-message-manager">
                <input type="search" 
                       name="s" 
                       value="<?php echo esc_attr($search_query); ?>" 
                       placeholder="搜尋客戶姓名、Email、電話或訊息內容..." 
                       class="lmm-search-input">
                <button type="submit" class="button">搜尋</button>
                <?php if (!empty($search_query)) : ?>
                    <a href="<?php echo admin_url('admin.php?page=line-message-manager'); ?>" class="button">清除</a>
                <?php endif; ?>
            </form>
            
            <hr class="wp-header-end">
            
            <?php if (!empty($search_query)) : ?>
                <div class="lmm-search-info">
                    搜尋「<strong><?php echo esc_html($search_query); ?></strong>」找到 <?php echo count($conversations); ?> 個結果
                </div>
            <?php endif; ?>
            
            <?php if (empty($conversations)) : ?>
                <div class="lmm-empty-state">
                    <div class="lmm-empty-icon">📭</div>
                    <h2><?php echo !empty($search_query) ? '沒有找到符合的結果' : '目前沒有訊息'; ?></h2>
                    <p><?php echo !empty($search_query) ? '請嘗試其他關鍵字' : '當客戶透過前台對話框發送訊息時，您將在這裡看到它們。'; ?></p>
                </div>
            <?php else : ?>
                <div class="lmm-conversations-list">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 20%;">客戶資訊</th>
                                <th style="width: 15%;">聯絡方式</th>
                                <th style="width: 30%;">對話ID</th>
                                <th style="width: 10%;" class="lmm-text-center">訊息數</th>
                                <th style="width: 10%;" class="lmm-text-center">未讀</th>
                                <th style="width: 15%;">最後訊息時間</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($conversations as $conv) : ?>
                                <tr class="<?php echo $conv->unread_count > 0 ? 'lmm-unread' : ''; ?>">
                                    <td>
                                        <strong>
                                            <a href="<?php echo admin_url('admin.php?page=line-message-manager&conversation_id=' . urlencode($conv->conversation_id)); ?>">
                                                <?php echo esc_html($conv->customer_name ?: '匿名客戶'); ?>
                                            </a>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if (!empty($conv->customer_email)) : ?>
                                            <div>📧 <?php echo esc_html($conv->customer_email); ?></div>
                                        <?php endif; ?>
                                        <?php if (!empty($conv->customer_phone)) : ?>
                                            <div>📱 <?php echo esc_html($conv->customer_phone); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <code class="lmm-conversation-id"><?php echo esc_html($conv->conversation_id); ?></code>
                                    </td>
                                    <td class="lmm-text-center">
                                        <span class="lmm-badge"><?php echo intval($conv->message_count); ?></span>
                                    </td>
                                    <td class="lmm-text-center">
                                        <?php if ($conv->unread_count > 0) : ?>
                                            <span class="lmm-badge lmm-badge-unread"><?php echo intval($conv->unread_count); ?></span>
                                        <?php else : ?>
                                            <span class="lmm-badge lmm-badge-read">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php echo esc_html(get_date_from_gmt($conv->last_message_time, 'Y-m-d H:i:s')); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * 渲染對話詳情
     */
    private function render_conversation_view($conversation_id) {
        $messages = LMM_Database::get_messages(array(
            'conversation_id' => $conversation_id,
            'orderby' => 'created_at',
            'order' => 'ASC',
            'limit' => 500
        ));
        
        if (empty($messages)) {
            echo '<div class="wrap"><p>找不到對話記錄</p></div>';
            return;
        }
        
        $customer_info = $messages[0];
        
        // 標記為已讀
        LMM_Database::update_message_status($customer_info->id, 'read');
        
        ?>
        <div class="wrap lmm-admin-wrap">
            <h1 class="wp-heading-inline">
                <a href="<?php echo admin_url('admin.php?page=line-message-manager'); ?>" class="lmm-back-link">← 返回列表</a>
                對話詳情
            </h1>
            <hr class="wp-header-end">
            
            <div class="lmm-conversation-view">
                <div class="lmm-customer-info-card">
                    <h2>客戶資訊</h2>
                    <div class="lmm-info-row">
                        <span class="lmm-info-label">姓名：</span>
                        <span class="lmm-info-value"><?php echo esc_html($customer_info->customer_name ?: '未提供'); ?></span>
                    </div>
                    <?php if (!empty($customer_info->customer_email)) : ?>
                        <div class="lmm-info-row">
                            <span class="lmm-info-label">Email：</span>
                            <span class="lmm-info-value">
                                <a href="mailto:<?php echo esc_attr($customer_info->customer_email); ?>">
                                    <?php echo esc_html($customer_info->customer_email); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($customer_info->customer_phone)) : ?>
                        <div class="lmm-info-row">
                            <span class="lmm-info-label">電話：</span>
                            <span class="lmm-info-value">
                                <a href="tel:<?php echo esc_attr($customer_info->customer_phone); ?>">
                                    <?php echo esc_html($customer_info->customer_phone); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="lmm-info-row">
                        <span class="lmm-info-label">對話ID：</span>
                        <span class="lmm-info-value">
                            <code><?php echo esc_html($conversation_id); ?></code>
                        </span>
                    </div>
                </div>
                
                <div class="lmm-messages-container" id="lmm-messages-container">
                    <?php foreach ($messages as $message) : ?>
                        <div class="lmm-message lmm-message-<?php echo esc_attr($message->message_type); ?>">
                            <div class="lmm-message-header">
                                <span class="lmm-message-sender">
                                    <?php if ($message->message_type === 'customer') : ?>
                                        <?php echo esc_html($message->customer_name ?: '客戶'); ?>
                                    <?php else : ?>
                                        <?php 
                                        $admin_user = get_userdata($message->admin_user_id);
                                        echo esc_html($admin_user ? $admin_user->display_name : '管理員');
                                        ?>
                                    <?php endif; ?>
                                </span>
                                <span class="lmm-message-time">
                                    <?php echo esc_html(get_date_from_gmt($message->created_at, 'Y-m-d H:i:s')); ?>
                                </span>
                            </div>
                            <div class="lmm-message-content">
                                <?php echo nl2br(esc_html($message->message)); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="lmm-reply-form">
                    <h3>回覆客戶</h3>
                    
                    <?php
                    // 確保資料表存在
                    global $wpdb;
                    $table_name = $wpdb->prefix . 'lmm_quick_replies';
                    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
                    
                    if ($table_exists) {
                        $quick_replies = LMM_Database::get_quick_replies();
                        if (!empty($quick_replies)) :
                        ?>
                        <div class="lmm-quick-replies-buttons">
                            <label>快速回覆：</label>
                            <div class="lmm-quick-reply-list">
                                <?php foreach ($quick_replies as $template) : ?>
                                    <button type="button" 
                                            class="button lmm-quick-reply-btn" 
                                            data-content="<?php echo esc_attr($template->content); ?>"
                                            title="<?php echo esc_attr($template->content); ?>">
                                        <?php echo esc_html($template->title); ?>
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php 
                        endif;
                    } else {
                        // 顯示提示並提供修復按鈕
                        ?>
                        <div class="notice notice-warning inline">
                            <p>快速回覆功能需要建立資料表。<a href="<?php echo admin_url('admin.php?page=line-message-manager&action=create_tables'); ?>" class="button button-small">立即建立</a></p>
                        </div>
                        <?php
                    }
                    ?>
                    
                    <form id="lmm-admin-reply-form" data-conversation-id="<?php echo esc_attr($conversation_id); ?>">
                        <textarea 
                            id="lmm-reply-message" 
                            name="message" 
                            rows="5" 
                            placeholder="輸入您的回覆..."
                            required
                        ></textarea>
                        <div class="lmm-form-actions">
                            <button type="submit" class="button button-primary button-large">
                                發送回覆
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * 渲染快速回覆頁面
     */
    public function render_quick_replies_page() {
        require_once LMM_PLUGIN_DIR . 'admin/quick-replies-page.php';
    }
    
    /**
     * 渲染設定頁面
     */
    public function render_settings_page() {
        require_once LMM_PLUGIN_DIR . 'admin/settings-page.php';
    }
    
    /**
     * 搜尋對話
     */
    private function search_conversations($search_query) {
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
                WHERE customer_name LIKE %s
                   OR customer_email LIKE %s
                   OR customer_phone LIKE %s
                   OR message LIKE %s
                GROUP BY conversation_id
                ORDER BY last_message_time DESC
                LIMIT 50";
        
        $like_query = '%' . $wpdb->esc_like($search_query) . '%';
        
        return $wpdb->get_results($wpdb->prepare($sql, $like_query, $like_query, $like_query, $like_query));
    }
    
    /**
     * 修改管理員頁腳文字
     */
    public function admin_footer_text($text) {
        $screen = get_current_screen();
        if (strpos($screen->id, 'line-message') !== false) {
            $text = 'LINE訊息管理系統 v' . LMM_VERSION;
        }
        return $text;
    }
}
