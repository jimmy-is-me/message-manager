<?php
/**
 * 設定頁面模板
 */

if (!defined('ABSPATH')) {
    exit;
}

// 處理表單提交
if (isset($_POST['lmm_save_settings']) && check_admin_referer('lmm_settings_nonce')) {
    update_option('lmm_discord_webhook_url', esc_url_raw($_POST['lmm_discord_webhook_url'] ?? ''));
    update_option('lmm_discord_notifications', sanitize_text_field($_POST['lmm_discord_notifications'] ?? 'no'));
    update_option('lmm_chat_title', sanitize_text_field($_POST['lmm_chat_title'] ?? ''));
    update_option('lmm_chat_placeholder', sanitize_text_field($_POST['lmm_chat_placeholder'] ?? ''));
    update_option('lmm_require_name', sanitize_text_field($_POST['lmm_require_name'] ?? 'no'));
    update_option('lmm_require_email', sanitize_text_field($_POST['lmm_require_email'] ?? 'no'));
    update_option('lmm_require_phone', sanitize_text_field($_POST['lmm_require_phone'] ?? 'no'));
    update_option('lmm_chat_position', sanitize_text_field($_POST['lmm_chat_position'] ?? 'bottom-right'));
        update_option('lmm_primary_color', sanitize_hex_color($_POST['lmm_primary_color'] ?? '#06c755'));
    update_option('lmm_use_minimal_style', sanitize_text_field($_POST['lmm_use_minimal_style'] ?? 'no'));
    
    echo '<div class="notice notice-success is-dismissible"><p>設定已儲存！</p></div>';
}

// 獲取當前設定
$discord_webhook_url = get_option('lmm_discord_webhook_url', '');
$discord_notifications = get_option('lmm_discord_notifications', 'yes');
$chat_title = get_option('lmm_chat_title', '線上客服');
$chat_placeholder = get_option('lmm_chat_placeholder', '請輸入您的問題...');
$require_name = get_option('lmm_require_name', 'yes');
$require_email = get_option('lmm_require_email', 'no');
$require_phone = get_option('lmm_require_phone', 'no');
$chat_position = get_option('lmm_chat_position', 'bottom-right');
$primary_color = get_option('lmm_primary_color', '#06c755');
$use_minimal = get_option('lmm_use_minimal_style', 'yes');
?>

<div class="wrap lmm-settings-wrap">
    <h1>LINE訊息管理 - 設定</h1>
    
    <form method="post" action="">
        <?php wp_nonce_field('lmm_settings_nonce'); ?>
        
        <div class="lmm-settings-section">
            <h2>📢 Discord 通知設定</h2>
            <p class="description">當有新訊息時，系統會透過Discord Webhook發送通知。</p>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="lmm_discord_webhook_url">Discord Webhook URL</label>
                    </th>
                    <td>
                        <input 
                            type="url" 
                            id="lmm_discord_webhook_url" 
                            name="lmm_discord_webhook_url" 
                            value="<?php echo esc_attr($discord_webhook_url); ?>" 
                            class="regular-text"
                            placeholder="https://discord.com/api/webhooks/..."
                        >
                        <button type="button" id="lmm-test-discord" class="button">測試連接</button>
                        <p class="description">
                            <a href="https://support.discord.com/hc/zh-tw/articles/228383668" target="_blank">如何取得Discord Webhook URL？</a>
                        </p>
                        <div id="lmm-discord-test-result"></div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">啟用Discord通知</th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                name="lmm_discord_notifications" 
                                value="yes" 
                                <?php checked($discord_notifications, 'yes'); ?>
                            >
                            當有新訊息時發送Discord通知
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="lmm-settings-section">
            <h2>💬 前台對話框設定</h2>
            
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="lmm_chat_title">對話框標題</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="lmm_chat_title" 
                            name="lmm_chat_title" 
                            value="<?php echo esc_attr($chat_title); ?>" 
                            class="regular-text"
                        >
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lmm_chat_placeholder">訊息輸入提示文字</label>
                    </th>
                    <td>
                        <input 
                            type="text" 
                            id="lmm_chat_placeholder" 
                            name="lmm_chat_placeholder" 
                            value="<?php echo esc_attr($chat_placeholder); ?>" 
                            class="regular-text"
                        >
                    </td>
                </tr>
                <tr>
                    <th scope="row">必填欄位</th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                name="lmm_require_name" 
                                value="yes" 
                                <?php checked($require_name, 'yes'); ?>
                            >
                            要求填寫姓名
                        </label><br>
                        <label>
                            <input 
                                type="checkbox" 
                                name="lmm_require_email" 
                                value="yes" 
                                <?php checked($require_email, 'yes'); ?>
                            >
                            要求填寫Email
                        </label><br>
                        <label>
                            <input 
                                type="checkbox" 
                                name="lmm_require_phone" 
                                value="yes" 
                                <?php checked($require_phone, 'yes'); ?>
                            >
                            要求填寫電話
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lmm_chat_position">對話框位置</label>
                    </th>
                    <td>
                        <select id="lmm_chat_position" name="lmm_chat_position">
                            <option value="bottom-right" <?php selected($chat_position, 'bottom-right'); ?>>右下角</option>
                            <option value="bottom-left" <?php selected($chat_position, 'bottom-left'); ?>>左下角</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="lmm_primary_color">主題顏色</label>
                    </th>
                    <td>
                        <input 
                            type="color" 
                            id="lmm_primary_color" 
                            name="lmm_primary_color" 
                            value="<?php echo esc_attr($primary_color); ?>"
                        >
                        <p class="description">對話框的主題顏色（預設為LINE綠色）</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">UI風格</th>
                    <td>
                        <label>
                            <input 
                                type="checkbox" 
                                name="lmm_use_minimal_style" 
                                value="yes" 
                                <?php checked($use_minimal, 'yes'); ?>
                            >
                            使用簡約風格（推薦）
                        </label>
                        <p class="description">簡約版去除多餘動畫和裝飾，載入更快速</p>
                    </td>
                </tr>
            </table>
        </div>
        
        <div class="lmm-settings-section">
            <h2>📝 短代碼使用說明</h2>
            <p>在任何頁面或文章中使用以下短代碼嵌入對話框：</p>
            <div class="lmm-shortcode-box">
                <code>[line_message_chat]</code>
            </div>
            <p class="description">對話框也會自動以浮動視窗的形式顯示在網站右下角。</p>
        </div>
        
        <p class="submit">
            <button type="submit" name="lmm_save_settings" class="button button-primary button-large">
                儲存設定
            </button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('#lmm-test-discord').on('click', function() {
        var $button = $(this);
        var $result = $('#lmm-discord-test-result');
        var webhookUrl = $('#lmm_discord_webhook_url').val();
        
        if (!webhookUrl) {
            $result.html('<div class="notice notice-error inline"><p>請先輸入Webhook URL</p></div>');
            return;
        }
        
        $button.prop('disabled', true).text('測試中...');
        $result.html('');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'lmm_test_discord',
                nonce: lmmAdmin.nonce,
                webhook_url: webhookUrl
            },
            success: function(response) {
                if (response.success) {
                    $result.html('<div class="notice notice-success inline"><p>' + response.data.message + '</p></div>');
                } else {
                    $result.html('<div class="notice notice-error inline"><p>' + response.data.message + '</p></div>');
                }
            },
            error: function() {
                $result.html('<div class="notice notice-error inline"><p>連接測試失敗</p></div>');
            },
            complete: function() {
                $button.prop('disabled', false).text('測試連接');
            }
        });
    });
});
</script>
<?php
