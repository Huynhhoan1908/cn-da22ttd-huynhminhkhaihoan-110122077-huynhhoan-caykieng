// Notification System
document.addEventListener("DOMContentLoaded", function() {
  let notifications = [];
  
  // Tạo UI nếu chưa có
  if (!document.querySelector('.notification-bell')) {
    const bellHtml = `
      <button class="btn btn-secondary notification-bell" id="notificationToggle" style="position:relative;">
        <i class="fas fa-bell"></i>
        <span class="notification-badge" id="notificationBadge" style="display:none;">0</span>
      </button>
      <div class="notification-dropdown" id="notificationDropdown">
          <div class="notification-header">
            <h3><i class="fas fa-bell"></i> Thông Báo</h3>
            <button class="mark-all-read" id="markAllRead">Đánh dấu đã đọc</button>
          </div>
          <div class="notification-list" id="notificationList">
            <div class="notification-empty">
              <i class="fas fa-bell-slash"></i>
              <div>Chưa có thông báo nào</div>
            </div>
          </div>
        </div>
      </div>
    `;
    
    // Tìm vị trí để chèn - sau nút search
    const headerActions = document.querySelector('.header-actions');
    if (headerActions) {
      // Tìm nút search
      const searchBtn = Array.from(headerActions.querySelectorAll('.icon-btn')).find(btn => 
        btn.querySelector('.fa-search')
      );
      
      if (searchBtn) {
        searchBtn.insertAdjacentHTML('afterend', bellHtml);
        console.log('✓ Notification bell inserted after search');
      } else {
        // Nếu không có search (như trang don_hang_cua_toi), chèn vào đầu
        const firstChild = headerActions.firstElementChild;
        if (firstChild) {
          firstChild.insertAdjacentHTML('beforebegin', bellHtml);
          console.log('✓ Notification bell inserted at beginning');
        } else {
          headerActions.insertAdjacentHTML('afterbegin', bellHtml);
        }
      }
    } else {
      console.error('❌ .header-actions not found');
      return;
    }
  }
  
  const toggle = document.getElementById('notificationToggle');
  const dropdown = document.getElementById('notificationDropdown');
  const badge = document.getElementById('notificationBadge');
  const list = document.getElementById('notificationList');
  const markAllBtn = document.getElementById('markAllRead');
  
  if (!toggle) {
    console.error('❌ Notification toggle button not found');
    return;
  }
  
  // Toggle dropdown
  toggle.addEventListener('click', function(e) {
    e.stopPropagation();
    dropdown.classList.toggle('show');
    if (dropdown.classList.contains('show')) {
      loadNotifications();
    }
  });
  
  // Đóng dropdown khi click ngoài
  document.addEventListener('click', function(e) {
    if (!dropdown.contains(e.target) && !toggle.contains(e.target)) {
      dropdown.classList.remove('show');
    }
  });
  
  // Đánh dấu tất cả đã đọc
  if (markAllBtn) {
    markAllBtn.addEventListener('click', async function() {
      try {
        const resp = await fetch('notifications_api.php?action=mark_all_read', {
          method: 'POST'
        });
        const data = await resp.json();
        if (data.success) {
          loadNotifications();
        }
      } catch (err) {
        console.error('Mark all read error:', err);
      }
    });
  }
  
  // Load thông báo
  async function loadNotifications() {
    try {
      const resp = await fetch('notifications_api.php?action=get_notifications');
      const data = await resp.json();
      
      if (data.success) {
        notifications = data.notifications;
        renderNotifications();
        updateBadge(data.unread_count);
      }
    } catch (err) {
      console.error('Load notifications error:', err);
    }
  }
  
  // Hiển thị danh sách
  function renderNotifications() {
    if (notifications.length === 0) {
      list.innerHTML = `
        <div class="notification-empty">
          <i class="fas fa-bell-slash"></i>
          <div>Chưa có thông báo nào</div>
        </div>
      `;
      return;
    }
    
    list.innerHTML = notifications.map(notif => {
      const typeLabels = {
        'new_product': 'Sản phẩm mới',
        'sale': 'Giảm giá',
        'promotion': 'Khuyến mãi',
        'announcement': 'Thông báo',
        'order_status': 'Trạng thái đơn hàng',
        'review_reply': 'Phản hồi đánh giá'
      };

      return `
        <div class="notification-item ${!notif.is_read ? 'unread' : ''}" 
             data-id="${notif.id}" 
             data-link="${notif.link || ''}">
          <div class="notification-type type-${notif.type}">
            ${typeLabels[notif.type] || notif.type}
          </div>
          <div class="notification-title">${escapeHtml(notif.title)}</div>
          <div class="notification-message">${escapeHtml(notif.message)}</div>
          <div class="notification-time">${formatTime(notif.created_at)}</div>
        </div>
      `;
    }).join('');
    
    // Add click handlers
    list.querySelectorAll('.notification-item').forEach(item => {
      item.addEventListener('click', function() {
        const id = parseInt(this.dataset.id);
        const link = this.dataset.link;
        markAsRead(id);
        if (link) {
          window.location.href = link;
        }
      });
    });
  }
  
  // Đánh dấu đã đọc
  async function markAsRead(id) {
    try {
      const formData = new FormData();
      formData.append('notification_id', id);
      
      await fetch('notifications_api.php?action=mark_read', {
        method: 'POST',
        body: formData
      });
      
      // Cập nhật UI
      const notif = notifications.find(n => n.id === id);
      if (notif) notif.is_read = true;
      
      renderNotifications();
      updateBadge();
    } catch (err) {
      console.error('Mark read error:', err);
    }
  }
  
  // Cập nhật badge
  function updateBadge(count) {
    if (count === undefined) {
      count = notifications.filter(n => !n.is_read).length;
    }
    
    const bellWrapper = document.querySelector('.notification-bell');
    
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'block';
      // Add animation class
      if (bellWrapper) bellWrapper.classList.add('has-unread');
    } else {
      badge.style.display = 'none';
      // Remove animation class
      if (bellWrapper) bellWrapper.classList.remove('has-unread');
    }
  }
  
  // Format time
  function formatTime(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000); // seconds
    
    if (diff < 60) return 'Vừa xong';
    if (diff < 3600) return Math.floor(diff / 60) + ' phút trước';
    if (diff < 86400) return Math.floor(diff / 3600) + ' giờ trước';
    if (diff < 2592000) return Math.floor(diff / 86400) + ' ngày trước';
    
    return date.toLocaleDateString('vi-VN');
  }
  
  function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }
  
  // Load lần đầu và định kỳ mỗi 30 giây
  loadNotifications();
  setInterval(loadNotifications, 30000);
});
