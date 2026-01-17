<?php
/**
 * Plugin Name: LINE官方帳號訊息管理系統
 * Plugin URI: https://github.com/jimmy-is-me/line-message-manager
 * Description: 管理LINE官方帳號的訊息，提供前台對話框讓客戶輸入問題，後台管理員可以回覆，並透過Discord通知新訊息。支援表情符號、快速回覆範本、訊息搜尋等功能。
 * Version: 1.2.1
 * Author: jimmy-is-me
 * Author URI: https://github.com/jimmy-is-me
 * Text Domain: line-message-manager
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

if (!defined('ABSPATH')) {
    exit; // 防止直接訪問
}

// 定義插件常數
define('LMM_VERSION', '1.2.1');
define('LMM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LMM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LMM_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * 主要插件類別
 */
class Line_Message_Manager {
    
    private static $instance = null;
    
    /**
     * 獲取單例實例
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * 構造函數
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * 載入相關文件
     */
    private function load_dependencies() {
        require_once LMM_PLUGIN_DIR . 'includes/class-database.php';
        require_once LMM_PLUGIN_DIR . 'includes/class-discord-notifier.php';
        require_once LMM_PLUGIN_DIR . 'includes/class-message-handler.php';
        require_once LMM_PLUGIN_DIR . 'includes/class-emoji-picker.php';
        require_once LMM_PLUGIN_DIR . 'includes/class-activator.php';
        require_once LMM_PLUGIN_DIR . 'admin/class-admin.php';
        require_once LMM_PLUGIN_DIR . 'admin/class-settings.php';
        require_once LMM_PLUGIN_DIR . 'public/class-frontend.php';
    }
    
    /**
     * 初始化掛鉤
     */
    private function init_hooks() {
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * 插件啟用
     */
    public function activate() {
        LMM_Activator::activate();
    }
    
    /**
     * 插件停用
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * 初始化插件
     */
    public function init() {
        // 載入文本域
        load_plugin_textdomain('line-message-manager', false, dirname(LMM_PLUGIN_BASENAME) . '/languages');
        
        // 初始化各個組件
        LMM_Admin::get_instance();
        LMM_Settings::get_instance();
        LMM_Frontend::get_instance();
        LMM_Message_Handler::get_instance();
    }
}

/**
 * 啟動插件
 */
function lmm_init() {
    return Line_Message_Manager::get_instance();
}

// 初始化插件
lmm_init();
