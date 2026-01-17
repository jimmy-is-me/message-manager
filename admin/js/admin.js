/**
 * LINE訊息管理系統 - 後台腳本
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        initReplyForm();
        initQuickReplies();
        scrollToBottom();
        autoRefreshMessages();
    });
    
    /**
     * 初始化回覆表單
     */
    function initReplyForm() {
        const $form = $('#lmm-admin-reply-form');
        
        if ($form.length === 0) return;
        
        $form.on('submit', function(e) {
            e.preventDefault();
            handleAdminReply();
        });
    }
    
    /**
     * 處理管理員回覆
     */
    function handleAdminReply() {
        const $form = $('#lmm-admin-reply-form');
        const $textarea = $('#lmm-reply-message');
        const $button = $form.find('button[type="submit"]');
        const conversationId = $form.data('conversation-id');
        const message = $textarea.val().trim();
        
        if (!message) {
            alert('請輸入回覆內容');
            return;
        }
        
        // 禁用按鈕和文本框
        $button.prop('disabled', true).text(lmmAdmin.strings.sendingReply);
        $textarea.prop('disabled', true);
        
        // 發送AJAX請求
        $.ajax({
            url: lmmAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'lmm_send_admin_reply',
                nonce: lmmAdmin.nonce,
                conversation_id: conversationId,
                message: message
            },
            success: function(response) {
                if (response.success) {
                    // 清空文本框
                    $textarea.val('');
                    
                    // 添加訊息到對話區
                    appendAdminMessage(message);
                    
                    // 顯示成功提示
                    showNotice('success', lmmAdmin.strings.replySuccess);
                    
                    // 滾動到底部
                    scrollToBottom();
                } else {
                    alert(response.data.message || lmmAdmin.strings.replyError);
                }
            },
            error: function() {
                alert(lmmAdmin.strings.replyError);
            },
            complete: function() {
                // 恢復按鈕和文本框
                $button.prop('disabled', false).text('發送回覆');
                $textarea.prop('disabled', false).focus();
            }
        });
    }
    
    /**
     * 添加管理員訊息到對話區
     */
    function appendAdminMessage(message) {
        const $container = $('#lmm-messages-container');
        const now = new Date();
        const timeString = now.getFullYear() + '-' + 
                          padZero(now.getMonth() + 1) + '-' + 
                          padZero(now.getDate()) + ' ' + 
                          padZero(now.getHours()) + ':' + 
                          padZero(now.getMinutes()) + ':' + 
                          padZero(now.getSeconds());
        
        const currentUser = lmmAdmin.currentUser || '管理員';
        
        const messageHtml = `
            <div class="lmm-message lmm-message-admin">
                <div class="lmm-message-header">
                    <span class="lmm-message-sender">${escapeHtml(currentUser)}</span>
                    <span class="lmm-message-time">${timeString}</span>
                </div>
                <div class="lmm-message-content">
                    ${escapeHtml(message).replace(/\n/g, '<br>')}
                </div>
            </div>
        `;
        
        $container.append(messageHtml);
    }
    
    /**
     * 滾動到底部
     */
    function scrollToBottom() {
        const $container = $('#lmm-messages-container');
        if ($container.length > 0) {
            $container.animate({
                scrollTop: $container[0].scrollHeight
            }, 300);
        }
    }
    
    /**
     * 自動刷新訊息（輪詢）
     */
    function autoRefreshMessages() {
        const $container = $('#lmm-messages-container');
        
        if ($container.length === 0) return;
        
        // 每10秒檢查一次新訊息
        setInterval(function() {
            // 可以在這裡添加AJAX請求來檢查新訊息
            // 暫時不實現，避免過多請求
        }, 10000);
    }
    
    /**
     * 顯示通知
     */
    function showNotice(type, message) {
        const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        const $notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.lmm-admin-wrap h1').after($notice);
        
        // 3秒後自動移除
        setTimeout(function() {
            $notice.fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    /**
     * 補零函數
     */
    function padZero(num) {
        return num.toString().padStart(2, '0');
    }
    
    /**
     * 轉義HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * 初始化快速回覆
     */
    function initQuickReplies() {
        $('.lmm-quick-reply-btn').on('click', function() {
            const content = $(this).data('content');
            const $textarea = $('#lmm-reply-message');
            
            // 插入範本內容
            const currentValue = $textarea.val();
            const newValue = currentValue ? currentValue + '\n\n' + content : content;
            $textarea.val(newValue);
            
            // 聚焦輸入框
            $textarea.focus();
        });
    }
    
    /**
     * 標記為已讀
     */
    function markAsRead(conversationId) {
        $.ajax({
            url: lmmAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'lmm_mark_as_read',
                conversation_id: conversationId
            }
        });
    }
    
    // 如果在對話詳情頁面，自動標記為已讀
    if ($('#lmm-messages-container').length > 0) {
        const conversationId = $('#lmm-admin-reply-form').data('conversation-id');
        if (conversationId) {
            markAsRead(conversationId);
        }
    }
    
})(jQuery);
