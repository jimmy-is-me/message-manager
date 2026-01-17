<?php
/**
 * 前台功能類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class LMM_Frontend {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        add_action('wp_footer', array($this, 'render_chat_widget'));
        add_shortcode('line_message_chat', array($this, 'render_chat_shortcode'));
    }
    
    /**
     * 載入前台資源
     */
    public function enqueue_frontend_assets() {
        // 檢查是否使用簡約版
        $use_minimal = get_option('lmm_use_minimal_style', 'yes');
        
        if ($use_minimal === 'yes') {
            wp_enqueue_style('lmm-frontend-css', LMM_PLUGIN_URL . 'public/css/frontend-minimal.css', array(), LMM_VERSION);
        } else {
            wp_enqueue_style('lmm-frontend-css', LMM_PLUGIN_URL . 'public/css/frontend.css', array(), LMM_VERSION);
        }
        
        wp_enqueue_style('lmm-emoji-css', LMM_PLUGIN_URL . 'public/css/emoji-picker.css', array(), LMM_VERSION);
        
        wp_enqueue_script('lmm-frontend-js', LMM_PLUGIN_URL . 'public/js/frontend.js', array('jquery'), LMM_VERSION, true);
        
        $primary_color = get_option('lmm_primary_color', '#06c755');
        
        wp_localize_script('lmm-frontend-js', 'lmmFrontend', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('lmm_frontend_nonce'),
            'chatTitle' => get_option('lmm_chat_title', '線上客服'),
            'chatPlaceholder' => get_option('lmm_chat_placeholder', '請輸入您的問題...'),
            'requireName' => get_option('lmm_require_name', 'yes'),
            'requireEmail' => get_option('lmm_require_email', 'no'),
            'requirePhone' => get_option('lmm_require_phone', 'no'),
            'primaryColor' => $primary_color,
            'strings' => array(
                'sending' => '發送中...',
                'sendError' => '發送失敗，請稍後再試',
                'nameRequired' => '請輸入您的姓名',
                'emailRequired' => '請輸入您的Email',
                'phoneRequired' => '請輸入您的電話',
                'messageRequired' => '請輸入訊息內容',
                'invalidEmail' => 'Email格式不正確'
            )
        ));
        
        // 添加自定義CSS變數
        $custom_css = "
            :root {
                --lmm-primary-color: {$primary_color};
            }
        ";
        wp_add_inline_style('lmm-frontend-css', $custom_css);
    }
    
    /**
     * 渲染浮動聊天小工具
     */
    public function render_chat_widget() {
        $chat_position = get_option('lmm_chat_position', 'bottom-right');
        $this->render_chat_interface('widget', $chat_position);
    }
    
    /**
     * 渲染短代碼
     */
    public function render_chat_shortcode($atts) {
        $atts = shortcode_atts(array(
            'position' => 'inline'
        ), $atts);
        
        ob_start();
        $this->render_chat_interface('inline');
        return ob_get_clean();
    }
    
    /**
     * 渲染聊天界面
     */
    private function render_chat_interface($type = 'widget', $position = 'bottom-right') {
        $chat_title = get_option('lmm_chat_title', '線上客服');
        $require_name = get_option('lmm_require_name', 'yes');
        $require_email = get_option('lmm_require_email', 'no');
        $require_phone = get_option('lmm_require_phone', 'no');
        
        $container_class = $type === 'widget' ? 'lmm-chat-widget lmm-position-' . $position : 'lmm-chat-inline';
        ?>
        <div class="lmm-chat-container <?php echo esc_attr($container_class); ?>" id="lmm-chat-container">
            <?php if ($type === 'widget') : ?>
                <button class="lmm-chat-toggle" id="lmm-chat-toggle" aria-label="開啟客服對話">
                    <svg class="lmm-icon-chat" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                    <svg class="lmm-icon-close" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                    <span class="lmm-unread-badge" id="lmm-unread-badge" style="display: none;">0</span>
                </button>
            <?php endif; ?>
            
            <div class="lmm-chat-window" id="lmm-chat-window">
                <div class="lmm-chat-header">
                    <div class="lmm-header-content">
                        <div class="lmm-header-icon">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2C6.48 2 2 6.48 2 12c0 1.54.36 3 .97 4.29L2 22l5.71-.97C9 21.64 10.46 22 12 22c5.52 0 10-4.48 10-10S17.52 2 12 2zm0 18c-1.38 0-2.67-.33-3.82-.91l-.27-.15-2.91.49.49-2.91-.15-.27C4.33 14.67 4 13.38 4 12c0-4.41 3.59-8 8-8s8 3.59 8 8-3.59 8-8 8z"/>
                            </svg>
                        </div>
                        <div class="lmm-header-text">
                            <h3><?php echo esc_html($chat_title); ?></h3>
                            <p class="lmm-status-text">線上</p>
                        </div>
                    </div>
                    <?php if ($type === 'widget') : ?>
                        <button class="lmm-close-btn" id="lmm-close-chat" aria-label="關閉對話">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="18" y1="6" x2="6" y2="18"></line>
                                <line x1="6" y1="6" x2="18" y2="18"></line>
                            </svg>
                        </button>
                    <?php endif; ?>
                </div>
                
                <div class="lmm-messages-area" id="lmm-messages-area">
                    <div class="lmm-welcome-message">
                        <div class="lmm-message lmm-message-admin">
                            <div class="lmm-message-bubble">
                                您好！歡迎使用線上客服 👋<br>
                                請先留下您的聯絡資訊，我們會盡快回覆您！
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="lmm-input-area">
                    <form id="lmm-chat-form">
                        <div class="lmm-customer-info-form" id="lmm-customer-info-form">
                            <!-- Email 必填 -->
                            <div class="lmm-form-group">
                                <label class="lmm-form-label">📧 電子信箱 *</label>
                                <input 
                                    type="email" 
                                    id="lmm-customer-email" 
                                    name="customer_email" 
                                    placeholder="請輸入您的Email" 
                                    required
                                    autocomplete="email"
                                >
                            </div>
                            
                            <?php if ($require_name === 'yes') : ?>
                                <div class="lmm-form-group">
                                    <label class="lmm-form-label">👤 您的姓名 *</label>
                                    <input 
                                        type="text" 
                                        id="lmm-customer-name" 
                                        name="customer_name" 
                                        placeholder="請輸入您的姓名" 
                                        required
                                        autocomplete="name"
                                    >
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($require_phone === 'yes') : ?>
                                <div class="lmm-form-group">
                                    <label class="lmm-form-label">📱 聯絡電話 *</label>
                                    <input 
                                        type="tel" 
                                        id="lmm-customer-phone" 
                                        name="customer_phone" 
                                        placeholder="請輸入您的電話" 
                                        required
                                        autocomplete="tel"
                                    >
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="lmm-message-input-wrapper">
                            <button type="button" class="lmm-emoji-btn" id="lmm-emoji-btn" aria-label="選擇表情符號">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-5-6c.78 2.34 2.72 4 5 4s4.22-1.66 5-4H7zm8-2c0-.55-.45-1-1-1s-1 .45-1 1 .45 1 1 1 1-.45 1-1zM9 12c0-.55-.45-1-1-1s-1 .45-1 1 .45 1 1 1 1-.45 1-1z"/>
                                </svg>
                            </button>
                            <textarea 
                                id="lmm-message-input" 
                                name="message" 
                                placeholder="<?php echo esc_attr(get_option('lmm_chat_placeholder', '請輸入您的問題...')); ?>" 
                                rows="1"
                                required
                            ></textarea>
                            <button type="submit" class="lmm-send-btn" aria-label="發送訊息">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                        
                        <?php echo LMM_Emoji_Picker::render_picker(); ?>
                    </form>
                </div>
            </div>
        </div>
        <?php
    }
}
