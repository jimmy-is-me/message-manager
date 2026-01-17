/**
 * LINE訊息管理系統 - 前台腳本
 */

(function($) {
    'use strict';
    
    let conversationId = null;
    let lastMessageId = 0;
    let customerInfoSubmitted = false;
    let pollInterval = null;
    
    $(document).ready(function() {
        initChatWidget();
        initChatForm();
        initEmojiPicker();
        loadConversationFromStorage();
        autoResizeTextarea();
    });
    
    /**
     * 初始化聊天小工具
     */
    function initChatWidget() {
        const $toggle = $('#lmm-chat-toggle');
        const $window = $('#lmm-chat-window');
        const $close = $('#lmm-close-chat');
        
        $toggle.on('click', function() {
            toggleChatWindow();
        });
        
        $close.on('click', function() {
            closeChatWindow();
        });
    }
    
    /**
     * 切換聊天視窗
     */
    function toggleChatWindow() {
        const $toggle = $('#lmm-chat-toggle');
        const $window = $('#lmm-chat-window');
        
        $toggle.toggleClass('active');
        $window.toggleClass('active');
        
        if ($window.hasClass('active')) {
            $('#lmm-message-input').focus();
            clearUnreadBadge();
            
            // 開始輪詢新訊息
            if (conversationId && !pollInterval) {
                startPolling();
            }
        } else {
            stopPolling();
        }
    }
    
    /**
     * 關閉聊天視窗
     */
    function closeChatWindow() {
        $('#lmm-chat-toggle').removeClass('active');
        $('#lmm-chat-window').removeClass('active');
        stopPolling();
    }
    
    /**
     * 初始化聊天表單
     */
    function initChatForm() {
        const $form = $('#lmm-chat-form');
        
        $form.on('submit', function(e) {
            e.preventDefault();
            handleMessageSubmit();
        });
        
        // Enter鍵發送（Shift+Enter換行）
        $('#lmm-message-input').on('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                $form.submit();
            }
        });
    }
    
    /**
     * 處理訊息發送
     */
    function handleMessageSubmit() {
        const $messageInput = $('#lmm-message-input');
        const message = $messageInput.val().trim();
        
        if (!message) {
            showError(lmmFrontend.strings.messageRequired);
            return;
        }
        
        // 如果是第一次發送，需要客戶資訊
        if (!customerInfoSubmitted) {
            const customerData = getCustomerInfo();
            if (!validateCustomerInfo(customerData)) {
                return;
            }
        }
        
        // 顯示客戶訊息
        appendMessage(message, 'customer');
        
        // 清空輸入框
        $messageInput.val('').css('height', 'auto');
        
        // 禁用發送按鈕
        $('.lmm-send-btn').prop('disabled', true);
        
        // 發送AJAX請求
        sendMessage(message);
    }
    
    /**
     * 獲取客戶資訊
     */
    function getCustomerInfo() {
        return {
            customer_name: $('#lmm-customer-name').val() || '',
            customer_email: $('#lmm-customer-email').val() || '', // Email必填
            customer_phone: $('#lmm-customer-phone').val() || ''
        };
    }
    
    /**
     * 驗證客戶資訊
     */
    function validateCustomerInfo(data) {
        // Email 永遠必填
        if (!data.customer_email) {
            showError('請輸入您的Email地址');
            $('#lmm-customer-email').focus();
            return false;
        }
        
        if (!isValidEmail(data.customer_email)) {
            showError(lmmFrontend.strings.invalidEmail);
            $('#lmm-customer-email').focus();
            return false;
        }
        
        if (lmmFrontend.requireName === 'yes' && !data.customer_name) {
            showError(lmmFrontend.strings.nameRequired);
            $('#lmm-customer-name').focus();
            return false;
        }
        
        if (lmmFrontend.requirePhone === 'yes' && !data.customer_phone) {
            showError(lmmFrontend.strings.phoneRequired);
            $('#lmm-customer-phone').focus();
            return false;
        }
        
        return true;
    }
    
    /**
     * 驗證Email格式
     */
    function isValidEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }
    
    /**
     * 發送訊息到伺服器
     */
    function sendMessage(message) {
        const customerData = getCustomerInfo();
        
        $.ajax({
            url: lmmFrontend.ajaxUrl,
            type: 'POST',
            data: {
                action: 'lmm_send_customer_message',
                nonce: lmmFrontend.nonce,
                message: message,
                conversation_id: conversationId || '',
                customer_name: customerData.customer_name,
                customer_email: customerData.customer_email,
                customer_phone: customerData.customer_phone
            },
            success: function(response) {
                if (response.success) {
                    if (!conversationId) {
                        conversationId = response.data.conversation_id;
                        saveConversationToStorage();
                        startPolling();
                    }
                    
                    lastMessageId = response.data.message_id;
                    
                    if (!customerInfoSubmitted) {
                        customerInfoSubmitted = true;
                        hideCustomerInfoForm();
                    }
                } else {
                    showError(response.data.message || lmmFrontend.strings.sendError);
                }
            },
            error: function() {
                showError(lmmFrontend.strings.sendError);
            },
            complete: function() {
                $('.lmm-send-btn').prop('disabled', false);
            }
        });
    }
    
    /**
     * 附加訊息到聊天區
     */
    function appendMessage(message, type, time) {
        const $messagesArea = $('#lmm-messages-area');
        const timeString = time || getCurrentTime();
        
        const messageHtml = `
            <div class="lmm-message lmm-message-${type}">
                <div class="lmm-message-bubble">
                    ${escapeHtml(message)}
                    <span class="lmm-message-time">${timeString}</span>
                </div>
            </div>
        `;
        
        $messagesArea.append(messageHtml);
        scrollToBottom();
    }
    
    /**
     * 滾動到底部
     */
    function scrollToBottom() {
        const $messagesArea = $('#lmm-messages-area');
        $messagesArea.animate({
            scrollTop: $messagesArea[0].scrollHeight
        }, 300);
    }
    
    /**
     * 開始輪詢新訊息
     */
    function startPolling() {
        if (pollInterval) return;
        
        pollInterval = setInterval(function() {
            checkNewMessages();
        }, 3000); // 每3秒檢查一次
    }
    
    /**
     * 停止輪詢
     */
    function stopPolling() {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    }
    
    /**
     * 檢查新訊息
     */
    function checkNewMessages() {
        if (!conversationId) return;
        
        $.ajax({
            url: lmmFrontend.ajaxUrl,
            type: 'GET',
            data: {
                action: 'lmm_get_new_messages',
                conversation_id: conversationId,
                last_message_id: lastMessageId
            },
            success: function(response) {
                if (response.success && response.data.messages.length > 0) {
                    response.data.messages.forEach(function(msg) {
                        appendMessage(msg.message, 'admin', formatDateTime(msg.created_at));
                        lastMessageId = Math.max(lastMessageId, msg.id);
                        
                        // 如果視窗未開啟，顯示未讀徽章
                        if (!$('#lmm-chat-window').hasClass('active')) {
                            showUnreadBadge();
                        }
                    });
                }
            }
        });
    }
    
    /**
     * 顯示未讀徽章
     */
    function showUnreadBadge() {
        const $badge = $('#lmm-unread-badge');
        let count = parseInt($badge.text()) || 0;
        count++;
        $badge.text(count).show();
    }
    
    /**
     * 清除未讀徽章
     */
    function clearUnreadBadge() {
        $('#lmm-unread-badge').text('0').hide();
    }
    
    /**
     * 隱藏客戶資訊表單
     */
    function hideCustomerInfoForm() {
        $('#lmm-customer-info-form').slideUp(300, function() {
            // 顯示確認訊息
            const email = $('#lmm-customer-email').val();
            appendMessage('已收到您的聯絡資訊！我們會透過 ' + email + ' 與您聯繫。', 'admin');
        });
    }
    
    /**
     * 自動調整textarea高度
     */
    function autoResizeTextarea() {
        $('#lmm-message-input').on('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
        });
    }
    
    /**
     * 初始化表情符號選擇器
     */
    function initEmojiPicker() {
        const $emojiBtn = $('#lmm-emoji-btn');
        const $emojiPicker = $('#lmm-emoji-picker');
        const $messageInput = $('#lmm-message-input');
        
        // 切換表情符號選擇器
        $emojiBtn.on('click', function(e) {
            e.stopPropagation();
            $emojiPicker.toggle();
            $emojiBtn.toggleClass('active');
        });
        
        // 切換表情符號分類
        $('.lmm-emoji-tab').on('click', function() {
            const category = $(this).data('category');
            
            $('.lmm-emoji-tab').removeClass('active');
            $(this).addClass('active');
            
            $('.lmm-emoji-category').removeClass('active');
            $('.lmm-emoji-category[data-category="' + category + '"]').addClass('active');
        });
        
        // 選擇表情符號
        $('.lmm-emoji-item').on('click', function() {
            const emoji = $(this).data('emoji');
            const currentValue = $messageInput.val();
            const cursorPos = $messageInput[0].selectionStart;
            
            // 在光標位置插入表情符號
            const newValue = currentValue.substring(0, cursorPos) + emoji + currentValue.substring(cursorPos);
            $messageInput.val(newValue);
            
            // 恢復光標位置
            const newCursorPos = cursorPos + emoji.length;
            $messageInput[0].setSelectionRange(newCursorPos, newCursorPos);
            
            // 聚焦輸入框
            $messageInput.focus();
            
            // 觸發input事件以調整高度
            $messageInput.trigger('input');
        });
        
        // 點擊外部關閉選擇器
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#lmm-emoji-picker, #lmm-emoji-btn').length) {
                $emojiPicker.hide();
                $emojiBtn.removeClass('active');
            }
        });
    }
    
    /**
     * 儲存對話ID到localStorage
     */
    function saveConversationToStorage() {
        if (conversationId) {
            try {
                localStorage.setItem('lmm_conversation_id', conversationId);
                
                // 同時儲存Email（方便下次使用）
                const email = $('#lmm-customer-email').val();
                if (email) {
                    localStorage.setItem('lmm_customer_email', email);
                }
            } catch (e) {
                console.error('Failed to save conversation ID', e);
            }
        }
    }
    
    /**
     * 從localStorage載入對話ID
     */
    function loadConversationFromStorage() {
        try {
            const savedId = localStorage.getItem('lmm_conversation_id');
            const savedEmail = localStorage.getItem('lmm_customer_email');
            
            if (savedId && savedEmail) {
                conversationId = savedId;
                customerInfoSubmitted = true;
                
                // 預填Email（方便客戶）
                $('#lmm-customer-email').val(savedEmail);
                
                // 立即隱藏表單
                $('#lmm-customer-info-form').hide();
            }
        } catch (e) {
            console.error('Failed to load conversation ID', e);
        }
    }
    
    /**
     * 顯示錯誤訊息
     */
    function showError(message) {
        alert(message);
    }
    
    /**
     * 獲取當前時間
     */
    function getCurrentTime() {
        const now = new Date();
        return now.getHours().toString().padStart(2, '0') + ':' + 
               now.getMinutes().toString().padStart(2, '0');
    }
    
    /**
     * 格式化日期時間
     */
    function formatDateTime(datetime) {
        const date = new Date(datetime);
        return date.getHours().toString().padStart(2, '0') + ':' + 
               date.getMinutes().toString().padStart(2, '0');
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
        return text.replace(/[&<>"']/g, function(m) { return map[m]; }).replace(/\n/g, '<br>');
    }
    
})(jQuery);
