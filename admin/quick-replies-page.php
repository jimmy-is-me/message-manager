<?php
/**
 * 快速回覆範本管理頁面
 */

if (!defined('ABSPATH')) {
    exit;
}

// 處理新增/編輯/刪除操作
if (isset($_POST['lmm_save_quick_reply']) && check_admin_referer('lmm_quick_reply_nonce')) {
    $template_id = intval($_POST['template_id'] ?? 0);
    
    $data = array(
        'title' => sanitize_text_field($_POST['title']),
        'content' => sanitize_textarea_field($_POST['content']),
        'shortcut' => sanitize_text_field($_POST['shortcut']),
        'category' => sanitize_text_field($_POST['category']),
        'sort_order' => intval($_POST['sort_order'])
    );
    
    if ($template_id > 0) {
        LMM_Database::update_quick_reply($template_id, $data);
        echo '<div class="notice notice-success is-dismissible"><p>快速回覆已更新！</p></div>';
    } else {
        LMM_Database::insert_quick_reply($data);
        echo '<div class="notice notice-success is-dismissible"><p>快速回覆已新增！</p></div>';
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    LMM_Database::delete_quick_reply($id);
    echo '<div class="notice notice-success is-dismissible"><p>快速回覆已刪除！</p></div>';
}

// 獲取所有快速回覆
$quick_replies = LMM_Database::get_quick_replies();

// 編輯模式
$edit_mode = isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id']);
$editing_template = null;
if ($edit_mode) {
    $edit_id = intval($_GET['id']);
    foreach ($quick_replies as $template) {
        if ($template->id == $edit_id) {
            $editing_template = $template;
            break;
        }
    }
}
?>

<div class="wrap lmm-admin-wrap">
    <h1>快速回覆範本管理</h1>
    <p class="description">建立常用的回覆範本，提升客服效率。在後台回覆時可以快速插入範本內容。</p>
    
    <div class="lmm-quick-replies-container">
        <div class="lmm-quick-replies-form">
            <h2><?php echo $edit_mode ? '編輯範本' : '新增範本'; ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('lmm_quick_reply_nonce'); ?>
                <input type="hidden" name="template_id" value="<?php echo $editing_template ? esc_attr($editing_template->id) : '0'; ?>">
                
                <table class="form-table">
                    <tr>
                        <th><label for="title">範本名稱 *</label></th>
                        <td>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   class="regular-text" 
                                   value="<?php echo $editing_template ? esc_attr($editing_template->title) : ''; ?>"
                                   required>
                            <p class="description">例如：歡迎訊息、營業時間</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="content">範本內容 *</label></th>
                        <td>
                            <textarea id="content" 
                                      name="content" 
                                      rows="5" 
                                      class="large-text" 
                                      required><?php echo $editing_template ? esc_textarea($editing_template->content) : ''; ?></textarea>
                            <p class="description">這將是插入的回覆內容</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="shortcut">快捷指令</label></th>
                        <td>
                            <input type="text" 
                                   id="shortcut" 
                                   name="shortcut" 
                                   class="regular-text" 
                                   value="<?php echo $editing_template ? esc_attr($editing_template->shortcut) : ''; ?>"
                                   placeholder="/hello">
                            <p class="description">選填。例如：/hello 可快速插入此範本</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="category">分類</label></th>
                        <td>
                            <select id="category" name="category">
                                <option value="general" <?php selected($editing_template ? $editing_template->category : 'general', 'general'); ?>>一般</option>
                                <option value="greeting" <?php selected($editing_template ? $editing_template->category : '', 'greeting'); ?>>問候</option>
                                <option value="info" <?php selected($editing_template ? $editing_template->category : '', 'info'); ?>>資訊</option>
                                <option value="support" <?php selected($editing_template ? $editing_template->category : '', 'support'); ?>>技術支援</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="sort_order">排序</label></th>
                        <td>
                            <input type="number" 
                                   id="sort_order" 
                                   name="sort_order" 
                                   value="<?php echo $editing_template ? esc_attr($editing_template->sort_order) : '0'; ?>"
                                   min="0"
                                   style="width: 80px;">
                            <p class="description">數字越小越前面</p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <button type="submit" name="lmm_save_quick_reply" class="button button-primary">
                        <?php echo $edit_mode ? '更新範本' : '新增範本'; ?>
                    </button>
                    <?php if ($edit_mode) : ?>
                        <a href="<?php echo admin_url('admin.php?page=line-message-quick-replies'); ?>" class="button">取消編輯</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>
        
        <div class="lmm-quick-replies-list">
            <h2>現有範本</h2>
            
            <?php if (empty($quick_replies)) : ?>
                <p>目前沒有快速回覆範本。</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 20%;">名稱</th>
                            <th style="width: 40%;">內容預覽</th>
                            <th style="width: 15%;">快捷指令</th>
                            <th style="width: 10%;">分類</th>
                            <th style="width: 15%;">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quick_replies as $template) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($template->title); ?></strong></td>
                                <td>
                                    <?php 
                                    $preview = mb_strlen($template->content) > 60 
                                        ? mb_substr($template->content, 0, 60) . '...' 
                                        : $template->content;
                                    echo esc_html($preview);
                                    ?>
                                </td>
                                <td>
                                    <?php if (!empty($template->shortcut)) : ?>
                                        <code><?php echo esc_html($template->shortcut); ?></code>
                                    <?php else : ?>
                                        <span class="description">無</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $categories = array(
                                        'general' => '一般',
                                        'greeting' => '問候',
                                        'info' => '資訊',
                                        'support' => '技術支援'
                                    );
                                    echo esc_html($categories[$template->category] ?? $template->category);
                                    ?>
                                </td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=line-message-quick-replies&action=edit&id=' . $template->id); ?>" 
                                       class="button button-small">編輯</a>
                                    <a href="<?php echo admin_url('admin.php?page=line-message-quick-replies&action=delete&id=' . $template->id); ?>" 
                                       class="button button-small"
                                       onclick="return confirm('確定要刪除此範本嗎？');">刪除</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.lmm-quick-replies-container {
    display: grid;
    grid-template-columns: 1fr 1.5fr;
    gap: 20px;
    margin-top: 20px;
}

.lmm-quick-replies-form,
.lmm-quick-replies-list {
    background: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 20px;
}

.lmm-quick-replies-form h2,
.lmm-quick-replies-list h2 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 1px solid #e0e0e0;
}

@media (max-width: 1200px) {
    .lmm-quick-replies-container {
        grid-template-columns: 1fr;
    }
}
</style>
<?php
