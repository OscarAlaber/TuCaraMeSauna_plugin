/**
 * TCMS Messaging JavaScript
 *
 * @package TCMS_Messaging_System
 */

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Initialize messaging
    TCMSMessaging.init();
});

// Messaging object
const TCMSMessaging = {
    /**
     * Active conversation user ID
     */
    activeConversation: null,
    
    /**
     * Message polling interval
     */
    messageInterval: null,
    
    /**
     * Last message timestamp
     */
    lastMessageTime: null,
    
    /**
     * Initialize messaging
     */
    init: function() {
        // Check if we're on the messages page
        if (document.querySelector('.tcms-messages-container')) {
            // Load conversations
            this.loadConversations();
            
            // Set up message form
            this.setupMessageForm();
            
            // Check URL for conversation parameter
            const urlParams = new URLSearchParams(window.location.search);
            const conversationWith = urlParams.get('conversation_with');
            
            if (conversationWith) {
                this.loadUserConversation(conversationWith);
            }
            
            // Set up new conversation button
            const newConversationBtn = document.querySelector('.tcms-new-conversation');
            if (newConversationBtn) {
                newConversationBtn.addEventListener('click', this.showNewConversationModal.bind(this));
            }
            
            // Set up conversation search
            const searchInput = document.querySelector('.tcms-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', this.filterConversations.bind(this));
            }
        }
    },
    
    /**
     * Load conversations
     */
    loadConversations: function() {
        if (typeof tcms_ajax === 'undefined') {
            console.error('AJAX object not available');
            return;
        }
        
        const conversationsList = document.querySelector('.tcms-conversations-list');
        if (!conversationsList) {
            return;
        }
        
        // Show loading state
        conversationsList.innerHTML = '<div class="tcms-loading"><div class="tcms-loading-spinner"></div><p>Loading conversations...</p></div>';
        
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_get_conversations',
                nonce: tcms_ajax.nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderConversations(data.data.conversations);
            } else {
                conversationsList.innerHTML = '<div class="tcms-error">Error loading conversations</div>';
            }
        })
        .catch(error => {
            console.error('Error loading conversations:', error);
            conversationsList.innerHTML = '<div class="tcms-error">Error loading conversations</div>';
        });
    },
    
    /**
     * Render conversations
     */
    renderConversations: function(conversations) {
        const conversationsList = document.querySelector('.tcms-conversations-list');
        
        if (!conversationsList) {
            return;
        }
        
        if (!conversations || conversations.length === 0) {
            conversationsList.innerHTML = `
                <div class="tcms-empty-conversations">
                    <div class="tcms-empty-icon">💬</div>
                    <h3>No conversations yet</h3>
                    <p>Start a new conversation to connect with users</p>
                    <button class="tcms-btn tcms-btn-primary tcms-new-conversation">
                        Start New Conversation
                    </button>
                </div>
            `;
            return;
        }
        
        let html = '';
        
        conversations.forEach(conversation => {
            const isOnline = this.getUserStatus(conversation.last_active) === 'online';
            
            html += `
                <div class="tcms-conversation-item${conversation.other_user_id === this.activeConversation ? ' active' : ''}" data-user-id="${conversation.other_user_id}">
                    <div class="tcms-conversation-avatar">
                        <img src="${conversation.avatar_url || this.getDefaultAvatar()}" alt="${conversation.display_name}">
                        <div class="tcms-online-indicator ${isOnline ? 'online' : 'offline'}"></div>
                    </div>
                    <div class="tcms-conversation-info">
                        <div class="tcms-conversation-name">
                            ${conversation.display_name}
                            ${conversation.is_premium ? '<span class="tcms-premium-icon">👑</span>' : ''}
                        </div>
                        <div class="tcms-conversation-preview">
                            ${conversation.last_sender_id == tcms_ajax.user_id ? 'You: ' : ''}
                            ${this.getMessagePreview(conversation.last_message, conversation.message_type)}
                        </div>
                    </div>
                    <div class="tcms-conversation-meta">
                        <div class="tcms-conversation-time">${this.formatTime(conversation.last_message_time)}</div>
                        ${conversation.unread_count > 0 ? `<div class="tcms-unread-badge">${conversation.unread_count}</div>` : ''}
                    </div>
                </div>
            `;
        });
        
        conversationsList.innerHTML = html;
        
        // Add click event to conversation items
        const conversationItems = conversationsList.querySelectorAll('.tcms-conversation-item');
        conversationItems.forEach(item => {
            item.addEventListener('click', () => {
                const userId = item.getAttribute('data-user-id');
                this.loadUserConversation(userId);
            });
        });
    },
    
    /**
     * Load user conversation
     */
    loadUserConversation: function(userId) {
        userId = parseInt(userId);
        
        if (isNaN(userId) || userId <= 0) {
            return;
        }
        
        this.activeConversation = userId;
        
        // Update active state in conversation list
        const conversationItems = document.querySelectorAll('.tcms-conversation-item');
        conversationItems.forEach(item => {
            item.classList.toggle('active', parseInt(item.getAttribute('data-user-id')) === userId);
        });
        
        // Show loading state in chat area
        const chatArea = document.querySelector('.tcms-chat-area');
        const chatContainer = document.querySelector('.tcms-chat-messages');
        
        if (!chatArea || !chatContainer) {
            return;
        }
        
        // Show chat area if it was hidden
        document.querySelector('.tcms-chat-welcome')?.classList.add('tcms-hidden');
        chatArea.classList.remove('tcms-hidden');
        
        // Show loading state
        chatContainer.innerHTML = '<div class="tcms-loading"><div class="tcms-loading-spinner"></div><p>Loading messages...</p></div>';
        
        // Load user info for header
        this.loadUserInfo(userId);
        
        // Load messages
        this.loadMessages(userId);
        
        // Set polling for new messages
        this.setupMessagePolling(userId);
        
        // Update URL parameter
        this.updateUrlParameter('conversation_with', userId);
    },
    
    /**
     * Load user info
     */
    loadUserInfo: function(userId) {
        if (typeof tcms_ajax === 'undefined') {
            return;
        }
        
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_get_user_info',
                nonce: tcms_ajax.nonce,
                user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.updateChatHeader(data.data.user);
            }
        })
        .catch(error => {
            console.error('Error loading user info:', error);
        });
    },
    
    /**
     * Update chat header
     */
    updateChatHeader: function(user) {
        const headerName = document.querySelector('.tcms-chat-user-details h3');
        const headerStatus = document.querySelector('.tcms-user-status');
        const headerAvatar = document.querySelector('.tcms-chat-avatar img');
        
        if (headerName) {
            headerName.textContent = user.display_name || 'User';
        }
        
        if (headerStatus) {
            const status = this.getUserStatus(user.last_active);
            
            if (status === 'online') {
                headerStatus.textContent = 'Online';
                headerStatus.className = 'tcms-user-status tcms-status-online';
            } else if (status === 'away') {
                headerStatus.textContent = 'Away';
                headerStatus.className = 'tcms-user-status tcms-status-away';
            } else {
                headerStatus.textContent = `Last seen ${this.formatTime(user.last_active)}`;
                headerStatus.className = 'tcms-user-status';
            }
        }
        
        if (headerAvatar) {
            headerAvatar.src = user.avatar_url || this.getDefaultAvatar();
            headerAvatar.alt = user.display_name || 'User';
        }
    },
    
    /**
     * Load messages
     */
    loadMessages: function(userId) {
        if (typeof tcms_ajax === 'undefined') {
            return;
        }
        
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_get_messages',
                nonce: tcms_ajax.nonce,
                other_user_id: userId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderMessages(data.data.messages);
                
                // Store timestamp of last message
                if (data.data.messages.length > 0) {
                    this.lastMessageTime = data.data.messages[data.data.messages.length - 1].created_at;
                }
                
                // Update UI to reflect read status
                this.updateConversationReadStatus(userId);
            } else {
                const chatContainer = document.querySelector('.tcms-chat-messages');
                if (chatContainer) {
                    chatContainer.innerHTML = '<div class="tcms-error">Error loading messages</div>';
                }
            }
        })
        .catch(error => {
            console.error('Error loading messages:', error);
            const chatContainer = document.querySelector('.tcms-chat-messages');
            if (chatContainer) {
                chatContainer.innerHTML = '<div class="tcms-error">Error loading messages</div>';
            }
        });
    },
    
    /**
     * Render messages
     */
    renderMessages: function(messages) {
        const chatContainer = document.querySelector('.tcms-chat-messages');
        
        if (!chatContainer) {
            return;
        }
        
        if (!messages || messages.length === 0) {
            chatContainer.innerHTML = `
                <div class="tcms-empty-messages">
                    <div class="tcms-empty-icon">💬</div>
                    <p>No messages yet. Send the first message to start the conversation!</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        let lastDate = null;
        
        messages.forEach(message => {
            // Check if we need to show date separator
            const messageDate = new Date(message.created_at).toLocaleDateString();
            if (lastDate !== messageDate) {
                html += `<div class="tcms-date-separator">${messageDate}</div>`;
                lastDate = messageDate;
            }
            
            const isSent = parseInt(message.sender_id) === parseInt(tcms_ajax.user_id);
            
            html += `
                <div class="tcms-message tcms-message-${isSent ? 'sent' : 'received'}" data-message-id="${message.id}">
                    <div class="tcms-message-bubble">
                        ${this.renderMessageContent(message.content, message.message_type, message.attachment_url)}
                        <div class="tcms-message-time">
                            ${this.formatTime(message.created_at)}
                            ${isSent ? `<span class="tcms-message-status">${parseInt(message.read_status) === 1 ? '✓✓' : '✓'}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        chatContainer.innerHTML = html;
        
        // Scroll to bottom
        chatContainer.scrollTop = chatContainer.scrollHeight;
    },
    
    /**
     * Render message content based on type
     */
    renderMessageContent: function(content, type, attachmentUrl) {
        switch (type) {
            case 'image':
                return `
                    <div class="tcms-message-image">
                        <img src="${attachmentUrl}" alt="Image" loading="lazy">
                        ${content ? `<div class="tcms-message-text">${content}</div>` : ''}
                    </div>
                `;
            case 'video':
                return `
                    <div class="tcms-message-video">
                        <video src="${attachmentUrl}" controls></video>
                        ${content ? `<div class="tcms-message-text">${content}</div>` : ''}
                    </div>
                `;
            case 'audio':
                return `
                    <div class="tcms-message-audio">
                        <audio src="${attachmentUrl}" controls></audio>
                        ${content ? `<div class="tcms-message-text">${content}</div>` : ''}
                    </div>
                `;
            case 'location':
                try {
                    const locationData = JSON.parse(content);
                    return `
                        <div class="tcms-message-location">
                            <div class="tcms-location-preview">
                                <img src="https://maps.googleapis.com/maps/api/staticmap?center=${locationData.latitude},${locationData.longitude}&zoom=14&size=300x150&markers=color:red%7C${locationData.latitude},${locationData.longitude}" alt="Location">
                            </div>
                            <div class="tcms-message-text">
                                <strong>📍 Shared location:</strong><br>
                                ${locationData.address || `Latitude: ${locationData.latitude}, Longitude: ${locationData.longitude}`}
                            </div>
                        </div>
                    `;
                } catch (e) {
                    return `<div class="tcms-message-text">${content}</div>`;
                }
            default:
                return `<div class="tcms-message-text">${content}</div>`;
        }
    },
    
    /**
     * Setup message form
     */
    setupMessageForm: function() {
        const messageForm = document.querySelector('.tcms-message-form');
        
        if (!messageForm) {
            return;
        }
        
        messageForm.addEventListener('submit', event => {
            event.preventDefault();
            
            if (!this.activeConversation) {
                return;
            }
            
            const messageInput = messageForm.querySelector('.tcms-message-input');
            const message = messageInput.value.trim();
            
            if (!message) {
                return;
            }
            
            this.sendMessage(this.activeConversation, message);
            
            // Clear input
            messageInput.value = '';
        });
        
        // Auto-resize textarea
        const messageInput = messageForm.querySelector('.tcms-message-input');
        if (messageInput) {
            messageInput.addEventListener('input', () => {
                messageInput.style.height = 'auto';
                messageInput.style.height = (messageInput.scrollHeight < 120 ? messageInput.scrollHeight : 120) + 'px';
            });
        }
    },
    
    /**
     * Send message
     */
    sendMessage: function(receiverId, content, attachmentUrl = '', messageType = 'text') {
        if (typeof tcms_ajax === 'undefined') {
            return;
        }
        
        // Optimistically add message to UI
        const chatContainer = document.querySelector('.tcms-chat-messages');
        
        if (chatContainer) {
            const tempId = 'temp-' + Date.now();
            const tempMessage = document.createElement('div');
            tempMessage.className = 'tcms-message tcms-message-sent';
            tempMessage.setAttribute('data-message-id', tempId);
            tempMessage.innerHTML = `
                <div class="tcms-message-bubble">
                    ${this.renderMessageContent(content, messageType, attachmentUrl)}
                    <div class="tcms-message-time">
                        ${this.formatTime(new Date())}
                        <span class="tcms-message-status">⏱️</span>
                    </div>
                </div>
            `;
            
            chatContainer.appendChild(tempMessage);
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
        
        // Send message to server
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_send_message',
                nonce: tcms_ajax.nonce,
                receiver_id: receiverId,
                content: content,
                attachment_url: attachmentUrl,
                message_type: messageType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update temp message with real ID and status
                const tempMessage = document.querySelector(`.tcms-message[data-message-id="temp-${Date.now()}"]`);
                
                if (tempMessage) {
                    tempMessage.setAttribute('data-message-id', data.data.message_data.id);
                    const statusEl = tempMessage.querySelector('.tcms-message-status');
                    if (statusEl) {
                        statusEl.textContent = '✓';
                    }
                }
                
                // Update conversation list
                this.updateConversationList(receiverId, content, messageType);
                
                // Update last message time
                this.lastMessageTime = data.data.message_data.created_at;
            } else {
                console.error('Error sending message:', data.data.message);
                
                // Show error in UI
                const tempMessage = document.querySelector(`.tcms-message[data-message-id="temp-${Date.now()}"]`);
                
                if (tempMessage) {
                    const statusEl = tempMessage.querySelector('.tcms-message-status');
                    if (statusEl) {
                        statusEl.textContent = '❌';
                        statusEl.title = 'Failed to send';
                    }
                }
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
            
            // Show error in UI
            const tempMessage = document.querySelector(`.tcms-message[data-message-id="temp-${Date.now()}"]`);
            
            if (tempMessage) {
                const statusEl = tempMessage.querySelector('.tcms-message-status');
                if (statusEl) {
                    statusEl.textContent = '❌';
                    statusEl.title = 'Failed to send';
                }
            }
        });
    },
    
    /**
     * Setup message polling
     */
    setupMessagePolling: function(userId) {
        // Clear existing interval
        if (this.messageInterval) {
            clearInterval(this.messageInterval);
        }
        
        // Set new interval
        this.messageInterval = setInterval(() => {
            this.checkNewMessages(userId);
        }, 10000); // Check every 10 seconds
    },
    
    /**
     * Check for new messages
     */
    checkNewMessages: function(userId) {
        if (typeof tcms_ajax === 'undefined' || userId !== this.activeConversation) {
            return;
        }
        
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_get_new_messages',
                nonce: tcms_ajax.nonce,
                other_user_id: userId,
                last_time: this.lastMessageTime
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.messages && data.data.messages.length > 0) {
                // Append new messages
                this.appendNewMessages(data.data.messages);
                
                // Update last message time
                if (data.data.messages.length > 0) {
                    this.lastMessageTime = data.data.messages[data.data.messages.length - 1].created_at;
                }
                
                // Mark messages as read
                this.markMessagesAsRead(userId);
                
                // Update conversation list
                this.loadConversations();
            }
        })
        .catch(error => {
            console.error('Error checking new messages:', error);
        });
    },
    
    /**
     * Append new messages
     */
    appendNewMessages: function(messages) {
        const chatContainer = document.querySelector('.tcms-chat-messages');
        
        if (!chatContainer) {
            return;
        }
        
        let html = '';
        let lastDate = null;
        
        // Check last date in existing messages
        const dateSeparators = chatContainer.querySelectorAll('.tcms-date-separator');
        if (dateSeparators.length > 0) {
            lastDate = dateSeparators[dateSeparators.length - 1].textContent;
        }
        
        messages.forEach(message => {
            // Check if we need to show date separator
            const messageDate = new Date(message.created_at).toLocaleDateString();
            if (lastDate !== messageDate) {
                html += `<div class="tcms-date-separator">${messageDate}</div>`;
                lastDate = messageDate;
            }
            
            const isSent = parseInt(message.sender_id) === parseInt(tcms_ajax.user_id);
            
            html += `
                <div class="tcms-message tcms-message-${isSent ? 'sent' : 'received'}" data-message-id="${message.id}">
                    <div class="tcms-message-bubble">
                        ${this.renderMessageContent(message.content, message.message_type, message.attachment_url)}
                        <div class="tcms-message-time">
                            ${this.formatTime(message.created_at)}
                            ${isSent ? `<span class="tcms-message-status">${parseInt(message.read_status) === 1 ? '✓✓' : '✓'}</span>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });
        
        // Append to container
        chatContainer.innerHTML += html;
        
        // Scroll to bottom
        chatContainer.scrollTop = chatContainer.scrollHeight;
    },
    
    /**
     * Mark messages as read
     */
    markMessagesAsRead: function(userId) {
        if (typeof tcms_ajax === 'undefined') {
            return;
        }
        
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_mark_as_read',
                nonce: tcms_ajax.nonce,
                other_user_id: userId
            })
        });
    },
    
    /**
     * Update conversation read status
     */
    updateConversationReadStatus: function(userId) {
        // Mark conversation as read in the UI
        const conversationItem = document.querySelector(`.tcms-conversation-item[data-user-id="${userId}"]`);
        
        if (conversationItem) {
            const unreadBadge = conversationItem.querySelector('.tcms-unread-badge');
            
            if (unreadBadge) {
                unreadBadge.remove();
            }
        }
        
        // Mark messages as read on the server
        this.markMessagesAsRead(userId);
    },
    
    /**
     * Update conversation list
     */
    updateConversationList: function(userId, message, messageType) {
        // Find conversation item
        const conversationItem = document.querySelector(`.tcms-conversation-item[data-user-id="${userId}"]`);
        
        if (conversationItem) {
            // Update preview
            const previewEl = conversationItem.querySelector('.tcms-conversation-preview');
            
            if (previewEl) {
                previewEl.innerHTML = 'You: ' + this.getMessagePreview(message, messageType);
            }
            
            // Update time
            const timeEl = conversationItem.querySelector('.tcms-conversation-time');
            
            if (timeEl) {
                timeEl.textContent = this.formatTime(new Date());
            }
            
            // Move to top of list
            const parent = conversationItem.parentNode;
            parent.insertBefore(conversationItem, parent.firstChild);
        } else {
            // Conversation doesn't exist in list, reload conversations
            this.loadConversations();
        }
    },
    
    /**
     * Show new conversation modal
     */
    showNewConversationModal: function() {
        // Create modal if it doesn't exist
        let modal = document.getElementById('tcms-new-conversation-modal');
        
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'tcms-new-conversation-modal';
            modal.className = 'tcms-modal';
            modal.innerHTML = `
                <div class="tcms-modal-content">
                    <div class="tcms-modal-header">
                        <h3>New Conversation</h3>
                        <button class="tcms-modal-close">&times;</button>
                    </div>
                    <div class="tcms-modal-body">
                        <div class="tcms-form-group">
                            <label for="tcms-user-search">Search User</label>
                            <input type="text" id="tcms-user-search" class="tcms-input" placeholder="Search by name">
                        </div>
                        <div class="tcms-user-search-results"></div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Set up search input
            const searchInput = modal.querySelector('#tcms-user-search');
            
            if (searchInput) {
                searchInput.addEventListener('input', this.searchUsers.bind(this));
            }
            
            // Set up close button
            const closeBtn = modal.querySelector('.tcms-modal-close');
            
            if (closeBtn) {
                closeBtn.addEventListener('click', () => {
                    modal.style.display = 'none';
                });
            }
            
            // Close when clicking outside
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
        
        // Show modal
        modal.style.display = 'flex';
    },
    
    /**
     * Search users
     */
    searchUsers: function(event) {
        const searchTerm = event.target.value.trim();
        const resultsContainer = document.querySelector('.tcms-user-search-results');
        
        if (!resultsContainer) {
            return;
        }
        
        if (searchTerm.length < 2) {
            resultsContainer.innerHTML = '<div class="tcms-search-info">Enter at least 2 characters to search</div>';
            return;
        }
        
        resultsContainer.innerHTML = '<div class="tcms-loading"><div class="tcms-loading-spinner"></div><p>Searching users...</p></div>';
        
        if (typeof tcms_ajax === 'undefined') {
            return;
        }
        
        fetch(tcms_ajax.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'tcms_search_users',
                nonce: tcms_ajax.nonce,
                search: searchTerm
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.renderSearchResults(data.data.users);
            } else {
                resultsContainer.innerHTML = '<div class="tcms-error">Error searching users</div>';
            }
        })
        .catch(error => {
            console.error('Error searching users:', error);
            resultsContainer.innerHTML = '<div class="tcms-error">Error searching users</div>';
        });
    },
    
    /**
     * Render search results
     */
    renderSearchResults: function(users) {
        const resultsContainer = document.querySelector('.tcms-user-search-results');
        
        if (!resultsContainer) {
            return;
        }
        
        if (!users || users.length === 0) {
            resultsContainer.innerHTML = '<div class="tcms-search-info">No users found</div>';
            return;
        }
        
        let html = '';
        
        users.forEach(user => {
            html += `
                <div class="tcms-user-result" data-user-id="${user.user_id}">
                    <div class="tcms-user-result-avatar">
                        <img src="${user.avatar_url || this.getDefaultAvatar()}" alt="${user.display_name}">
                    </div>
                    <div class="tcms-user-result-info">
                        <div class="tcms-user-result-name">${user.display_name}</div>
                        <div class="tcms-user-result-meta">${user.city || ''}</div>
                    </div>
                </div>
            `;
        });
        
        resultsContainer.innerHTML = html;
        
        // Add click event to user results
        const userResults = resultsContainer.querySelectorAll('.tcms-user-result');
        userResults.forEach(result => {
            result.addEventListener('click', () => {
                const userId = result.getAttribute('data-user-id');
                
                // Close modal
                document.getElementById('tcms-new-conversation-modal').style.display = 'none';
                
                // Load conversation
                this.loadUserConversation(userId);
            });
        });
    },
    
    /**
     * Filter conversations
     */
    filterConversations: function(event) {
        const searchTerm = event.target.value.toLowerCase().trim();
        const conversationItems = document.querySelectorAll('.tcms-conversation-item');
        
        conversationItems.forEach(item => {
            const name = item.querySelector('.tcms-conversation-name').textContent.toLowerCase();
            
            if (name.includes(searchTerm) || searchTerm === '') {
                item.style.display = '';
            } else {
                item.style.display = 'none';
            }
        });
    },
    
    /**
     * Format time
     */
    formatTime: function(datetime) {
        if (!datetime) {
            return '';
        }
        
        const date = new Date(datetime);
        const now = new Date();
        const diffMs = now - date;
        const diffSeconds = Math.floor(diffMs / 1000);
        
        // Today
        if (date.toDateString() === now.toDateString()) {
            return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        }
        
        // Yesterday
        const yesterday = new Date(now);
        yesterday.setDate(now.getDate() - 1);
        
        if (date.toDateString() === yesterday.toDateString()) {
            return 'Yesterday';
        }
        
        // This week
        const diffDays = Math.floor(diffSeconds / 86400);
        
        if (diffDays < 7) {
            return date.toLocaleDateString([], { weekday: 'short' });
        }
        
        // Older
        return date.toLocaleDateString([], { month: 'short', day: 'numeric' });
    },
    
    /**
     * Get message preview
     */
    getMessagePreview: function(content, type) {
        if (!content) {
            return '';
        }
        
        switch (type) {
            case 'image':
                return '📷 Image';
            case 'video':
                return '🎥 Video';
            case 'audio':
                return '🎵 Audio';
            case 'location':
                return '📍 Location';
            default:
                return content.length > 30 ? content.substring(0, 30) + '...' : content;
        }
    },
    
    /**
     * Get user status
     */
    getUserStatus: function(lastActive) {
        if (!lastActive) {
            return 'offline';
        }
        
        const lastActiveDate = new Date(lastActive);
        const now = new Date();
        const diffMinutes = Math.floor((now - lastActiveDate) / (1000 * 60));
        
        if (diffMinutes < 15) {
            return 'online';
        } else if (diffMinutes < 30) {
            return 'away';
        } else {
            return 'offline';
        }
    },
    
    /**
     * Get default avatar
     */
    getDefaultAvatar: function() {
        return tcms_ajax.plugin_url + 'assets/images/default-avatar.png';
    },
    
    /**
     * Update URL parameter
     */
    updateUrlParameter: function(key, value) {
        const url = new URL(window.location.href);
        url.searchParams.set(key, value);
        window.history.replaceState({}, '', url);
    }
};