<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Chatbot & Promo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/chatbot.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: #f5f5f5;
        }
        .test-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        h2 {
            color: #1d3e1f;
        }
        button {
            background: #7fa84e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
        }
        button:hover {
            background: #1d3e1f;
        }
        #promoList {
            margin-top: 10px;
            max-height: 300px;
            overflow-y: auto;
        }
        .promo-item {
            padding: 10px;
            background: #f0fdf4;
            border: 1px solid #9bc26f;
            border-radius: 5px;
            margin: 5px 0;
        }
    </style>
</head>
<body>
    <h1>🧪 Test Chatbot & Promo System</h1>

    <div class="test-section">
        <h2>1. Test Chatbot</h2>
        <p>Chatbot icon should appear in bottom-right corner with 🌿 emoji</p>
        <button onclick="testChatbot()">Force Open Chatbot</button>
    </div>

    <div class="test-section">
        <h2>2. Test Promo List API</h2>
        <button onclick="loadPromos()">Load Promo Codes</button>
        <div id="promoList"></div>
    </div>

    <div class="test-section">
        <h2>3. Test Promo Validation</h2>
        <input type="text" id="testPromoCode" placeholder="Enter promo code" style="padding: 8px; width: 200px;">
        <button onclick="testValidatePromo()">Validate Promo</button>
        <div id="validationResult" style="margin-top: 10px;"></div>
    </div>

    <script src="assets/chatbot.js"></script>
    <script>
        function testChatbot() {
            const chatToggle = document.getElementById('chatToggle');
            const chatWindow = document.getElementById('chatWindow');
            
            if (chatToggle) {
                console.log('✅ Chatbot toggle button found');
                chatToggle.click();
            } else {
                console.error('❌ Chatbot toggle button NOT found');
                alert('Chatbot không tìm thấy! Kiểm tra console.');
            }
            
            if (chatWindow) {
                console.log('✅ Chat window found');
            } else {
                console.error('❌ Chat window NOT found');
            }
        }

        function loadPromos() {
            const container = document.getElementById('promoList');
            container.innerHTML = '<p>Loading...</p>';
            
            fetch('ajax_get_promo_list.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    console.log('Promo API response:', data);
                    
                    if (data.success && data.promos) {
                        if (data.promos.length === 0) {
                            container.innerHTML = '<p>Không có mã khuyến mãi nào</p>';
                        } else {
                            container.innerHTML = data.promos.map(promo => `
                                <div class="promo-item">
                                    <strong>${promo.code}</strong> - ${promo.name}<br>
                                    <small>${promo.discount_text} | ${promo.condition_text} | ${promo.quantity_text}</small>
                                </div>
                            `).join('');
                        }
                    } else {
                        container.innerHTML = `<p style="color: red;">Error: ${data.message || 'Unknown error'}</p>`;
                    }
                })
                .catch(error => {
                    console.error('Fetch error:', error);
                    container.innerHTML = `<p style="color: red;">Network error: ${error.message}</p>`;
                });
        }

        function testValidatePromo() {
            const code = document.getElementById('testPromoCode').value.trim();
            const result = document.getElementById('validationResult');
            
            if (!code) {
                alert('Please enter a promo code');
                return;
            }
            
            result.innerHTML = '<p>Validating...</p>';
            
            // Sample cart data
            const cartData = [
                { id: 1, quantity: 2 }
            ];
            
            const formData = new URLSearchParams();
            formData.append('promo_code', code);
            formData.append('cart_items', JSON.stringify(cartData));
            formData.append('total', '500000');
            
            fetch('ajax_validate_promo.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: formData.toString()
            })
            .then(response => response.json())
            .then(data => {
                console.log('Validation response:', data);
                
                if (data.success) {
                    result.innerHTML = `
                        <div style="color: green; padding: 10px; background: #d1fae5; border-radius: 5px;">
                            ✅ Valid!<br>
                            Discount: ${data.discount || 0} VND<br>
                            Final: ${data.final_total || 0} VND
                        </div>
                    `;
                } else {
                    result.innerHTML = `
                        <div style="color: red; padding: 10px; background: #fee; border-radius: 5px;">
                            ❌ ${data.message || 'Invalid promo'}
                        </div>
                    `;
                }
            })
            .catch(error => {
                console.error('Validation error:', error);
                result.innerHTML = `<p style="color: red;">Error: ${error.message}</p>`;
            });
        }

        // Check chatbot on load
        setTimeout(() => {
            const chatbot = document.getElementById('chatbot');
            if (chatbot) {
                console.log('✅ Chatbot loaded successfully');
            } else {
                console.error('❌ Chatbot NOT loaded');
            }
        }, 1000);
    </script>
</body>
</html>
