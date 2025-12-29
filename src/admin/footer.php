</div><!-- End main-content -->

<script>
    const ADMIN_API_NOTI = 'api_notifications.php'; 


    function loadAdminNotifications() {
        fetch(ADMIN_API_NOTI)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.counts_by_type) {
                    updateMenuBadges(data.counts_by_type);
                }
            })
            .catch(err => console.error('Lỗi tải thông báo Admin:', err));
    }

    // Đánh dấu đã đọc khi click menu
    function markNotiTypeRead(type) {
        fetch(ADMIN_API_NOTI + '?mark_type=' + encodeURIComponent(type))
            .then(() => {
                // Ẩn badge ngay lập tức
                const map = {
                    'new_order': 'noti-order-badge',
                    'new_review': 'noti-review-badge',
                    'new_post': 'noti-post-badge'
                };
                const badgeId = map[type];
                if (badgeId) {
                    const badge = document.getElementById(badgeId);
                    if (badge) badge.style.display = 'none';
                }
            });
    }

    // Gán sự kiện click cho menu
    document.addEventListener('DOMContentLoaded', function() {
        loadAdminNotifications();
        setInterval(loadAdminNotifications, 30000);
        // Đơn hàng
        const orderMenu = document.querySelector('a[href="qldonhang.php"]');
        if (orderMenu) orderMenu.addEventListener('click', function() { markNotiTypeRead('new_order'); });
        // Bài viết
        const postMenu = document.querySelector('a[href="qlbaiviet.php"]');
        if (postMenu) postMenu.addEventListener('click', function() { markNotiTypeRead('new_post'); });
        // Đánh giá
        const reviewMenu = document.querySelector('a[href="qldanhgia.php"]');
        if (reviewMenu) reviewMenu.addEventListener('click', function() { markNotiTypeRead('new_review'); });
    });

    function updateMenuBadges(counts) {
        const map = {
            'new_order': 'noti-order-badge',
            'new_review': 'noti-review-badge',
            'new_post': 'noti-post-badge'
        };

        for (const type in map) {
            const badgeId = map[type];
            const count = counts[type] || 0; 
            const badgeElement = document.getElementById(badgeId);

            if (badgeElement) {
                if (count > 0) {
                    badgeElement.textContent = count;
                    badgeElement.style.display = 'inline-block';
                } else {
                    badgeElement.style.display = 'none';
                }
            }
        }
    }

    // Kích hoạt chạy tự động
    document.addEventListener('DOMContentLoaded', loadAdminNotifications);
    setInterval(loadAdminNotifications, 30000); 
</script>

<!-- Chatbot -->
<link rel="stylesheet" href="../assets/chatbot.css">
<link rel="stylesheet" href="../assets/notifications.css">

<script src="../assets/notifications.js" defer></script>
<script src="../assets/chatbot.js" defer></script>

</body>
</html>
