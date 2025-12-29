// Notification System
document.addEventListener("DOMContentLoaded", function() {
  let notifications = [];
  
  // 1. Tạo HTML với cấu trúc MỚI (Có thẻ bao notification-wrapper)
  if (!document.querySelector('.notification-bell')) {
    const bellHtml = `
      <div class="notification-wrapper" style="position: relative; display: inline-flex; align-items: center; margin-left: 10px;">
        <button class="btn btn-secondary notification-bell" id="notificationToggle">
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
                <div>Đang tải thông báo...</div>
              </div>
            </div>
        </div>
      </div>
    `;
    // 2. Chèn vào Header
    const headerActions = document.querySelector('.header-actions');
    if (headerActions) {
      const searchBtn = Array.from(headerActions.querySelectorAll('.icon-btn')).find(btn => 
        btn.querySelector('.fa-search')
      );
      
      if (searchBtn) {
        searchBtn.insertAdjacentHTML('afterend', bellHtml);
      } else {
        const firstChild = headerActions.firstElementChild;
        if (firstChild) {
          firstChild.insertAdjacentHTML('beforebegin', bellHtml);
        } else {
          headerActions.insertAdjacentHTML('afterbegin', bellHtml);
        }
      }
    }
  }
  
  // 3. Khai báo biến
  const toggle = document.getElementById('notificationToggle');
  const dropdown = document.getElementById('notificationDropdown');
  const badge = document.getElementById('notificationBadge');
  const list = document.getElementById('notificationList');
  const markAllBtn = document.getElementById('markAllRead');
  
  if (!toggle) return;
  
  // 4. Sự kiện Click
  toggle.addEventListener('click', function(e) {
    e.stopPropagation();
    dropdown.classList.toggle('show');
    
    // Log để kiểm tra
    console.log('Đã click chuông. Class list:', dropdown.classList);
    
    if (dropdown.classList.contains('show')) {
      loadNotifications();
    }
  });
  
  // Đóng khi click ra ngoài
  document.addEventListener('click', function(e) {
    // Nếu click không trúng dropdown VÀ không trúng nút chuông
    if (dropdown && !dropdown.contains(e.target) && !toggle.contains(e.target)) {
      dropdown.classList.remove('show');
    }
  });
  
  // Đánh dấu tất cả đã đọc
  if (markAllBtn) {
    markAllBtn.addEventListener('click', async function(e) {
      e.stopPropagation(); // Giữ menu mở khi bấm nút này
      try {
        // Kiểm tra xem file API có tồn tại không trước khi gọi để tránh lỗi console
        const resp = await fetch('notifications_api.php?action=mark_all_read', { method: 'POST' });
        if(resp.ok) {
            const data = await resp.json();
            if (data.success) loadNotifications();
        }
      } catch (err) {
        console.error('Mark all read error (API missing?):', err);
      }
    });
  }
  
  // Load thông báo
  async function loadNotifications() {
    try {
      const resp = await fetch('notifications_api.php?action=get_notifications');
      // Nếu file không tồn tại hoặc lỗi server
      if (!resp.ok) throw new Error("API not found or Error");
      
      const data = await resp.json();
      
      if (data.success) {
        notifications = data.notifications;
        renderNotifications();
        updateBadge(data.unread_count);
      }
    } catch (err) {
      console.warn('Chưa có API thông báo, hiển thị dữ liệu mẫu.');
      // Dữ liệu giả lập để test giao diện khi chưa có API (Giúp bạn thấy menu hoạt động)
      notifications = []; 
      renderNotifications();
    }
  }
  
  // Hiển thị danh sách
  function renderNotifications() {
    if (!notifications || notifications.length === 0) {
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
        'order_status': 'Trạng thái đơn hàng'
      };

      return `
        <div class="notification-item ${!notif.is_read ? 'unread' : ''}" data-id="${notif.id}">
          <div class="notification-type type-${notif.type}">
            ${typeLabels[notif.type] || notif.type}
          </div>
          <div class="notification-title">${escapeHtml(notif.title)}</div>
          <div class="notification-message">${escapeHtml(notif.message)}</div>
          <div class="notification-time">${formatTime(notif.created_at)}</div>
        </div>
      `;
    }).join('');
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
    if (count === undefined) count = 0;
    const bellWrapper = document.querySelector('.notification-bell');
    
    if (count > 0) {
      badge.textContent = count > 99 ? '99+' : count;
      badge.style.display = 'block';
      if (bellWrapper) bellWrapper.classList.add('has-unread');
    } else {
      badge.style.display = 'none';
      if (bellWrapper) bellWrapper.classList.remove('has-unread');
    }
  }
  
  // Helper format time
  function formatTime(dateStr) {
    const date = new Date(dateStr);
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
