<?php
/**
 * 表情符號選擇器類別
 */

if (!defined('ABSPATH')) {
    exit;
}

class LMM_Emoji_Picker {
    
    /**
     * 常用表情符號列表
     */
    public static function get_emoji_list() {
        return array(
            'smileys' => array(
                'name' => '笑臉',
                'emojis' => array(
                    '😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊',
                    '😇', '🙂', '🙃', '😉', '😌', '😍', '🥰', '😘',
                    '😗', '😙', '😚', '😋', '😛', '😝', '😜', '🤪'
                )
            ),
            'gestures' => array(
                'name' => '手勢',
                'emojis' => array(
                    '👋', '🤚', '🖐', '✋', '🖖', '👌', '🤌', '🤏',
                    '✌', '🤞', '🤟', '🤘', '🤙', '👈', '👉', '👆',
                    '👇', '☝', '👍', '👎', '✊', '👊', '🤛', '🤜'
                )
            ),
            'hearts' => array(
                'name' => '愛心',
                'emojis' => array(
                    '❤', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍',
                    '🤎', '💔', '❣', '💕', '💞', '💓', '💗', '💖',
                    '💘', '💝', '💟', '💌', '💋', '💏', '💑', '🥰'
                )
            ),
            'objects' => array(
                'name' => '物品',
                'emojis' => array(
                    '📱', '💻', '⌨', '🖥', '🖨', '🖱', '📞', '☎',
                    '📧', '✉', '📨', '📩', '📮', '📫', '📪', '📬',
                    '📭', '📦', '📋', '📄', '📃', '📑', '📊', '📈'
                )
            ),
            'symbols' => array(
                'name' => '符號',
                'emojis' => array(
                    '✅', '❌', '⭕', '❗', '❓', '⚠', '🔔', '🔕',
                    '⏰', '⏱', '⏲', '⏳', '⌛', '📢', '📣', '🔊',
                    '🔇', '🔈', '🔉', '💬', '💭', '🗨', '🗯', '💡'
                )
            ),
            'nature' => array(
                'name' => '自然',
                'emojis' => array(
                    '🌟', '✨', '⭐', '🌙', '☀', '🌤', '⛅', '🌥',
                    '☁', '🌦', '🌧', '⛈', '🌩', '🌨', '❄', '☃',
                    '⛄', '🌬', '💨', '🌪', '🌈', '☂', '⛱', '⚡'
                )
            )
        );
    }
    
    /**
     * 渲染表情符號選擇器HTML
     */
    public static function render_picker() {
        $emoji_categories = self::get_emoji_list();
        
        ob_start();
        ?>
        <div class="lmm-emoji-picker" id="lmm-emoji-picker" style="display: none;">
            <div class="lmm-emoji-header">
                <div class="lmm-emoji-tabs">
                    <?php $first = true; foreach ($emoji_categories as $key => $category) : ?>
                        <button type="button" 
                                class="lmm-emoji-tab <?php echo $first ? 'active' : ''; ?>" 
                                data-category="<?php echo esc_attr($key); ?>">
                            <?php echo esc_html($category['name']); ?>
                        </button>
                        <?php $first = false; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="lmm-emoji-content">
                <?php $first = true; foreach ($emoji_categories as $key => $category) : ?>
                    <div class="lmm-emoji-category <?php echo $first ? 'active' : ''; ?>" 
                         data-category="<?php echo esc_attr($key); ?>">
                        <div class="lmm-emoji-grid">
                            <?php foreach ($category['emojis'] as $emoji) : ?>
                                <button type="button" 
                                        class="lmm-emoji-item" 
                                        data-emoji="<?php echo esc_attr($emoji); ?>">
                                    <?php echo $emoji; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php $first = false; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
