-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Dec 16, 2025 at 04:37 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `web_cay`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_notifications`
--

CREATE TABLE `admin_notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_notifications`
--

INSERT INTO `admin_notifications` (`id`, `type`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 'new_post', 'Bài viết mới \'9\' đang chờ duyệt.', 'admin/qlbaiviet.php?filter=pending', 1, '2025-12-12 18:46:11'),
(2, 'new_order', 'Đơn hàng mới #51 trị giá 120.000₫ từ Vàng vừa được đặt.', 'admin/qldonhang.php?order_id=51', 1, '2025-12-12 18:46:55'),
(3, 'new_review', 'Có đánh giá mới 5 sao cho sản phẩm: Tên Sản Phẩm.', 'admin/qldanhgia.php?review_id=20', 1, '2025-12-12 19:05:46'),
(4, 'new_order', 'Đơn hàng mới #52 trị giá 500.000₫ từ Huỳnh vừa được đặt.', 'admin/qldonhang.php?order_id=52', 1, '2025-12-13 02:12:04'),
(5, 'new_post', 'Bài viết mới \'123456\' đang chờ duyệt.', 'admin/qlbaiviet.php?filter=pending', 1, '2025-12-13 02:12:38'),
(6, 'new_review', 'Có đánh giá mới 5 sao cho sản phẩm: Tên Sản Phẩm.', 'admin/qldanhgia.php?review_id=21', 1, '2025-12-13 03:10:01'),
(7, 'new_review', 'Có đánh giá mới 5 sao cho sản phẩm: Tên Sản Phẩm.', 'admin/qldanhgia.php?review_id=22', 1, '2025-12-13 03:10:10'),
(8, 'new_review', 'Có đánh giá mới 5 sao cho sản phẩm: Tên Sản Phẩm.', 'admin/qldanhgia.php?review_id=23', 1, '2025-12-13 03:10:20'),
(9, 'new_order', 'Đơn hàng mới #54 trị giá 120.000₫ từ Administrator vừa được đặt.', 'admin/qldonhang.php?order_id=54', 0, '2025-12-15 10:29:10');

-- --------------------------------------------------------

--
-- Table structure for table `bai_viet`
--

CREATE TABLE `bai_viet` (
  `id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `noi_dung` text NOT NULL,
  `trang_thai` enum('pending','approved','rejected') DEFAULT 'pending',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bai_viet`
--

INSERT INTO `bai_viet` (`id`, `nguoi_dung_id`, `tieu_de`, `noi_dung`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(6, 32, 'Tạo Dựng Terrarium', 'Tóm tắt: Hướng dẫn chi tiết từng bước để tạo ra một hệ sinh thái mini hoàn chỉnh trong lọ thủy tinh (Terrarium kín và hở).\n\nNội dung chính:\n\nTerrarium là gì?: Là một hệ sinh thái thu nhỏ, tự duy trì độ ẩm (đối với Terrarium kín).\n\nCác lớp vật liệu (từ dưới lên):\n\nLớp 1 (Thoát nước): Sỏi nhẹ/Than hoạt tính.\n\nLớp 2 (Ngăn cách): Lưới hoặc Rêu Sphagnum.\n\nLớp 3 (Đất): Đất trộn xốp (tùy loại cây).\n\nLựa chọn cây:\n\nTerrarium Kín: Chọn cây ưa ẩm, phát triển chậm (Rêu, Lan Chi mini).\n\nTerrarium Hở: Chọn cây chịu hạn (Sen Đá, Xương Rồng).\n\nBảo trì: Terrarium kín chỉ cần tưới rất ít (1-2 lần/tháng). Thỉnh thoảng mở nắp để thông gió.', 'approved', '2025-12-12 07:31:30', '2025-12-12 08:36:46'),
(7, 32, 'Phân Bón Không Chỉ Là Nước: Hướng Dẫn Chi Tiết Về NPK Và Các Chất Vi Lượng', 'Mục tiêu: Giúp người đọc hiểu tại sao và khi nào cần bón phân.\r\n\r\nCấu trúc chính:\r\n\r\nI. NPK: Ba Người Khổng Lồ Quyết Định:\r\n\r\nN (Nitơ): Lợi ích cho Lá và Thân.\r\n\r\nP (Phốt pho): Lợi ích cho Rễ và Hoa/Quả.\r\n\r\nK (Kali): Lợi ích cho Sức đề kháng và Sinh trưởng tổng thể.\r\n\r\nII. Các Yếu Tố Vi Lượng Cần Thiết: Giải thích vai trò của Canxi, Magie, Lưu huỳnh.\r\n\r\nIII. Khi Nào Nên Bón Phân: Phân tích chu kỳ nghỉ (mùa đông) và chu kỳ phát triển mạnh (mùa xuân/hè).\r\n\r\nIV. Các Loại Phân Bón Phổ Biến: So sánh Phân tan chậm (Osmocote) vs Phân bón lỏng hữu cơ.', 'approved', '2025-12-12 07:33:24', '2025-12-12 07:42:38'),
(8, 32, 'Ngũ Hành Tương Sinh: Cách Chọn Cây Cảnh Đúng Mệnh Giúp Cải Vận Và Tài Lộc', 'Mục tiêu: Đưa ra các gợi ý cụ thể và đáng tin cậy cho từng Mệnh.\r\n\r\nCấu trúc chính:\r\n\r\nI. Nguyên Lý Tương Sinh Trong Phong Thủy: Giải thích ngắn gọn Mộc sinh Hỏa, Hỏa sinh Thổ, Thổ sinh Kim, Kim sinh Thủy, Thủy sinh Mộc.\r\n\r\nII. Gợi Ý Chi Tiết Theo Mệnh:\r\n\r\nMệnh Kim: Nên chọn cây có màu trắng, vàng, nâu (tương sinh Thổ). Gợi ý: Bạch Mã Hoàng Tử, Lan Hồ Điệp Trắng.\r\n\r\nMệnh Mộc: Nên chọn cây có màu xanh lá (bản mệnh) hoặc xanh dương, đen (tương sinh Thủy). Gợi ý: Trầu Bà Đế Vương Xanh, Cây Kim Ngân.\r\n\r\nMệnh Thủy: Nên chọn cây có màu trắng, bạc (tương sinh Kim). Gợi ý: Cây Lưỡi Hổ, Cây Lan Ý.\r\n\r\nMệnh Hỏa: Nên chọn cây có màu đỏ, hồng, tím (bản mệnh) hoặc xanh lá (tương sinh Mộc). Gợi ý: Cây Phú Quý, Vạn Lộc.\r\n\r\nMệnh Thổ: Nên chọn cây có màu đỏ, hồng (tương sinh Hỏa) hoặc vàng, nâu (bản mệnh). Gợi ý: Sen Đá Nâu, Cây Trầu Bà Vàng.', 'approved', '2025-12-12 07:34:28', '2025-12-12 07:42:32'),
(9, 32, 'Sống Xanh Đúng Cách: Bố Trí Cây Cảnh Tối Ưu Cho Phòng Khách, Phòng Ngủ Và Phòng Bếp', 'Mục tiêu: Hướng dẫn người đọc chọn đúng loại cây cho điều kiện ánh sáng và chức năng của từng phòng.\r\n\r\nCấu trúc chính:\r\n\r\nI. Phòng Khách (Ánh sáng & Tính thẩm mỹ): Chọn cây lớn, có dáng đẹp (Bàng Singapore, Kim Tiền) để tạo điểm nhấn. Vị trí lý tưởng: cạnh sofa hoặc cửa sổ lớn.\r\n\r\nII. Phòng Ngủ (Không khí & Thư giãn): Chọn cây nhả oxy ban đêm hoặc có mùi hương nhẹ. Cảnh báo: Tránh đặt cây quá lớn. Gợi ý: Lưỡi Hổ, Lan Chi (Cây Nhện).\r\n\r\nIII. Phòng Bếp & Phòng Tắm (Độ ẩm & Hấp thụ mùi): Chọn cây ưa ẩm và có khả năng hấp thụ mùi. Gợi ý: Bạc Hà, Hương Thảo (Phòng Bếp), Dương Xỉ, Trầu Bà (Phòng Tắm).\r\n\r\nIV. Góc Làm Việc (Giảm bức xạ & Stress): Chọn cây có màu xanh dịu mắt, hình dáng nhỏ gọn. Gợi ý: Sen Đá, Xương Rồng, Vạn Niên Thanh.', 'approved', '2025-12-12 07:35:04', '2025-12-12 07:42:26'),
(10, 32, 'Tự Trộn Đất Thần Thánh: 3 Công Thức Tuyệt Mật Cho Sen Đá, Cây Kiểng Lá và Cây Thủy Canh', 'Mục tiêu: Hướng dẫn người đọc tự tạo hỗn hợp đất tối ưu, đảm bảo thoát nước và dinh dưỡng.\r\n\r\nCấu trúc chính:\r\n\r\nI. Nguyên Tắc Cơ Bản Của Đất: Vai trò của 3 thành phần chính: Giữ ẩm (Mùn dừa, Trấu hun), Thoát nước (Đá Perlite, Đá Pumice), và Dinh dưỡng (Phân hữu cơ).\r\n\r\nII. Công Thức 1: Đất Siêu Thoát Nước cho Sen Đá & Xương Rồng:\r\n\r\nTỉ lệ khuyến nghị: 60% Khoáng chất (Pumice, Sỏi nhẹ) + 40% Hữu cơ (Mùn dừa, Đất thịt).\r\n\r\nLợi ích: Tránh thối rễ, kích thích rễ khỏe.\r\n\r\nIII. Công Thức 2: Đất Giàu Dinh Dưỡng cho Cây Kiểng Lá (Monstera, Trầu Bà):\r\n\r\nTỉ lệ khuyến nghị: 40% Mùn dừa/Trấu hun + 30% Perlite/Vỏ thông + 30% Đất thịt/Phân trùn quế.\r\n\r\nLợi ích: Giữ ẩm vừa phải, thoáng khí, cung cấp dinh dưỡng dài hạn.\r\n\r\nIV. Công Thức 3: Nước Nuôi Cây Thủy Canh (Thủy Sinh):\r\n\r\nPhân tích: Không dùng đất, chỉ dùng dung dịch thủy canh (Hydroponic solution) pha loãng.\r\n\r\nLưu ý: Thay nước định kỳ để tránh rêu mốc và bệnh cho rễ.', 'approved', '2025-12-12 07:36:31', '2025-12-12 07:42:22'),
(11, 32, 'Cấp Cứu Cây Cảnh Sắp Chết: Quy Trình 5 Bước Hồi Sinh Cây Bị Sốc Nhiệt, Vàng Lá và Thối Rễ', 'Mục tiêu: Cung cấp hướng dẫn từng bước để cứu cây khỏi những bệnh phổ biến nhất.\r\n\r\nCấu trúc chính:\r\n\r\nI. Phân Biệt Triệu Chứng:\r\n\r\nThối Rễ (Úng nước): Rễ mềm, đen, lá vàng rụng từ dưới lên.\r\n\r\nSốc Nhiệt/Thiếu Nước: Lá khô, héo rũ, đất cứng và khô.\r\n\r\nII. Cấp Cứu Thối Rễ (Bước quan trọng nhất):\r\n\r\nBước 1: Lấy cây ra khỏi chậu, cắt bỏ toàn bộ rễ thối bằng kéo đã khử trùng.\r\n\r\nBước 2: Rắc bột quế hoặc than hoạt tính lên vết cắt để sát khuẩn.\r\n\r\nBước 3: Để cây khô ráo nơi thoáng mát 1-2 ngày trước khi trồng lại vào giá thể mới, khô.\r\n\r\nIII. Hồi Phục Cây Khô Héo:\r\n\r\nĐặt chậu vào bồn nước để cây tự hấp thụ nước từ dưới lên (ngâm 30 phút).\r\n\r\nPhun sương nhẹ lên lá và chuyển cây đến nơi mát mẻ, tránh ánh nắng trực tiếp.\r\n\r\nIV. Biện pháp Phòng Ngừa: Đặt nhiệt kế/ẩm kế gần cây.', 'approved', '2025-12-12 07:37:36', '2025-12-12 07:42:14'),
(12, 32, 'Chăm Sóc Cây Cảnh Theo 4 Mùa: Điều Chỉnh Ánh Sáng Và Nước Để Vượt Qua Mùa Đông', 'Mục tiêu: Giúp người đọc thay đổi thói quen chăm sóc cây theo sự thay đổi của khí hậu.\r\n\r\nCấu trúc chính:\r\n\r\nI. Mùa Xuân (Tái sinh): Tăng cường bón phân (bón lót), tưới nước thường xuyên hơn, bắt đầu thay chậu cho cây lớn.\r\n\r\nII. Mùa Hè (Phát triển mạnh): Cần tưới nước hàng ngày hoặc cách ngày (tùy cây), nhưng phải tránh tưới vào buổi trưa (dễ gây sốc nhiệt). Che lưới cho cây đặt ngoài trời.\r\n\r\nIII. Mùa Thu (Chuyển tiếp): Giảm dần lượng phân bón. Chuẩn bị thu dọn cây vào trong nhà trước khi trời trở lạnh.\r\n\r\nIV. Mùa Đông (Nghỉ ngơi): Gần như ngưng bón phân. Giảm lượng nước tưới tối đa (nhiều cây chỉ cần tưới 1-2 lần/tháng). Đảm bảo cây không bị gió lùa trực tiếp.', 'approved', '2025-12-12 07:38:20', '2025-12-12 07:42:09'),
(13, 32, 'Cây Thủy Sinh Cho Người Lười: Hướng Dẫn Kỹ Thuật Trồng Và Duy Trì Cây Trong Môi Trường Nước', 'Mục tiêu: Cung cấp kiến thức cơ bản về cách trồng và chăm sóc cây thủy sinh sạch sẽ, ít sâu bệnh.\r\n\r\nCấu trúc chính:\r\n\r\nI. Cây Nào Phù Hợp Thủy Sinh? Gợi ý các loại cây dễ trồng: Trầu Bà, Vạn Niên Thanh, Cây Phát Lộc, Hồng Môn.\r\n\r\nII. Quy Trình Chuyển Từ Đất Sang Nước:\r\n\r\nBước 1: Rửa sạch rễ cây dưới vòi nước, loại bỏ hết đất.\r\n\r\nBước 2: Cắt bỏ các rễ đã hư hại hoặc rễ quá dài.\r\n\r\nBước 3: Đặt cây vào bình, chỉ để rễ ngập trong nước, không để thân và lá ngập.\r\n\r\nIII. Chăm Sóc & Dinh Dưỡng Thủy Sinh:\r\n\r\nThay nước: 5-7 ngày/lần.\r\n\r\nDinh dưỡng: Sử dụng dung dịch thủy canh (Phân nước chuyên dụng) theo hướng dẫn, không dùng phân bón cho cây đất.\r\n\r\nVị trí: Đặt nơi có ánh sáng tán xạ, tránh ánh nắng gắt trực tiếp làm nóng nước và phát sinh rêu.', 'approved', '2025-12-12 07:39:01', '2025-12-12 07:42:04'),
(14, 32, 'SOS! Nhận Diện và Diệt Tận Gốc 3 Kẻ Thù Số 1 của Cây Cảnh: Rệp Sáp, Nhện Đỏ và Nấm Mốc', 'Mục tiêu: Cung cấp giải pháp xử lý sâu bệnh nhanh chóng và an toàn.\r\n\r\nCấu trúc chính:\r\n\r\nI. Rệp Sáp (Mealybugs): Kẻ hút nhựa trắng xóa\r\n\r\nNhận diện: Các đốm trắng, nhìn giống bông gòn, bám dày đặc ở kẽ lá và thân.\r\n\r\nTác hại: Hút nhựa, làm cây yếu, chậm phát triển, và gây nấm mốc đen.\r\n\r\nGiải pháp: Dùng tăm bông nhúng cồn 70% để lau sạch. Hoặc pha dung dịch nước rửa chén và xịt đều toàn bộ cây (lặp lại sau 3 ngày).\r\n\r\nII. Nhện Đỏ (Spider Mites): Siêu tí hon gây cháy lá\r\n\r\nNhận diện: Lá có các chấm nhỏ li ti màu vàng/bạc, có mạng nhện mỏng manh ở mặt dưới lá. Thường xuất hiện trong điều kiện khô nóng.\r\n\r\nTác hại: Hút dịch tế bào, làm lá khô cháy, rụng hàng loạt.\r\n\r\nGiải pháp: Phun nước lạnh áp lực mạnh vào mặt dưới lá. Sử dụng dung dịch dầu Neem (pha với nước) để xịt.\r\n\r\nIII. Bệnh Nấm Mốc (Mốc Trắng, Mốc Bồ Hóng):\r\n\r\nNguyên nhân: Tưới quá nhiều nước, đất kém thoát nước hoặc độ ẩm không khí quá cao.\r\n\r\nGiải pháp: Cắt bỏ phần lá bị bệnh. Phun thuốc sát khuẩn/trừ nấm chuyên dụng. Quan trọng nhất: Giảm tưới nước và tăng cường thông thoáng khí.', 'approved', '2025-12-12 07:40:26', '2025-12-12 07:41:56'),
(15, 32, 'Loại Cây Cảnh Độc Đáo \'Làm Mưa Làm Gió\' Trong Giới Decor Thủ Công (Monstera, Cây Ăn Thịt,...)', 'Mục tiêu: Giới thiệu các loại cây có giá trị trang trí cao và thu hút người đam mê sưu tập.\r\n\r\nCấu trúc chính:\r\n\r\nI. Monstera Deliciosa (Trầu Bà Lá Xẻ):\r\n\r\nĐiểm độc đáo: Lá xẻ to, tạo hình ảnh nhiệt đới mạnh mẽ, là \"nữ hoàng\" của nội thất hiện đại.\r\n\r\nGiá trị: Dễ nhân giống, dễ chăm sóc, tạo cảm giác sang trọng.\r\n\r\nII. Cây Nắp Ấm (Pitcher Plant):\r\n\r\nĐiểm độc đáo: Là cây bắt mồi, có hình dạng cái ấm nước dùng để bẫy côn trùng.\r\n\r\nChăm sóc đặc biệt: Cần độ ẩm rất cao, nên trồng bằng rêu Sphagnum thay vì đất.\r\n\r\nIII. Cây Hoya Kerrii (Sen Đá Tim):\r\n\r\nĐiểm độc đáo: Lá hình trái tim hoàn hảo, thường được bán theo cặp vào dịp Valentine hoặc lễ tình nhân.\r\n\r\nLưu ý: Chỉ cần tưới rất ít nước, dễ bị úng nếu tưới quá nhiều.\r\n\r\nIV. Calathea Orbifolia (Cây Cầu Vồng):\r\n\r\nĐiểm độc đáo: Lá tròn lớn, có các đường vân màu xanh đậm và bạc đối xứng tuyệt đẹp.\r\n\r\nLưu ý: Cần ánh sáng gián tiếp và độ ẩm cao để lá không bị quăn mép.', 'approved', '2025-12-12 07:41:09', '2025-12-12 08:12:03'),
(16, 24, 'Bí Quyết Tưới Nước: 5 Sai Lầm Phổ Biến Nhất', 'Tóm tắt: Lỗi tưới nước là nguyên nhân hàng đầu khiến cây chết. Bài viết này chỉ ra 5 sai lầm cơ bản và nguyên tắc tưới nước cho từng loại cây (Sen đá, cây kiểng lá, v.v.).\r\n\r\nNội dung chính:\r\n\r\nTưới quá nhiều và quá thường xuyên: Nguyên nhân chính gây úng và thối rễ. Luôn kiểm tra đất bằng ngón tay trước khi tưới.\r\n\r\nTưới vào giữa trưa nắng gắt: Gây sốc nhiệt cho rễ và làm bốc hơi nhanh. Nên tưới vào sáng sớm hoặc chiều mát.\r\n\r\nKhông thoát nước: Đảm bảo chậu có lỗ thoát nước lớn và không đọng nước dưới đĩa hứng.\r\n\r\nChỉ tưới trên bề mặt: Nước không đủ thấm xuống rễ sâu. Nên tưới đẫm đến khi nước chảy ra dưới đáy chậu.\r\n\r\nSử dụng nước quá lạnh hoặc nóng: Ảnh hưởng đến hoạt động của rễ, nên dùng nước có nhiệt độ phòng.', 'approved', '2025-12-12 08:03:52', '2025-12-12 08:11:59'),
(17, 24, 'SOS! Nhận Diện và Diệt Tận Gốc 3 Kẻ Thù Số 1', 'Tóm tắt: Hướng dẫn chi tiết cách nhận biết và xử lý các loại sâu bệnh phổ biến nhất bằng phương pháp an toàn.\r\n\r\nNội dung chính:\r\n\r\nRệp Sáp (Mealybugs):\r\n\r\nNhận diện: Các đốm trắng, nhìn giống bông gòn, bám ở kẽ lá và thân.\r\n\r\nXử lý: Dùng tăm bông nhúng cồn 70% để lau sạch. Hoặc xịt dung dịch nước rửa chén pha loãng.\r\n\r\nNhện Đỏ (Spider Mites):\r\n\r\nNhận diện: Lá có chấm nhỏ li ti màu vàng/bạc, có mạng nhện mỏng manh dưới lá.\r\n\r\nXử lý: Phun nước lạnh áp lực mạnh vào mặt dưới lá. Dùng dung dịch dầu Neem.\r\n\r\nBệnh Nấm Mốc:\r\n\r\nNguyên nhân: Tưới quá nhiều nước, đất kém thoát nước.\r\n\r\nXử lý: Cắt bỏ lá bị bệnh, tăng cường thông thoáng khí và giảm tưới nước. Có thể dùng thuốc trừ nấm sinh học.', 'approved', '2025-12-12 08:05:14', '2025-12-12 08:11:54'),
(18, 24, 'Tự Trộn Đất Thần Thánh Cho Sen Đá và Cây Kiểng Lá', 'Tóm tắt: Bí quyết để cây khỏe là nằm ở chất nền. Bài viết cung cấp công thức trộn đất chuyên biệt cho 2 nhóm cây phổ biến nhất.\r\n\r\nNội dung chính:\r\n\r\nNguyên tắc cơ bản: Cân bằng giữa Giữ ẩm (Mùn dừa) và Thoát nước (Perlite, Pumice).\r\n\r\nCông thức cho Sen Đá/Xương Rồng (Siêu Thoát Nước): 60% Khoáng chất (Pumice, Sỏi nhẹ) + 40% Hữu cơ (Mùn dừa, Trấu hun). Giúp rễ không bị úng.\r\n\r\nCông thức cho Cây Kiểng Lá (Giàu Dinh Dưỡng): 40% Mùn dừa + 30% Perlite + 30% Đất thịt/Phân trùn quế. Đảm bảo giữ ẩm và thoáng khí cho rễ phát triển.', 'approved', '2025-12-12 08:05:50', '2025-12-12 08:11:41'),
(19, 24, 'Sống Xanh Đúng Cách: Bố Trí Cây Cảnh Tối Ưu Cho Phòng Khách, Ngủ, và Bếp', 'Tóm tắt: Gợi ý các loại cây phù hợp cho từng khu vực, dựa trên điều kiện ánh sáng, độ ẩm và chức năng của phòng.\r\n\r\nNội dung chính:\r\n\r\nPhòng Khách (Thẩm mỹ và Ánh sáng): Chọn cây lớn tạo điểm nhấn (Bàng Singapore, Kim Tiền). Vị trí lý tưởng: cạnh sofa.\r\n\r\nPhòng Ngủ (Không khí và Thư giãn): Chọn cây nhả oxy ban đêm (Lưỡi Hổ) hoặc cây có mùi hương nhẹ. Tránh cây quá lớn.\r\n\r\nPhòng Bếp/Phòng Tắm (Độ ẩm và Hấp thụ mùi): Chọn cây ưa ẩm và có khả năng hấp thụ mùi (Bạc Hà, Hương Thảo, Dương Xỉ).\r\n\r\nGóc Làm Việc (Giảm Stress): Chọn cây xanh dịu mắt, nhỏ gọn (Sen Đá, Xương Rồng).', 'approved', '2025-12-12 08:06:47', '2025-12-12 08:11:36'),
(20, 24, 'Phân Bón Không Chỉ Là Nước: Hướng Dẫn Chi Tiết Về NPK', 'Tóm tắt: Hướng dẫn chi tiết vai trò của 3 nguyên tố đa lượng (NPK) và các chất vi lượng, cùng với chu kỳ bón phân đúng cách.\r\n\r\nNội dung chính:\r\n\r\nNPK là gì?: Vai trò của N (Lá/Thân), P (Rễ/Hoa), K (Sức đề kháng).\r\n\r\nChu kỳ bón phân: Bón nhiều vào mùa Xuân/Hè (mùa phát triển), giảm và ngưng bón vào mùa Đông (mùa nghỉ).\r\n\r\nPhân tan chậm vs Phân lỏng: So sánh ưu nhược điểm và cách sử dụng.', 'approved', '2025-12-12 08:07:44', '2025-12-12 08:11:31'),
(21, 24, '5 Loại Cây Cảnh Độc Đáo \'Làm Mưa Làm Gió\' Trong Giới Decor', 'Tóm tắt: Giới thiệu các loại cây có hình dáng độc đáo, thu hút giới trẻ sưu tập và trang trí nhà cửa theo phong cách hiện đại.\r\n\r\nNội dung chính:\r\n\r\nMonstera Deliciosa (Trầu Bà Lá Xẻ): Vẻ đẹp nhiệt đới, lá xẻ lớn tạo điểm nhấn sang trọng.\r\n\r\nCây Nắp Ấm (Pitcher Plant): Cây bắt mồi độc đáo, cần độ ẩm cao và rêu thay vì đất.\r\n\r\nHoya Kerrii (Sen Đá Tim): Hình dáng trái tim hoàn hảo, thường dùng làm quà tặng.\r\n\r\nCalathea Orbifolia (Cây Cầu Vồng): Lá tròn lớn với các đường vân màu bạc đối xứng, cần ánh sáng gián tiếp.\r\n\r\nThường Xuân Rủ: Loại dây leo phổ biến, dễ chăm sóc, thường được đặt trên kệ cao.', 'approved', '2025-12-12 08:08:21', '2025-12-12 08:11:25'),
(22, 24, 'Chăm Sóc Cây Cảnh Theo 4 Mùa: Điều Chỉnh Ánh Sáng và Nước', 'Tóm tắt: Hướng dẫn điều chỉnh thói quen chăm sóc cây theo sự thay đổi của thời tiết và khí hậu.\r\n\r\nNội dung chính:\r\n\r\nMùa Xuân (Tái sinh): Tăng cường tưới nước và bón phân trở lại. Thay chậu cho cây lớn.\r\n\r\nMùa Hè (Phát triển mạnh): Tưới nước nhiều hơn nhưng tránh giữa trưa. Che lưới cho cây đặt ngoài trời.\r\n\r\nMùa Đông (Nghỉ ngơi): Giảm tối đa lượng nước tưới (có thể chỉ 1-2 lần/tháng). Ngừng bón phân. Đảm bảo cây ấm áp, tránh gió lùa.', 'approved', '2025-12-12 08:08:56', '2025-12-12 08:11:21'),
(23, 24, 'Cấp Cứu Cây Cảnh Sắp Chết: Quy Trình 5 Bước Hồi Sinh Cây', 'Tóm tắt: Các bước chi tiết để cứu cây khỏi tình trạng thối rễ, sốc nhiệt và vàng lá do chăm sóc sai.\r\n\r\nNội dung chính:\r\n\r\nBước 1: Chẩn đoán: Phân biệt rõ úng nước (thối rễ) và thiếu nước (héo khô).\r\n\r\nBước 2: Cấp cứu Thối Rễ: Lấy cây ra khỏi chậu, cắt bỏ rễ đen/nhũn, rắc bột quế hoặc than hoạt tính lên vết cắt. Trồng lại vào đất khô.\r\n\r\nBước 3: Hồi phục Sốc Nhiệt: Đặt cây vào nơi mát mẻ, phun sương nhẹ.', 'approved', '2025-12-12 08:09:38', '2025-12-12 08:11:17'),
(24, 24, 'Kinh Nghiệm Mua Cây Cảnh Online: Làm Sao Để Chọn Được Cây Khỏe Mạnh', 'Tóm tắt: Xây dựng niềm tin cho khách hàng khi mua sắm tại shop bạn, hướng dẫn họ cách kiểm tra sản phẩm từ xa.\r\n\r\nNội dung chính:\r\n\r\nKiểm Tra Ảnh & Video: Yêu cầu ảnh chụp cận cảnh mặt dưới lá (kiểm tra sâu bệnh) và gốc rễ (nếu có thể).\r\n\r\nThông Tin Shop: Ưu tiên shop có chính sách đổi trả, đóng gói và bảo hành rõ ràng.\r\n\r\nĐánh giá Tình Trạng Cây: Lá phải xanh tươi, không có đốm lạ. Rễ phải có màu trắng hoặc vàng nhạt, không được đen.\r\n\r\nQuy Trình Nhận Hàng: Khuyến nghị khách hàng quay video mở hộp để làm bằng chứng khiếu nại (nếu cần).', 'approved', '2025-12-12 08:10:19', '2025-12-12 08:11:11'),
(25, 33, 'Nhân Giống Cây - Bí quyết làm vườn nhân đôi', 'Tóm tắt: Hướng dẫn 3 phương pháp nhân giống phổ biến (giâm cành, chiết cành, gieo hạt) mà bất kỳ ai cũng có thể làm tại nhà.\r\n\r\nNội dung chính:\r\n\r\nGiâm Cành (Cutting): Phương pháp dễ nhất, áp dụng cho Trầu Bà, Lưỡi Hổ, Sen Đá. Chỉ cần cắt một đoạn thân hoặc lá, ngâm trong nước hoặc cắm vào đất ẩm.\r\n\r\nChiết Cành (Air Layering): Dùng cho cây thân gỗ (như Mai, Quất, Vải) để tạo ra cây con mang đặc tính giống hệt cây mẹ. Quy trình: Tạo vết khoanh vỏ -> Bọc bầu đất/mùn dừa -> Cắt sau khi rễ mọc.\r\n\r\nGieo Hạt: Thường dùng cho các loại cây ăn quả hoặc rau. Yêu cầu ngâm hạt trong nước ấm trước khi gieo để kích thích nảy mầm.\r\n\r\nLời khuyên: Dùng thuốc kích rễ B1 hoặc dung dịch Atonik để tăng tỷ lệ thành công.', 'approved', '2025-12-12 08:15:27', '2025-12-12 08:24:37'),
(26, 33, 'Ánh Sáng Nhân Tạo - Bí mật của cây nội thất', 'Tóm tắt: Phân tích vai trò của ánh sáng nhân tạo và cách chọn đèn LED quang phổ (Full Spectrum) để cây trong nhà vẫn quang hợp khỏe mạnh.\r\n\r\nNội dung chính:\r\n\r\nTại sao cần đèn? Cây nội thất cách xa cửa sổ 1-2m đã không đủ ánh sáng để quang hợp. Đèn giúp bù đắp phổ ánh sáng cần thiết.\r\n\r\nPhổ ánh sáng nào quan trọng?\r\n\r\nMàu Xanh Lam (Blue): Tốt cho sự phát triển của lá và thân (quang hợp).\r\n\r\nMàu Đỏ (Red): Kích thích ra hoa, đậu quả và phát triển rễ.\r\n\r\nFull Spectrum: Loại đèn tốt nhất, mô phỏng ánh sáng mặt trời tự nhiên.\r\n\r\nVị trí và thời gian: Đèn nên đặt cách ngọn cây 30-50cm. Thời gian chiếu sáng lý tưởng: 8-12 giờ/ngày.', 'approved', '2025-12-12 08:15:59', '2025-12-12 08:24:42'),
(27, 33, 'Tận Dụng Phế Phẩm - Biến Vỏ Trứng, Vỏ Chuối thành phân bón', 'Tóm tắt: Hướng dẫn cách tái chế các vật liệu nhà bếp thành phân bón hữu cơ, cung cấp Kali và Canxi cho cây trồng.\r\n\r\nNội dung chính:\r\n\r\nVỏ Trứng (Nguồn Canxi):\r\n\r\nLợi ích: Cung cấp Canxi giúp cứng cáp, tránh thối ngọn và làm giá thể thoát nước tốt hơn.\r\n\r\nCách làm: Rửa sạch, phơi khô, nghiền thành bột mịn rồi trộn trực tiếp vào đất hoặc rắc lên bề mặt.\r\n\r\nVỏ Chuối (Nguồn Kali):\r\n\r\nLợi ích: Cung cấp Kali dồi dào, giúp cây ra hoa, ra quả và tăng sức đề kháng.\r\n\r\nCách làm: Cắt nhỏ vỏ chuối, phơi khô rồi chôn trực tiếp vào gốc cây hoặc ngâm vỏ chuối vào nước làm phân bón lỏng (tưới sau 1 tuần).\r\n\r\nBã Cà Phê (Nitơ và Độ thoáng):\r\n\r\nLợi ích: Bổ sung Nitơ nhẹ và cải tạo độ tơi xốp của đất.\r\n\r\nLưu ý: Chỉ dùng một lượng nhỏ để tránh làm chua đất.', 'approved', '2025-12-12 08:16:41', '2025-12-12 08:24:46'),
(28, 33, 'Vượt Qua Khói Bụi: Top 5 Loại Cây Lọc Khí Độc NASA Khuyến Nghị', 'Tóm tắt: Giới thiệu 5 loại cây đã được nghiên cứu khoa học chứng minh khả năng hấp thụ các hóa chất độc hại phổ biến trong nhà.\r\n\r\nNội dung chính:\r\n\r\nNghiên cứu của NASA: Các nhà khoa học đã chứng minh cây cảnh có khả năng lọc bỏ Formaldehyde, Benzene, Xylene và Carbon Monoxide.\r\n\r\nTop 5 Cây Vàng:\r\n\r\nLưỡi Hổ (Sansevieria): Lọc Formaldehyde và nhả oxy vào ban đêm (lý tưởng cho phòng ngủ).\r\n\r\nLan Ý (Peace Lily): Loại bỏ ba chất độc phổ biến nhất (Formaldehyd, Benzen, Trichloroethylene).\r\n\r\nCây Nhện (Spider Plant): Siêu dễ trồng, hiệu quả cao trong việc loại bỏ Carbon Monoxide.\r\n\r\nCây Thường Xuân (English Ivy): Tuyệt vời để lọc Formaldehyde từ khói thuốc lá và đồ nội thất.', 'approved', '2025-12-12 08:17:14', '2025-12-12 08:24:50'),
(29, 33, 'Nguyên Tắc Bố Cục 3 Tầng: Sắp xếp cây cảnh tăng tính thẩm mỹ', 'Tóm tắt: Hướng dẫn kỹ thuật bố cục chuyên nghiệp, giúp góc xanh của bạn có chiều sâu, hài hòa và không bị rối mắt.\r\n\r\nNội dung chính:\r\n\r\nLý thuyết Bố cục 3 Tầng:\r\n\r\nTầng 1 (Cao): Cây có chiều cao (Bàng Singapore, Lưỡi Hổ cao) đặt ở phía sau hoặc góc phòng, tạo nền (Background).\r\n\r\nTầng 2 (Trung bình): Cây có kích thước vừa (Trầu Bà lá xẻ, Hồng Môn) đặt ở giữa, tạo sự chuyển tiếp.\r\n\r\nTầng 3 (Thấp/Rủ): Cây nhỏ (Sen Đá, Rêu) hoặc cây dây rủ (Trầu Bà) đặt ở phía trước hoặc trên kệ cao, tạo điểm nhấn (Foreground).\r\n\r\nNguyên tắc Phối Hợp: Đặt cây có lá đậm màu ra phía sau và cây có lá sáng màu ra phía trước. Tránh đặt các cây có hình dạng/chiều cao quá giống nhau cạnh nhau.', 'approved', '2025-12-12 08:17:45', '2025-12-12 08:24:57'),
(30, 33, 'Món Ăn Khó Tính: Hướng Dẫn Chăm Sóc Cây Bonsai và Cây Ăn Thịt', 'Tóm tắt: Hướng dẫn chuyên sâu về 2 loại cây cần chế độ chăm sóc và môi trường đặc biệt, khác biệt hoàn toàn với cây thông thường.\r\n\r\nNội dung chính:\r\n\r\nCây Bonsai:\r\n\r\nĐất trồng: Yêu cầu đất hạt nhỏ, thoát nước cực tốt (như đất Akadama).\r\n\r\nTưới nước: Tưới hàng ngày nhưng phải tưới bằng vòi phun sương, không tưới đẫm như cây thường.\r\n\r\nCắt tỉa: Cần tỉa cành định kỳ để giữ dáng.\r\n\r\nCây Ăn Thịt (Venus Flytrap, Nắp Ấm):\r\n\r\nĐất trồng: Phải dùng rêu than bùn (Peat moss) hoặc rêu Sphagnum, không dùng đất thông thường (sẽ làm cây chết do quá nhiều chất dinh dưỡng).\r\n\r\nNước: Chỉ dùng nước mưa hoặc nước cất, tránh nước máy (có khoáng chất sẽ làm cây bị sốc).', 'approved', '2025-12-12 08:18:22', '2025-12-12 08:25:01'),
(31, 33, 'Văn Hóa Cây Tết: Ý Nghĩa Thâm Sâu Của Cây Mai, Đào, Quất', 'Tóm tắt: Phân tích ý nghĩa phong thủy và văn hóa của ba loại cây cảnh không thể thiếu trong dịp Tết Nguyên Đán tại Việt Nam.\r\n\r\nNội dung chính:\r\n\r\nCây Mai Vàng (Miền Nam): Tượng trưng cho sự giàu sang, phú quý, hy vọng về một năm mới sung túc, phát tài.\r\n\r\nCây Đào (Miền Bắc): Tượng trưng cho sự sinh sôi, nảy nở, xua đuổi tà khí và mang lại may mắn, bình an.\r\n\r\nCây Quất: Tượng trưng cho sự đoàn viên, viên mãn, quả chín vàng tượng trưng cho lộc tài dồi dào.', 'approved', '2025-12-12 08:18:57', '2025-12-12 08:25:13'),
(32, 33, 'Thủy Canh Cao Cấp: Kỹ Thuật Nuôi Dưỡng Rễ Trắng', 'Tóm tắt: Đi sâu vào kỹ thuật trồng thủy canh: cách pha dung dịch dinh dưỡng NPK chuyên dụng và cách giữ rễ luôn trắng, không bị đóng cặn hay chuyển sang màu nâu.\r\n\r\nNội dung chính:\r\n\r\nTầm quan trọng của pH và TDS: Kiểm tra nồng độ pH của nước (nên giữ ở mức trung tính 6.0-6.5) và tổng chất rắn hòa tan (TDS) của dung dịch dinh dưỡng.\r\n\r\nKỹ thuật giữ rễ trắng: Thay nước định kỳ (5-7 ngày/lần), sử dụng bình thủy tinh trong suốt để dễ quan sát. Tránh để nước bị nóng.\r\n\r\nKhắc phục rêu xanh: Rêu xuất hiện do ánh sáng quá nhiều. Dùng bình màu tối hoặc che bớt rễ khỏi ánh sáng.', 'approved', '2025-12-12 08:19:25', '2025-12-12 08:25:08'),
(33, 33, 'Lựa Chọn Chậu Cây: So Sánh Ưu Nhược Điểm', 'Tóm tắt: Phân tích ưu và nhược điểm của các loại chậu phổ biến (Gốm, Nhựa, Xi măng) để giúp khách hàng chọn chậu phù hợp với nhu cầu thẩm mỹ và kỹ thuật chăm sóc.\r\n\r\nNội dung chính:\r\n\r\nChậu Gốm/Đất Nung:\r\n\r\nƯu điểm: Thẩm mỹ cao, thoáng khí, thấm nước tốt.\r\n\r\nNhược điểm: Dễ vỡ, nặng, dễ làm cây bị khô nhanh.\r\n\r\nChậu Nhựa:\r\n\r\nƯu điểm: Nhẹ, bền, giá rẻ, giữ ẩm tốt (tốt cho cây ưa ẩm).\r\n\r\nNhược điểm: Kém thoáng khí (dễ gây úng nếu tưới nhiều), dễ bị bạc màu dưới nắng.\r\n\r\nChậu Xi Măng/Composite:\r\n\r\nƯu điểm: Sang trọng, phù hợp với cây lớn, rất bền.\r\n\r\nNhược điểm: Rất nặng, ít thoát nước (cần đục thêm lỗ).', 'approved', '2025-12-12 08:20:03', '2025-12-12 08:26:24'),
(34, 33, 'Vệ Sinh Dụng Cụ Trồng Cây: Quy Trình Khử Trùng Đất, Chậu và Kéo Cắt', 'Tóm tắt: Hướng dẫn các bước khử trùng đơn giản nhưng cực kỳ quan trọng để ngăn chặn sự lây lan của nấm mốc và sâu bệnh từ cây này sang cây khác.\r\n\r\nNội dung chính:\r\n\r\nKhử trùng Đất cũ: Không nên tái sử dụng đất trực tiếp. Phơi đất dưới nắng gắt hoặc làm nóng đất để tiêu diệt mầm bệnh.\r\n\r\nKhử trùng Dụng cụ (Kéo, Dao): Luôn khử trùng kéo bằng cồn 70% trước và sau khi cắt tỉa bất kỳ cây nào để ngăn lây lan bệnh.\r\n\r\nKhử trùng Chậu cũ: Rửa sạch chậu bằng xà phòng và nước nóng, sau đó ngâm chậu trong dung dịch thuốc tẩy pha loãng để diệt nấm mốc.', 'approved', '2025-12-12 08:20:35', '2025-12-12 08:26:03'),
(35, 24, 'Tưới Tự Động Thông Minh', 'Tóm tắt: Hướng dẫn lắp đặt hệ thống tưới nhỏ giọt tự động đơn giản, giúp bạn tiết kiệm thời gian và đảm bảo cây luôn đủ nước, đặc biệt khi đi du lịch dài ngày.\r\n\r\nNội dung chính:\r\n\r\nLợi ích của hệ thống nhỏ giọt: Tiết kiệm nước (tưới trực tiếp vào gốc), đảm bảo độ ẩm ổn định, và giải phóng thời gian cho người trồng.\r\n\r\nCác thành phần cần thiết: Bơm mini, hẹn giờ (Timer), ống dẫn (Micro tube), và đầu nhỏ giọt (Dripper).\r\n\r\nQuy trình lắp đặt đơn giản: Đặt Timer tại nguồn nước -> Kết nối ống dẫn chính -> Phân nhánh ống nhỏ giọt đến từng chậu -> Cố định và cài đặt thời gian tưới.\r\n\r\nCài đặt thời gian: Tưới 2-3 lần/ngày trong mùa hè (tổng cộng 15-20 phút), giảm còn 1 lần/ngày vào mùa mát.', 'approved', '2025-12-12 08:21:44', '2025-12-12 08:26:19'),
(36, 24, 'Mùa Lễ Hội - Cây cảnh theo sự kiện', 'Tóm tắt: Gợi ý các loại cây cảnh mang ý nghĩa tốt lành, phù hợp để làm quà tặng trong các dịp lễ, khai trương hoặc tân gia.\r\n\r\nNội dung chính:\r\n\r\nTặng Khai Trương/Tân Gia: Chọn cây mang ý nghĩa phát tài, vững vàng (Cây Kim Tiền, Cây Phát Lộc/Tre Phú Quý, Cây Trúc Nhật).\r\n\r\nTặng Lễ Tình Nhân/8/3: Chọn cây có hình dáng đặc biệt và hoa đẹp (Sen Đá Tim, Cây Hồng Môn Đỏ/Hồng, Cây Lan Hồ Điệp).\r\n\r\nTặng Sinh Nhật: Chọn cây hợp mệnh hoặc cây dễ chăm sóc, tượng trưng cho sức sống (Cây Lưỡi Hổ, Cây Trường Sinh).', 'approved', '2025-12-12 08:22:13', '2025-12-12 08:25:26'),
(37, 32, 'An Toàn Cho Thú Cưng', 'Tóm tắt: Danh sách các loại cây cảnh phổ biến nhưng có thể gây ngộ độc cho chó mèo khi chúng nhai phải. Gợi ý 5 loại cây hoàn toàn an toàn để thay thế.\r\n\r\nNội dung chính:\r\n\r\nDanh Sách Độc Hại Cần Tránh: Tránh xa Vạn Niên Thanh, Lan Ý (gây kích ứng đường tiêu hóa), Cây Thiên Điểu (có thể gây nôn mửa).\r\n\r\n5 Lựa Chọn Thay Thế An Toàn (Non-toxic):\r\n\r\nCây Nhện (Lan Chi): Hấp thụ độc tố trong không khí, hoàn toàn an toàn.\r\n\r\nCây Phát Tài (Money Tree): An toàn và mang ý nghĩa phong thủy tốt.\r\n\r\nCây Lưỡi Hổ: An toàn, lọc khí độc hiệu quả.\r\n\r\nCác loại thảo mộc: Hương Thảo, Bạc Hà (thú cưng có thể ăn được).\r\n\r\nBiện pháp phòng ngừa: Đặt cây độc ở nơi thú cưng không thể tiếp cận (treo lên cao hoặc trong lồng kính).', 'approved', '2025-12-12 08:23:11', '2025-12-12 08:25:22'),
(38, 32, 'Nghệ Thuật Cắt Tỉa và Tạo Dáng', 'Tóm tắt: Hướng dẫn kỹ thuật cắt tỉa đúng cách để kích thích cây ra nhánh mới, giữ cây gọn gàng và tạo ra hình dáng đẹp theo thời gian.\r\n\r\nNội dung chính:\r\n\r\nMục đích của cắt tỉa: Loại bỏ cành lá úa vàng, kích thích đâm chồi, và tạo hình nghệ thuật.\r\n\r\nNguyên tắc cắt tỉa cơ bản:\r\n\r\nCắt trên mắt ngủ: Luôn cắt phía trên một mắt ngủ (chồi non) để kích thích cây mọc nhánh mới từ đó.\r\n\r\nKhử trùng dụng cụ: Luôn dùng kéo sắc và khử trùng bằng cồn trước khi cắt.\r\n\r\nThời điểm: Nên cắt tỉa mạnh vào đầu mùa xuân (mùa phát triển).\r\n\r\nTạo dáng Bonsai/Cây leo: Hướng dẫn cách uốn cành bằng dây đồng hoặc dùng cọc rêu để cây leo phát triển theo chiều cao.', 'approved', '2025-12-12 08:23:48', '2025-12-12 08:26:08'),
(44, 34, '123456', '789456', 'rejected', '2025-12-13 02:12:38', '2025-12-13 03:08:58');

-- --------------------------------------------------------

--
-- Table structure for table `bai_viet_yeuthich`
--

CREATE TABLE `bai_viet_yeuthich` (
  `id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `bai_viet_id` int(11) NOT NULL,
  `ngay_yeu_thich` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `bai_viet_yeuthich`
--

INSERT INTO `bai_viet_yeuthich` (`id`, `nguoi_dung_id`, `bai_viet_id`, `ngay_yeu_thich`) VALUES
(3, 28, 14, '2025-12-12 07:43:37'),
(4, 34, 37, '2025-12-13 02:16:24'),
(6, 24, 38, '2025-12-13 17:23:13');

-- --------------------------------------------------------

--
-- Table structure for table `binh_luan_bai_viet`
--

CREATE TABLE `binh_luan_bai_viet` (
  `id` int(11) NOT NULL,
  `bai_viet_id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) NOT NULL,
  `noi_dung` text NOT NULL,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `binh_luan_bai_viet`
--

INSERT INTO `binh_luan_bai_viet` (`id`, `bai_viet_id`, `nguoi_dung_id`, `noi_dung`, `ngay_tao`) VALUES
(3, 38, 28, 'hay quá', '2025-12-12 08:31:28'),
(4, 38, 24, 'quá hay', '2025-12-13 17:23:33');

-- --------------------------------------------------------

--
-- Table structure for table `chat_messages`
--

CREATE TABLE `chat_messages` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `message` text NOT NULL,
  `is_from_admin` tinyint(1) DEFAULT 0 COMMENT '0=Khách, 1=Admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `is_read` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_messages`
--

INSERT INTO `chat_messages` (`id`, `session_id`, `message`, `is_from_admin`, `created_at`, `is_read`) VALUES
(1, 'chat_69398353ea8740.79376178', 'hi', 0, '2025-12-11 03:15:35', 1),
(2, 'chat_69398353ea8740.79376178', 'chào', 0, '2025-12-11 03:41:49', 1),
(3, 'guest_693a3d91e1a52', 'chào', 0, '2025-12-11 03:42:30', 1),
(4, 'guest_693a3d91e1a52', 'hi', 0, '2025-12-11 03:50:57', 1),
(5, 'user_24', 'g', 0, '2025-12-11 03:56:00', 1),
(6, 'user_24', 'n', 1, '2025-12-11 03:56:24', 0),
(7, 'user_24', 'm', 0, '2025-12-11 04:00:36', 1),
(8, 'user_24', 'cin chào', 0, '2025-12-11 04:01:11', 1),
(9, 'user_28', 'hi', 0, '2025-12-11 10:54:22', 1),
(10, 'user_28', '2', 0, '2025-12-11 11:08:43', 1),
(11, 'user_28', 'chào', 0, '2025-12-12 14:30:37', 1),
(12, 'user_34', 'hi shop', 0, '2025-12-13 02:11:09', 1),
(13, 'user_34', 'có vấn đề gì không ạ', 1, '2025-12-13 03:08:41', 0);

-- --------------------------------------------------------

--
-- Table structure for table `chat_sessions`
--

CREATE TABLE `chat_sessions` (
  `id` int(11) NOT NULL,
  `session_id` varchar(100) NOT NULL,
  `customer_name` varchar(100) DEFAULT 'Khách',
  `last_message` text DEFAULT NULL,
  `last_message_time` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `unread_count` int(11) DEFAULT 0 COMMENT 'Số tin admin chưa đọc',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chat_sessions`
--

INSERT INTO `chat_sessions` (`id`, `session_id`, `customer_name`, `last_message`, `last_message_time`, `unread_count`, `created_at`) VALUES
(1, 'chat_69398353ea8740.79376178', 'Khách 6178', 'chào', '2025-12-11 03:41:51', 0, '2025-12-11 03:15:35'),
(2, 'guest_693a3d91e1a52', 'Khách 1a52', 'hi', '2025-12-11 03:51:05', 0, '2025-12-11 03:42:30'),
(3, 'user_24', 'Huỳnh Minh Khải Hoàn', 'cin chào', '2025-12-11 04:01:14', 0, '2025-12-11 03:56:01'),
(4, 'user_28', 'Administrator', 'chào', '2025-12-12 19:15:10', 0, '2025-12-11 10:54:22'),
(5, 'user_34', 'Huỳnh', 'Bạn: có vấn đề gì không ạ', '2025-12-13 03:08:41', 0, '2025-12-13 02:11:09');

-- --------------------------------------------------------

--
-- Table structure for table `chi_tiet_don_hang`
--

CREATE TABLE `chi_tiet_don_hang` (
  `id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `san_pham_id` int(11) NOT NULL,
  `ten_san_pham` varchar(255) NOT NULL,
  `gia` decimal(15,2) NOT NULL,
  `so_luong` int(11) DEFAULT 1,
  `thanh_tien` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `chi_tiet_don_hang`
--

INSERT INTO `chi_tiet_don_hang` (`id`, `don_hang_id`, `san_pham_id`, `ten_san_pham`, `gia`, `so_luong`, `thanh_tien`) VALUES
(30, 26, 149, '', 85000.00, 1, 85000.00),
(32, 26, 147, '', 85000.00, 1, 85000.00),
(33, 26, 146, '', 70000.00, 1, 70000.00),
(34, 26, 145, '', 120000.00, 1, 120000.00),
(35, 27, 157, '', 70000.00, 3, 210000.00),
(37, 27, 155, '', 100000.00, 1, 100000.00),
(38, 27, 154, '', 130000.00, 1, 130000.00),
(39, 28, 157, '', 70000.00, 1, 70000.00),
(40, 28, 156, '', 90000.00, 1, 90000.00),
(41, 28, 154, '', 130000.00, 1, 130000.00),
(42, 28, 155, '', 100000.00, 1, 100000.00),
(43, 29, 120, 'Cây Tùng La Hán', 550000.00, 1, 550000.00),
(44, 30, 157, '', 70000.00, 1, 70000.00),
(45, 30, 147, '', 85000.00, 1, 85000.00),
(46, 30, 146, '', 70000.00, 1, 70000.00),
(47, 30, 155, '', 100000.00, 1, 100000.00),
(48, 31, 134, '', 120000.00, 2, 240000.00),
(49, 31, 133, '', 280000.00, 1, 280000.00),
(50, 32, 121, '', 500000.00, 3, 1500000.00),
(51, 33, 139, '', 140000.00, 5, 700000.00),
(52, 34, 157, '', 70000.00, 7, 490000.00),
(53, 35, 154, '', 130000.00, 4, 520000.00),
(54, 36, 157, '', 70000.00, 10, 700000.00),
(56, 37, 154, '', 130000.00, 3, 390000.00),
(57, 38, 154, '', 130000.00, 3, 390000.00),
(58, 39, 151, 'Cây Nha Đam (Lô Hội)', 50000.00, 20, 1000000.00),
(59, 40, 150, '', 140000.00, 5, 700000.00),
(60, 40, 151, '', 50000.00, 6, 300000.00),
(61, 41, 157, 'Cây Tiên Ông', 70000.00, 1, 70000.00),
(62, 42, 154, 'Cây Hồng Môn', 130000.00, 3, 390000.00),
(63, 43, 156, 'Cây Trúc Phú Quý', 90000.00, 1, 90000.00),
(64, 44, 155, 'Cây Trạng Nguyên', 100000.00, 1, 100000.00),
(66, 46, 155, 'Cây Trạng Nguyên', 100000.00, 1, 100000.00),
(67, 47, 154, 'Cây Hồng Môn', 130000.00, 1, 130000.00),
(68, 48, 156, 'Cây Trúc Phú Quý', 90000.00, 1, 90000.00),
(69, 49, 153, 'Cây Trúc Nhật Vàng', 110000.00, 4, 440000.00),
(70, 50, 150, 'Cây Ngũ Gia Bì', 140000.00, 1, 140000.00),
(71, 51, 148, 'Cây Lưỡi Hổ Viền Vàng', 90000.00, 1, 90000.00),
(72, 52, 143, 'Cây Cọ Nhật', 190000.00, 3, 570000.00),
(73, 53, 153, '', 110000.00, 3, 330000.00),
(74, 53, 151, '', 50000.00, 1, 50000.00),
(75, 53, 147, '', 85000.00, 1, 85000.00),
(76, 54, 156, 'Cây Trúc Phú Quý', 90000.00, 1, 90000.00);

-- --------------------------------------------------------

--
-- Table structure for table `danh_gia`
--

CREATE TABLE `danh_gia` (
  `id` int(11) NOT NULL,
  `san_pham_id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `user_name` varchar(150) NOT NULL,
  `rating` tinyint(4) NOT NULL DEFAULT 5,
  `comment` text NOT NULL,
  `admin_reply` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `danh_gia`
--

INSERT INTO `danh_gia` (`id`, `san_pham_id`, `user_id`, `user_email`, `user_name`, `rating`, `comment`, `admin_reply`, `created_at`) VALUES
(14, 108, 28, NULL, 'admin', 4, 'hnb', NULL, '2025-12-05 21:20:08'),
(15, 109, 28, NULL, 'admin', 5, 'jfbkejw', 'ok', '2025-12-05 21:22:51'),
(16, 157, 28, NULL, 'admin', 5, 'đẹp quá trời', NULL, '2025-12-08 12:37:07'),
(17, 160, 28, NULL, 'admin', 4, 'cũng đi', NULL, '2025-12-08 12:37:28'),
(18, 155, 28, NULL, 'admin', 3, 'ok đó', NULL, '2025-12-08 12:37:43'),
(19, 154, 28, NULL, 'admin', 2, 'xấu', NULL, '2025-12-08 12:37:55'),
(20, 148, 33, NULL, 'Vàng', 4, 'cũng cũng đi', NULL, '2025-12-13 02:05:46'),
(21, 153, 34, NULL, 'Huỳnh', 4, '1', NULL, '2025-12-13 10:10:01'),
(22, 151, 34, NULL, 'Huỳnh', 5, '3', NULL, '2025-12-13 10:10:10'),
(23, 147, 34, NULL, 'Huỳnh', 5, '9', '3', '2025-12-13 10:10:20');

-- --------------------------------------------------------

--
-- Table structure for table `danh_muc`
--

CREATE TABLE `danh_muc` (
  `id` int(11) NOT NULL,
  `ten_san_pham` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `mo_ta` varchar(255) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `nhom_danh_muc` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `danh_muc`
--

INSERT INTO `danh_muc` (`id`, `ten_san_pham`, `mo_ta`, `nhom_danh_muc`) VALUES
(1, 'Cây phong thuỷ', 'Áo', NULL),
(5, 'Cây Cao & Lớn', NULL, 'kieu_dang'),
(6, 'Cây Cảnh Mini', NULL, 'kieu_dang'),
(7, 'Cây Treo Trong Nhà', NULL, 'kieu_dang'),
(8, 'Cây Nhiệt Đới', NULL, 'kieu_dang'),
(9, 'Cây Kiểng Lá', NULL, 'kieu_dang'),
(10, 'Cây Cảnh Để Bàn', NULL, 'vi_tri'),
(11, 'Cây Cảnh Văn Phòng', NULL, 'vi_tri'),
(12, 'Cây Trong Bếp & Nhà Tắm', NULL, 'vi_tri'),
(13, 'Cây Trước Cửa & Hành Lang', NULL, 'vi_tri'),
(14, 'Cây Trồng Ban Công', NULL, 'vi_tri'),
(15, 'Cây Lọc Không Khí', NULL, 'chuc_nang'),
(16, 'Cây Dễ Trồng', NULL, 'chuc_nang'),
(17, 'Cây Cần Ít Ánh Sáng', NULL, 'chuc_nang'),
(18, 'Cây Thủy Sinh', NULL, 'chuc_nang'),
(19, 'Sen đá', NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `don_hang`
--

CREATE TABLE `don_hang` (
  `id` int(11) NOT NULL,
  `ma_don_hang` varchar(50) NOT NULL,
  `nguoi_dung_id` int(11) DEFAULT NULL,
  `ten_khach_hang` varchar(255) NOT NULL,
  `so_dien_thoai` varchar(20) NOT NULL,
  `email` varchar(255) DEFAULT NULL,
  `dia_chi` text DEFAULT NULL,
  `thanh_pho` varchar(100) DEFAULT NULL,
  `phuong_thuc_thanh_toan` varchar(50) DEFAULT 'COD',
  `ghi_chu` text DEFAULT NULL,
  `tong_tien` decimal(15,2) DEFAULT 0.00,
  `phi_van_chuyen` decimal(15,2) DEFAULT 0.00,
  `tong_thanh_toan` decimal(15,2) DEFAULT 0.00,
  `trang_thai` varchar(50) DEFAULT 'Chờ xác nhận',
  `ngay_dat` datetime DEFAULT current_timestamp(),
  `ngay_cap_nhat` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `ma_khuyen_mai` varchar(50) DEFAULT NULL COMMENT 'Mã khuyến mãi đã sử dụng',
  `giam_gia` decimal(10,2) DEFAULT 0.00 COMMENT 'Số tiền giảm giá'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `don_hang`
--

INSERT INTO `don_hang` (`id`, `ma_don_hang`, `nguoi_dung_id`, `ten_khach_hang`, `so_dien_thoai`, `email`, `dia_chi`, `thanh_pho`, `phuong_thuc_thanh_toan`, `ghi_chu`, `tong_tien`, `phi_van_chuyen`, `tong_thanh_toan`, `trang_thai`, `ngay_dat`, `ngay_cap_nhat`, `ma_khuyen_mai`, `giam_gia`) VALUES
(25, 'DH20251205150855795', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 300000.00, 30000.00, 330000.00, 'Đã giao', '2025-12-05 21:08:55', '2025-12-05 21:15:20', NULL, 0.00),
(26, 'DH20251207120642688', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 500000.00, 0.00, 500000.00, 'Đã giao', '2025-12-07 18:06:42', '2025-12-07 18:07:03', NULL, 0.00),
(27, 'DH20251208050002885', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 580000.00, 0.00, 580000.00, 'Đã giao', '2025-12-08 11:00:02', '2025-12-08 12:36:27', NULL, 0.00),
(28, 'DH20251210121139841', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'bank_transfer', '', 390000.00, 30000.00, 290000.00, 'Chờ xác nhận', '2025-12-10 18:11:39', '2025-12-10 18:11:39', '12', 100000.00),
(29, 'DH20251210123352668', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 550000.00, 30000.00, 480000.00, 'Chờ xác nhận', '2025-12-10 18:33:52', '2025-12-10 18:33:52', '12', 100000.00),
(30, 'DH20251210124436413', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 325000.00, 30000.00, 225000.00, 'Chờ xác nhận', '2025-12-10 18:44:36', '2025-12-10 18:44:36', '12', 100000.00),
(31, 'DH20251210124849635', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 520000.00, 0.00, 420000.00, 'Chờ xác nhận', '2025-12-10 18:48:49', '2025-12-10 18:48:49', '12', 100000.00),
(32, 'DH20251210125221991', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 1500000.00, 0.00, 1400000.00, 'Chờ xác nhận', '2025-12-10 18:52:21', '2025-12-10 18:52:21', '12', 100000.00),
(33, 'DH20251210125427463', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 700000.00, 0.00, 600000.00, 'Chờ xác nhận', '2025-12-10 18:54:27', '2025-12-10 18:54:27', '12', 100000.00),
(34, 'DH20251210125700419', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 490000.00, 30000.00, 390000.00, 'Chờ xác nhận', '2025-12-10 18:57:00', '2025-12-10 18:57:00', '12', 100000.00),
(35, 'DH20251210130007585', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 520000.00, 0.00, 420000.00, 'Chờ xác nhận', '2025-12-10 19:00:07', '2025-12-10 19:00:07', '12', 100000.00),
(36, 'DH20251210130304191', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 700000.00, 0.00, 600000.00, 'Chờ xác nhận', '2025-12-10 19:03:04', '2025-12-10 19:03:04', '12', 100000.00),
(37, 'DH20251210130459260', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 670000.00, 0.00, 570000.00, 'Chờ xác nhận', '2025-12-10 19:04:59', '2025-12-10 19:04:59', '12', 100000.00),
(38, 'DH20251210130748614', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 390000.00, 30000.00, 290000.00, 'Chờ xác nhận', '2025-12-10 19:07:48', '2025-12-10 19:07:48', '12', 100000.00),
(39, 'DH20251210132027405', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 1000000.00, 30000.00, 930000.00, 'Chờ xác nhận', '2025-12-10 19:20:27', '2025-12-10 19:20:27', '12', 100000.00),
(40, 'DH20251210132053154', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'cod', '', 1000000.00, 0.00, 900000.00, 'Chờ xác nhận', '2025-12-10 19:20:53', '2025-12-10 19:20:53', '12', 100000.00),
(41, 'DH20251210132218871', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 70000.00, 30000.00, 100000.00, 'Chờ xác nhận', '2025-12-10 19:22:18', '2025-12-13 00:05:51', NULL, 0.00),
(42, 'DH20251211011148664', 24, 'Huỳnh Minh Khải Hoàn', '5165346', 'hoan80904@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 390000.00, 30000.00, 320000.00, 'Chờ xác nhận', '2025-12-11 07:11:48', '2025-12-11 07:11:48', '12', 100000.00),
(43, 'DH20251212153721770', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 90000.00, 30000.00, 120000.00, 'Đã hủy', '2025-12-12 21:37:21', '2025-12-13 00:09:20', NULL, 0.00),
(44, 'DH20251212153808936', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 100000.00, 30000.00, 130000.00, 'Chờ xác nhận', '2025-12-12 21:38:08', '2025-12-12 21:38:08', NULL, 0.00),
(45, 'DH20251212155519556', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 140000.00, 30000.00, 170000.00, 'Chờ xác nhận', '2025-12-12 21:55:19', '2025-12-12 21:55:19', NULL, 0.00),
(46, 'DH20251212155702513', 33, 'Vàng', '5165346', 'Vang@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 100000.00, 30000.00, 130000.00, 'Chờ xác nhận', '2025-12-12 21:57:02', '2025-12-12 21:57:02', NULL, 0.00),
(47, 'DH20251212163146272', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 130000.00, 30000.00, 160000.00, 'Đã giao', '2025-12-12 22:31:46', '2025-12-12 23:38:52', NULL, 0.00),
(48, 'DH20251212184943473', 33, 'Vàng', '5165346', 'Vang@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 90000.00, 30000.00, 120000.00, 'Đang giao', '2025-12-13 00:49:43', '2025-12-13 04:03:29', NULL, 0.00),
(49, 'DH20251212185049345', 33, 'Vàng', '5165346', 'Vang@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 440000.00, 30000.00, 370000.00, 'Đã giao', '2025-12-13 00:50:49', '2025-12-13 04:00:11', '12', 100000.00),
(50, 'DH20251212185431861', 33, 'Vàng', '5165346', 'Vang@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 140000.00, 30000.00, 170000.00, 'Đã giao', '2025-12-13 00:54:31', '2025-12-13 03:55:07', NULL, 0.00),
(51, 'DH20251212194655931', 33, 'Vàng', '5165346', 'Vang@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'Thanh toán khi nhận hàng', NULL, 90000.00, 30000.00, 120000.00, 'Đã giao', '2025-12-13 01:46:55', '2025-12-13 02:05:19', NULL, 0.00),
(52, 'DH20251213031204454', 34, 'Huỳnh', '5165346', 'Huynh@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'Chuyển khoản ngân hàng', NULL, 570000.00, 30000.00, 500000.00, 'Chờ xác nhận', '2025-12-13 09:12:04', '2025-12-13 09:12:04', '12', 100000.00),
(53, 'DH20251213033802517', 34, 'Huỳnh', '5165346', 'Huynh@gmail.com', 'csayujadgj, Cần Thơ', NULL, 'bank_transfer', '', 465000.00, 30000.00, 365000.00, 'Đã giao', '2025-12-13 09:38:02', '2025-12-13 10:08:12', '12', 100000.00),
(54, 'DH20251215112910318', 28, 'Administrator', '5165346', 'admin@shop.com', 'csayujadgj, Cần Thơ', NULL, 'Chuyển khoản ngân hàng', NULL, 90000.00, 30000.00, 120000.00, 'Chờ xác nhận', '2025-12-15 17:29:10', '2025-12-15 17:29:10', NULL, 0.00);

-- --------------------------------------------------------

--
-- Table structure for table `kho_movements`
--

CREATE TABLE `kho_movements` (
  `id` int(11) NOT NULL,
  `loai` enum('nhap','xuat','kiemke') NOT NULL,
  `san_pham_id` int(11) NOT NULL,
  `qty` int(11) NOT NULL,
  `nguoi_id` int(11) DEFAULT NULL,
  `ghi_chu` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kho_movements`
--

INSERT INTO `kho_movements` (`id`, `loai`, `san_pham_id`, `qty`, `nguoi_id`, `ghi_chu`, `created_at`) VALUES
(1, 'nhap', 161, 10, NULL, '', '2025-12-11 14:45:01');

-- --------------------------------------------------------

--
-- Table structure for table `khuyen_mai`
--

CREATE TABLE `khuyen_mai` (
  `id` int(11) NOT NULL,
  `ma_khuyen_mai` varchar(50) NOT NULL,
  `ten_khuyen_mai` varchar(255) NOT NULL,
  `mo_ta` text DEFAULT NULL,
  `loai_giam` enum('phan_tram','so_tien') DEFAULT 'phan_tram',
  `gia_tri_giam` decimal(10,2) NOT NULL,
  `gia_tri_don_toi_thieu` decimal(10,2) DEFAULT 0.00,
  `gia_tri_giam_toi_da` decimal(10,2) DEFAULT NULL,
  `so_luong_ma` int(11) DEFAULT NULL,
  `so_lan_da_dung` int(11) DEFAULT 0,
  `loai_ap_dung` enum('tat_ca','danh_muc','san_pham') DEFAULT 'tat_ca',
  `ngay_bat_dau` datetime NOT NULL,
  `ngay_ket_thuc` datetime NOT NULL,
  `trang_thai` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `khuyen_mai`
--

INSERT INTO `khuyen_mai` (`id`, `ma_khuyen_mai`, `ten_khuyen_mai`, `mo_ta`, `loai_giam`, `gia_tri_giam`, `gia_tri_don_toi_thieu`, `gia_tri_giam_toi_da`, `so_luong_ma`, `so_lan_da_dung`, `loai_ap_dung`, `ngay_bat_dau`, `ngay_ket_thuc`, `trang_thai`, `created_at`, `updated_at`) VALUES
(1, '12', 'mai', 'bsajk', 'so_tien', 100000.00, 300000.00, 1000000.00, 100, 12, '', '2025-12-09 16:00:00', '2025-12-31 16:00:00', 1, '2025-12-10 09:01:18', '2025-12-13 02:38:02'),
(108, 'MEW', 'MEW', '', 'phan_tram', 10.00, 0.00, 1000000.00, 20, 0, 'tat_ca', '2025-12-11 20:48:00', '2025-12-26 20:48:00', 1, '2025-12-13 13:48:10', '2025-12-13 13:48:10');

-- --------------------------------------------------------

--
-- Table structure for table `khuyen_mai_danh_muc`
--

CREATE TABLE `khuyen_mai_danh_muc` (
  `id` int(11) NOT NULL,
  `khuyen_mai_id` int(11) NOT NULL,
  `danh_muc_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `khuyen_mai_san_pham`
--

CREATE TABLE `khuyen_mai_san_pham` (
  `id` int(11) NOT NULL,
  `khuyen_mai_id` int(11) NOT NULL,
  `san_pham_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lich_su_kho`
--

CREATE TABLE `lich_su_kho` (
  `id` int(11) NOT NULL,
  `san_pham_id` int(11) NOT NULL,
  `hanh_dong` enum('nhap','xuat','dieu_chinh') NOT NULL,
  `so_luong` int(11) NOT NULL,
  `gia_nhap` decimal(10,2) DEFAULT 0.00,
  `ghi_chu` text DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `lich_su_khuyen_mai`
--

CREATE TABLE `lich_su_khuyen_mai` (
  `id` int(11) NOT NULL,
  `khuyen_mai_id` int(11) NOT NULL,
  `don_hang_id` int(11) NOT NULL,
  `nguoi_dung_id` int(11) DEFAULT NULL,
  `gia_tri_giam` decimal(10,2) NOT NULL,
  `ngay_su_dung` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `lich_su_khuyen_mai`
--

INSERT INTO `lich_su_khuyen_mai` (`id`, `khuyen_mai_id`, `don_hang_id`, `nguoi_dung_id`, `gia_tri_giam`, `ngay_su_dung`) VALUES
(1, 1, 28, 24, 100000.00, '2025-12-10 11:11:39'),
(2, 1, 30, 24, 100000.00, '2025-12-10 11:44:36'),
(3, 1, 31, 24, 100000.00, '2025-12-10 11:48:49'),
(4, 1, 32, 24, 100000.00, '2025-12-10 11:52:21'),
(5, 1, 33, 24, 100000.00, '2025-12-10 11:54:27'),
(6, 1, 34, 24, 100000.00, '2025-12-10 11:57:00'),
(7, 1, 35, 24, 100000.00, '2025-12-10 12:00:07'),
(8, 1, 36, 24, 100000.00, '2025-12-10 12:03:04'),
(9, 1, 37, 24, 100000.00, '2025-12-10 12:04:59'),
(10, 1, 38, 24, 100000.00, '2025-12-10 12:07:48'),
(11, 1, 40, 28, 100000.00, '2025-12-10 12:20:53'),
(12, 1, 53, 34, 100000.00, '2025-12-13 02:38:02');

-- --------------------------------------------------------

--
-- Table structure for table `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `id` int(11) NOT NULL,
  `ho_ten` varchar(100) NOT NULL,
  `ten_dang_nhap` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mat_khau` varchar(255) NOT NULL,
  `quyen` varchar(20) NOT NULL DEFAULT 'user',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `khoa` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`id`, `ho_ten`, `ten_dang_nhap`, `email`, `mat_khau`, `quyen`, `ngay_tao`, `khoa`) VALUES
(12, '123', '147', '147@gmail.com', '$2y$10$OuybL3c4tvTL59oAsSvL2.Hy9XuFCqfxJyWOYCqPTZ/XLGQc0YYA6', 'user', '2025-11-20 05:13:19', 0),
(13, '123', '123', '123@gmail.com', '$2y$10$6LnQuLYCICN7XP/bedYYVOUGrgqJ0VQ8230lWDQs6ISwhYz63y5Cm', 'user', '2025-11-20 07:02:58', 0),
(14, 'Phương Trương', '', 'truongphuong060304@gmail.com', '$2y$10$66lQTBaKoIoUkQp2tfK8S.yOtbMG7eU/TI0nyIwPslp3R/dU1h1h6', 'user', '2025-11-22 07:16:27', 0),
(18, 'Bảo Ngọc', 'vodangkhoavk', 'vodangkhoavk@gmail.com', '$2y$10$FHTttqVPRoXkLX1DiV2cF.D/TtB29CDl07IgHRk7swZsaloRV5byS', 'user', '2025-11-24 06:54:31', 0),
(19, '258', '258', '258@gmail.com.vn', '$2y$10$yE5DSYg.5NpEv8Rsi.2wFeusdw1g4qUoSrDtJ4OfxpkXCAJ4g9pOS', 'user', '2025-11-25 00:32:45', 0),
(20, 'Thái Huỳnh', 'thaidthwa2004', 'thaidthwa2004@gmail.com', '$2y$10$GYiDBTQgdxhh5H50fp6bn.YLreh/wn5fkT71ZCcwgcq6fR1Gqy2Nu', 'user', '2025-11-25 00:33:53', 0),
(21, '456', '456', '456@gmail.com', '$2y$10$GpGLfdaVgki93g6Rxx61j.cP7QPwebFPgjraTitAG5DlqtANDRtgC', 'user', '2025-11-25 01:38:44', 1),
(24, 'Huỳnh Minh Khải Hoàn', 'Hoan', 'hoan80904@gmail.com', '$2y$10$EU6r.bWoqV6DI6BTnoGpUeRhnXXnNpV5DV7bsD4aq5fgBIXzVkv9W', 'admin', '2025-11-25 02:03:00', 0),
(26, 'Trương Thị Mỹ Phương', 'mỹ', 'truongphuong0603@gmail.com', '$2y$10$gHwpwz2XphLz0VRbQ/Q1d.WbOJfBlZcwLnYaQPZ8rvl9UCGyV2UQO', 'user', '2025-11-26 03:55:28', 0),
(28, 'Administrator', 'admin', 'admin@shop.com', '$2y$10$4PkCcVDrpQxvziPtw1XB0.ju4CE7LJM0ruZx7sWAhZpVUK.OKy61q', 'admin', '2025-12-02 00:59:00', 0),
(29, '789', '789', '789@gmail.com.vn', '$2y$10$w12SJJK1rNm3qN5H2XX56OoPxQgQz0h/GCVYuxsv5JBEpLDFtIC56', 'user', '2025-12-04 23:36:38', 1),
(30, '357', '357', '357@gmail.com', '$2y$10$BPXfdV7wV9UAqmD8OFbNrOOILSkNZXa0j7vkSwbMVdyPbEX/ruNNu', 'user', '2025-12-05 01:20:09', 0),
(31, '159', '159', '159@gmail.com', '$2y$10$ve5hKA9bmE/3FCZmOofuuOIX02FH/d7lb0hUy.pBMQ1OANIFIbdhi', 'user', '2025-12-05 01:33:55', 0),
(32, 'Oanh Huỳnh', 'oh2756844', 'oh2756844@gmail.com', '$2y$10$txXw/x91E.wC6VFR17RZ6OmOPcCeVyLuO7aFUrQHWXA.RQH8LXKRK', 'user', '2025-12-12 05:27:00', 0),
(33, 'Vàng', 'Vàng', 'Vang@gmail.com', '$2y$10$RLEgOISayDuX1TtaMYGMuuigAI2FBOn09jWxZjOhhiYupKfXmvRYa', 'user', '2025-12-12 08:13:22', 0),
(34, 'Huỳnh', 'Huỳnh', 'Huynh@gmail.com', '$2y$10$SDAPU8aLUTO18kcvPhFJKeucqPaEa23S.aB692664eDROsulgIAMC', 'user', '2025-12-13 02:09:42', 0);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `type` varchar(50) NOT NULL COMMENT 'new_product, sale, promotion, announcement',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NULL DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `type`, `title`, `message`, `link`, `image_url`, `created_at`, `expires_at`, `is_active`) VALUES
(1, 'new_product', 'Sản phẩm mới về', 'Bộ sưu tập váy mùa đông 2024 vừa được cập nhật! Xem ngay để không bỏ lỡ.', 'san-pham.php', 'https://images.unsplash.com/photo-1515886657613-9f3515b0c78f?w=400', '2025-11-28 10:33:09', NULL, 1),
(2, 'sale', 'Giảm giá 50%', 'Flash Sale - Giảm giá đến 50% cho tất cả sản phẩm mùa đông. Nhanh tay!', 'sale.php', 'https://images.unsplash.com/photo-1607082348824-0a96f2a4b9da?w=400', '2025-11-28 10:33:09', NULL, 1),
(3, 'promotion', 'Miễn phí vận chuyển', 'Áp dụng miễn phí vận chuyển cho đơn hàng từ 500k trong tuần này!', 'trangchu.php', NULL, '2025-11-28 10:33:09', NULL, 1),
(4, 'announcement', 'Sắp hết: váy đuôi cá', 'Chỉ còn 5 sản phẩm. Đặt hàng ngay để không bỏ lỡ!', 'chitiet_san_pham.php?id=42', NULL, '2025-11-28 10:40:49', '2025-11-30 10:40:49', 1),
(5, 'new_product', 'Sản phẩm mới: 999', 'Chúng tôi vừa cập nhật sản phẩm mới thuộc danh mục Sản phẩm. Xem ngay!', 'chitiet_san_pham.php?id=106', NULL, '2025-11-28 10:41:57', '2025-12-05 10:41:57', 1),
(6, 'new_product', 'Sản phẩm mới: 88', 'Chúng tôi vừa cập nhật sản phẩm mới thuộc danh mục Sản phẩm. Xem ngay!', 'chitiet_san_pham.php?id=107', NULL, '2025-11-28 10:45:45', '2025-12-05 10:45:45', 1),
(7, 'new_product', 'Sản phẩm mới: Cây ngũ gia bì cẩm thạch nhỏ chậu ươm SCHE020', 'Chúng tôi vừa cập nhật sản phẩm mới thuộc danh mục Sản phẩm. Xem ngay!', 'chitiet_san_pham.php?id=108', NULL, '2025-12-05 12:01:20', '2025-12-12 12:01:20', 1),
(8, 'new_product', 'Sản phẩm mới: 2', 'Chúng tôi vừa cập nhật sản phẩm mới thuộc danh mục Sản phẩm. Xem ngay!', 'chitiet_san_pham.php?id=109', NULL, '2025-12-05 13:14:13', '2025-12-12 13:14:13', 1),
(9, 'new_product', 'Sản phẩm mới: Cây Vạn Hoa', 'Chúng tôi vừa cập nhật sản phẩm mới thuộc danh mục Sản phẩm. Xem ngay!', 'chitiet_san_pham.php?id=160', NULL, '2025-12-07 10:13:33', '2025-12-14 10:13:33', 1);

-- --------------------------------------------------------

--
-- Table structure for table `san_pham`
--

CREATE TABLE `san_pham` (
  `id` int(11) NOT NULL,
  `ma_san_pham` varchar(50) NOT NULL,
  `ten_san_pham` varchar(200) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
  `danh_muc_id` int(11) DEFAULT NULL,
  `gia` decimal(10,2) NOT NULL,
  `gia_nhap` decimal(10,2) DEFAULT 0.00,
  `so_luong` int(11) NOT NULL DEFAULT 0,
  `mo_ta` varchar(1000) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `hinh_anh` varchar(255) DEFAULT NULL,
  `trang_thai` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `san_pham`
--

INSERT INTO `san_pham` (`id`, `ma_san_pham`, `ten_san_pham`, `danh_muc_id`, `gia`, `gia_nhap`, `so_luong`, `mo_ta`, `hinh_anh`, `trang_thai`) VALUES
(110, 'SP001', 'Cây Kim Tiền', 1, 150000.00, 105000.00, 50, 'Lá xanh bóng, thân vươn thẳng, mang lại tài lộc và may mắn cho gia chủ.', 'p_69364ca61903a.png', 1),
(111, 'SP002', 'Cây Kim Ngân (3 thân)', 1, 150000.00, 105000.00, 40, 'Thân xoắn Tam Tài, giữ tiền của, rất hợp người mệnh Mộc và Hỏa.', 'p_69364c8035ba7.png', 1),
(112, 'SP003', 'Cây Vạn Lộc', 1, 110000.00, 77000.00, 35, 'Lá đỏ hồng rực rỡ, mang lại vạn sự như ý, thích hợp làm quà tặng.', 'p_69364c6e9e00d.png', 1),
(113, 'SP004', 'Cây Bạch Mã Hoàng Tử', 1, 160000.00, 112000.00, 20, 'Thân trắng, lá xanh, vẻ đẹp lịch lãm, giúp công việc thuận buồm xuôi gió.', 'p_69364c5d11642.png', 1),
(114, 'SP005', 'Cây Lan Quân Tử', 1, 250000.00, 175000.00, 15, 'Hoa cam rực rỡ, dáng cây vươn thẳng khí phách quân tử.', 'p_69364c4855c0f.png', 1),
(115, 'SP006', 'Cây Tùng Bồng Lai', 1, 160000.00, 112000.00, 25, 'Dáng thế đẹp như cổ thụ thu nhỏ, quý nhân phù trợ cho tuổi Thân.', 'p_69364c341dc3c.png', 1),
(116, 'SP007', 'Cây Kim Ngân Lượng', 1, 200000.00, 140000.00, 30, 'Chùm quả đỏ mọng sum suê, mang ý nghĩa tài lộc đầy nhà dịp lễ Tết.', 'p_69364c1d986e9.png', 1),
(117, 'SP008', 'Cây Bàng Singapore', 5, 450000.00, 315000.00, 10, 'Lá to tròn, dáng đứng hiện đại, thích hợp trang trí góc phòng khách.', 'p_69364c005595a.png', 1),
(118, 'SP009', 'Cây Thiết Mộc Lan', 5, 350000.00, 245000.00, 12, 'Lọc khí tốt, mang lại may mắn, thường đặt ở sảnh lớn.', 'p_69364befb49f2.png', 1),
(119, 'SP010', 'Cây Phát Tài Núi', 5, 400000.00, 280000.00, 8, 'Dáng cây hoang dã, sức sống bền bỉ, thích hợp sân vườn hoặc sảnh.', 'p_69364bd95361c.png', 1),
(120, 'SP011', 'Cây Tùng La Hán', 5, 550000.00, 385000.00, 4, 'Dáng thế bonsai nghệ thuật, trấn trạch, trừ tà, thể hiện đẳng cấp.', 'p_69364ba6cd37d.png', 1),
(121, 'SP012', 'Cây Chuối Rẻ Quạt', 5, 500000.00, 350000.00, 4, 'Lá to bản, dáng vẻ bề thế, mang phong cách nhiệt đới (Tropical).', 'p_69364b8e1c7b2.png', 1),
(122, 'SP013', 'Sen Đá Nâu', 6, 25000.00, 17500.00, 100, 'Nhỏ nhắn, màu nâu socola độc đáo, sức sống bền bỉ.', 'p_69364b7ebaea3.png', 1),
(123, 'SP014', 'Sen Đá Phật Bà', 6, 40000.00, 28000.00, 80, 'Các lá xếp tỏa tròn như đài sen phật bà, mang lại sự bình an.', 'p_69364b6f5c611.png', 1),
(124, 'SP015', 'Xương Rồng Tai Thỏ', 6, 35000.00, 24500.00, 90, 'Hình dáng ngộ nghĩnh như tai thỏ, chịu hạn cực tốt.', 'p_69364b5b8d419.png', 1),
(125, 'SP016', 'Cây Cẩm Nhung', 6, 40000.00, 28000.00, 60, 'Lá có vân lưới đỏ hoặc trắng rất đẹp, ưa ẩm, thích hợp Terrarium.', 'p_69364b47ee2e9.png', 1),
(126, 'SP017', 'Sen Đá Kim Cương', 6, 65000.00, 45500.00, 50, 'Lá trong suốt, căng mọng như viên ngọc quý, vẻ đẹp tinh tế.', 'p_69364b34cfbe3.png', 1),
(127, 'SP018', 'Xương Rồng Trứng Chim', 6, 30000.00, 21000.00, 70, 'Tròn nhỏ, gai trắng bao phủ như kén tằm, rất dễ thương.', 'p_69364b239bde4.png', 1),
(128, 'SP019', 'Cây Dây Nhện (Cỏ Lan Chi)', 7, 50000.00, 35000.00, 45, 'Lá rủ mềm mại, lọc không khí cực tốt, an toàn cho trẻ nhỏ.', 'p_69364b1165a44.png', 1),
(129, 'SP020', 'Cây Thường Xuân', 7, 80000.00, 56000.00, 30, 'Dạng dây leo rủ, biểu tượng của sự may mắn, xua đuổi âm khí.', 'p_69364b00bcc45.png', 1),
(130, 'SP021', 'Cây Trầu Bà Sữa', 7, 75000.00, 52500.00, 40, 'Lá đốm trắng sữa độc đáo, dáng rủ đẹp, thích hợp treo ban công.', 'p_69364aefec3de.png', 1),
(131, 'SP022', 'Cây Lan Hạt Dưa', 7, 60000.00, 42000.00, 50, 'Lá nhỏ hình bầu dục như hạt dưa, rủ xuống xanh mát.', 'p_69364addbde69.png', 1),
(132, 'SP023', 'Cây Thu Hải Đường', 7, 90000.00, 63000.00, 25, 'Lá và hoa đều đẹp, thích hợp treo cửa sổ trang trí.', 'p_69364acabc3de.png', 1),
(133, 'SP024', 'Cây Monstera (Trầu bà Nam Mỹ)', 9, 280000.00, 196000.00, 14, 'Lá xẻ độc đáo, phong cách nội thất Châu Âu hiện đại.', 'p_69364ab96bf2e.png', 1),
(134, 'SP025', 'Cây Dương Xỉ Cổ Đại', 9, 120000.00, 84000.00, 28, 'Lá xẻ đẹp mắt, lọc ẩm mốc và kim loại nặng trong không khí.', 'p_69364a9e0a0ca.png', 1),
(135, 'SP026', 'Cây Trầu Bà Đế Vương Đỏ', 10, 180000.00, 126000.00, 20, 'Lá màu đỏ tía sang trọng, thể hiện quyền uy và thăng tiến.', 'p_69364a5da53db.png', 1),
(136, 'SP027', 'Cây Phú Quý', 10, 100000.00, 70000.00, 40, 'Thân trắng hồng, lá xanh viền đỏ, biểu tượng của sự giàu sang.', 'p_69364a4bcc4c1.png', 1),
(137, 'SP028', 'Cây Hạnh Phúc', 10, 130000.00, 91000.00, 30, 'Lá xanh non mướt, mang ý nghĩa về gia đình hạnh phúc.', 'p_69364a3393eef.png', 1),
(138, 'SP029', 'Cây Nhất Mạt Hương', 10, 45000.00, 31500.00, 50, 'Mùi thơm nhẹ như bạc hà khi chạm vào, lá nhỏ nhắn.', 'p_69364a200d3bd.png', 1),
(139, 'SP030', 'Cây Tuyết Tùng', 10, 140000.00, 98000.00, 15, 'Hình dáng như cây thông noel mini, mùi thơm gỗ nhẹ.', 'p_69364a04dd3ab.png', 1),
(140, 'SP031', 'Cây Ngọc Ngân', 11, 95000.00, 66500.00, 40, 'Lá đốm trắng xanh đẹp mắt, tượng trưng cho tình yêu thuần khiết.', 'p_693649f2573ee.png', 1),
(141, 'SP032', 'Cây Đa Búp Đỏ', 11, 220000.00, 154000.00, 15, 'Lá dày bóng, búp đỏ nổi bật, sức sống mạnh mẽ, hút bụi tốt.', 'p_693649d747609.png', 1),
(142, 'SP033', 'Cây Cau Tiểu Trâm', 11, 80000.00, 56000.00, 45, 'Hình dáng như cây dừa mini, lọc khí benzene tốt.', 'p_693649c04b5d8.png', 1),
(143, 'SP034', 'Cây Cọ Nhật', 11, 190000.00, 133000.00, 15, 'Tán lá xòe rộng như chiếc quạt, tạo điểm nhấn xanh mát.', 'p_693649a6009da.png', 1),
(144, 'SP035', 'Cây Hương Thảo (Rosemary)', 12, 65000.00, 45500.00, 35, 'Tỏa hương thơm dễ chịu, dùng làm gia vị nấu ăn, đuổi muỗi.', 'p_69364992a3c6a.png', 1),
(145, 'SP036', 'Cây Hoa Giấy', 14, 120000.00, 84000.00, 25, 'Sai hoa, nhiều màu sắc, chịu nắng tốt, làm đẹp ban công.', 'p_6936497da8c72.png', 1),
(146, 'SP037', 'Cây Dạ Yến Thảo', 14, 70000.00, 49000.00, 39, 'Hoa nở quanh năm, màu sắc rực rỡ, dáng rủ mềm mại.', 'p_6936495ed385d.png', 1),
(147, 'SP038', 'Cây Cúc Tần Ấn Độ', 14, 85000.00, 59500.00, 28, 'Dây leo rủ xuống như tấm rèm xanh, che nắng hướng Tây cực tốt.', 'p_6936494beb222.png', 1),
(148, 'SP039', 'Cây Lưỡi Hổ Viền Vàng', 15, 90000.00, 63000.00, 59, 'Hấp thụ khí độc, nhả oxy ban đêm, xua đuổi tà khí.', 'p_69364934961ee.png', 1),
(149, 'SP040', 'Cây Lan Ý (Vĩ Trâm)', 15, 85000.00, 59500.00, 50, 'Hoa trắng tinh khôi, lọc bụi bẩn, cân bằng trường khí.', 'p_6936491a1e0a2.png', 1),
(150, 'SP041', 'Cây Ngũ Gia Bì', 15, 140000.00, 98000.00, 29, 'Lá xanh quanh năm, có khả năng đuổi muỗi tự nhiên.', 'p_69364902f1aff.png', 1),
(151, 'SP042', 'Cây Nha Đam (Lô Hội)', 16, 50000.00, 35000.00, 18, 'Dễ trồng, thanh lọc không khí, gel dùng làm đẹp.', 'p_693648eecec55.png', 1),
(152, 'SP043', 'Cây Sống Đời', 16, 60000.00, 42000.00, 40, 'Hoa nhỏ nhiều màu, sức sống mãnh liệt, biểu tượng sự trường thọ.', 'p_693648c8e9a16.png', 1),
(153, 'SP044', 'Cây Trúc Nhật Vàng', 17, 110000.00, 77000.00, 18, 'Lá đốm vàng nổi bật, chịu bóng tốt, thích hợp để trong nhà.', 'p_693648b376843.png', 1),
(154, 'SP045', 'Cây Hồng Môn', 17, 130000.00, 91000.00, 15, 'Hoa đỏ rực rỡ, lá xanh đậm, mang lại may mắn tình duyên.', 'p_69364889b8121.png', 1),
(155, 'SP046', 'Cây Trạng Nguyên', 17, 100000.00, 70000.00, 16, 'Lá bắc màu đỏ rực vào mùa đông, biểu tượng của sự thành đạt.', 'p_693640399f7e3.png', 1),
(156, 'SP047', 'Cây Trúc Phú Quý', 18, 90000.00, 63000.00, 31, 'Trồng trong nước, dễ chăm sóc, mang lại tài lộc.', 'p_69364012b47c2.png', 1),
(157, 'SP048', 'Cây Tiên Ông', 18, 70000.00, 49000.00, 0, 'Bộ rễ trắng muốt đẹp mắt, hoa thơm nồng nàn.', 'p_69363fef3b218.png', 0),
(158, 'SP049', 'Cây Bèo Nhật', 18, 15000.00, 10500.00, 100, 'Cây thả bể cá, lọc nước, tạo môi trường cho cá.', 'p_69363fd13eaaf.png', 0);

-- --------------------------------------------------------

--
-- Table structure for table `san_pham_danh_muc`
--

CREATE TABLE `san_pham_danh_muc` (
  `san_pham_id` int(11) NOT NULL,
  `danh_muc_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `san_pham_danh_muc`
--

INSERT INTO `san_pham_danh_muc` (`san_pham_id`, `danh_muc_id`) VALUES
(110, 1),
(110, 10),
(110, 11),
(111, 9),
(112, 1),
(112, 6),
(112, 9),
(112, 10),
(112, 11),
(112, 15),
(113, 1),
(113, 6),
(113, 9),
(113, 10),
(113, 11),
(113, 15),
(114, 10),
(114, 11),
(114, 15),
(115, 1),
(115, 6),
(115, 9),
(115, 10),
(115, 15),
(116, 1),
(116, 10),
(117, 1),
(117, 5),
(117, 9),
(117, 11),
(117, 13),
(117, 15),
(118, 1),
(118, 5),
(118, 11),
(118, 13),
(118, 15),
(119, 1),
(119, 5),
(119, 9),
(119, 13),
(119, 15),
(120, 1),
(120, 5),
(120, 9),
(121, 1),
(121, 5),
(122, 6),
(122, 10),
(122, 19),
(123, 6),
(123, 16),
(123, 19),
(124, 6),
(124, 10),
(124, 16),
(125, 6),
(125, 9),
(125, 10),
(125, 15),
(126, 6),
(126, 10),
(126, 12),
(126, 16),
(126, 19),
(127, 6),
(127, 10),
(127, 16),
(128, 7),
(128, 9),
(128, 11),
(128, 15),
(129, 1),
(129, 6),
(129, 7),
(129, 9),
(129, 10),
(129, 14),
(129, 15),
(129, 16),
(130, 1),
(130, 7),
(130, 9),
(130, 10),
(130, 11),
(130, 14),
(130, 15),
(130, 16),
(131, 6),
(131, 7),
(131, 9),
(131, 14),
(131, 15),
(131, 16),
(132, 10),
(132, 14),
(133, 1),
(133, 5),
(133, 9),
(133, 11),
(133, 13),
(133, 15),
(133, 16),
(134, 1),
(134, 5),
(134, 9),
(134, 11),
(134, 13),
(134, 15),
(134, 16),
(135, 1),
(135, 5),
(135, 9),
(135, 11),
(135, 13),
(135, 15),
(136, 6),
(136, 9),
(136, 10),
(136, 11),
(136, 15),
(137, 1),
(137, 6),
(137, 9),
(137, 10),
(137, 11),
(138, 1),
(138, 9),
(138, 11),
(138, 12),
(138, 15),
(138, 16),
(139, 1),
(139, 9),
(139, 11),
(140, 1),
(140, 9),
(140, 11),
(140, 13),
(140, 15),
(140, 16),
(141, 1),
(141, 5),
(141, 9),
(141, 11),
(141, 13),
(141, 15),
(141, 16),
(142, 1),
(142, 6),
(142, 9),
(142, 10),
(142, 15),
(142, 16),
(143, 1),
(143, 5),
(143, 9),
(143, 13),
(143, 15),
(144, 6),
(144, 9),
(144, 10),
(144, 11),
(144, 15),
(145, 5),
(145, 13),
(145, 16),
(146, 6),
(146, 7),
(146, 14),
(147, 7),
(147, 8),
(147, 9),
(147, 14),
(148, 6),
(148, 9),
(148, 10),
(148, 11),
(148, 15),
(148, 16),
(149, 10),
(149, 11),
(149, 13),
(149, 15),
(149, 16),
(150, 6),
(150, 9),
(150, 10),
(150, 11),
(151, 6),
(151, 10),
(151, 11),
(152, 10),
(152, 11),
(153, 1),
(153, 9),
(153, 10),
(153, 11),
(154, 1),
(155, 10),
(155, 11),
(156, 1),
(156, 9),
(156, 10),
(156, 11),
(156, 15),
(156, 16),
(156, 18),
(157, 6),
(157, 10),
(157, 11),
(157, 16),
(157, 18),
(158, 16),
(158, 18);

-- --------------------------------------------------------

--
-- Table structure for table `thong_bao`
--

CREATE TABLE `thong_bao` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `type` varchar(50) NOT NULL COMMENT 'review_reply, order_delivered, new_product',
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `thong_bao`
--

INSERT INTO `thong_bao` (`id`, `user_id`, `user_email`, `type`, `title`, `message`, `link`, `is_read`, `created_at`) VALUES
(1, 14, '', 'order_delivered', '📦 Đơn hàng đã được giao', 'Đơn hàng DH20251127043930652 đã được giao thành công. Bạn có thể đánh giá sản phẩm ngay!', 'don_hang_cua_toi.php', 1, '2025-11-27 10:40:22'),
(2, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:41:48'),
(3, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:41:53'),
(4, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:42:04'),
(5, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:44:33'),
(6, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:44:41'),
(7, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:44:49'),
(8, 14, '', 'order_delivered', '📦 Đơn hàng đã được giao', 'Đơn hàng DH20251127044939140 đã được giao thành công. Bạn có thể đánh giá sản phẩm ngay!', 'don_hang_cua_toi.php', 1, '2025-11-27 10:49:57'),
(9, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:53:20'),
(10, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:53:25'),
(11, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:53:31'),
(12, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:53:41'),
(13, 14, NULL, 'review_reply', '💬 Shop đã phản hồi đánh giá của bạn', 'Shop vừa phản hồi đánh giá sản phẩm của bạn. Xem chi tiết trong đơn hàng.', 'don_hang_cua_toi.php', 1, '2025-11-27 10:53:54');

-- --------------------------------------------------------

--
-- Table structure for table `thong_bao_chung`
--

CREATE TABLE `thong_bao_chung` (
  `id` int(11) NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `noi_dung` text NOT NULL,
  `loai` enum('san_pham','khuyen_mai','tin_tuc') DEFAULT 'tin_tuc',
  `duong_dan` varchar(255) DEFAULT NULL,
  `ngay_tao` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `thong_bao_chung`
--

INSERT INTO `thong_bao_chung` (`id`, `tieu_de`, `noi_dung`, `loai`, `duong_dan`, `ngay_tao`) VALUES
(1, '🌱 Sản Phẩm Mới: Tiên hoa', 'Shop vừa về thêm mẫu Tiên hoa thuộc danh mục Sản phẩm. Xem ngay kẻo hết!', 'san_pham', 'chitiet_san_pham.php?id=161', '2025-12-11 20:58:05'),
(2, '🌱 Sản Phẩm Mới: 1', 'Shop vừa về thêm mẫu 1 thuộc danh mục Sản phẩm. Xem ngay kẻo hết!', 'san_pham', 'chitiet_san_pham.php?id=162', '2025-12-13 10:08:00'),
(3, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 17:14:31'),
(4, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 17:16:28'),
(5, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 17:53:08'),
(6, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 18:01:06'),
(7, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 18:01:11'),
(8, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 18:01:16'),
(9, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:11'),
(10, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:13'),
(11, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:14'),
(12, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:14'),
(13, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:14'),
(14, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:14'),
(15, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:14'),
(16, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:15'),
(17, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:15'),
(18, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:15'),
(19, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:31:48'),
(20, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:11'),
(21, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:12'),
(22, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:13'),
(23, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:14'),
(24, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:14'),
(25, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:14'),
(26, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:14'),
(27, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:15'),
(28, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:15'),
(29, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:15'),
(30, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:15'),
(31, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:15'),
(32, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:16'),
(33, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:59'),
(34, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:34:59'),
(35, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:35:00'),
(36, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:35:00'),
(37, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:35:00'),
(38, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:35:00'),
(39, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:35:00'),
(40, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:35:01'),
(41, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:16'),
(42, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:18'),
(43, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:19'),
(44, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:19'),
(45, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:20'),
(46, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:20'),
(47, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:20'),
(48, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:20'),
(49, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:20'),
(50, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:21'),
(51, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:21'),
(52, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:21'),
(53, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:22'),
(54, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:22'),
(55, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:22'),
(56, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:22'),
(57, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:55:22'),
(58, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:58:48'),
(59, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:58:50'),
(60, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:58:51'),
(61, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:58:51'),
(62, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 19:58:52'),
(63, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:16'),
(64, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:17'),
(65, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:18'),
(66, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:18'),
(67, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:19'),
(68, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:19'),
(69, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:20'),
(70, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:20'),
(71, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:20'),
(72, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:20'),
(73, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:20'),
(74, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:21'),
(75, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:21'),
(76, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:21'),
(77, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:21'),
(78, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:22'),
(79, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:22'),
(80, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:22'),
(81, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:22'),
(82, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:22'),
(83, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:23'),
(84, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:23'),
(85, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:02:23'),
(86, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:05:59'),
(87, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:00'),
(88, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:01'),
(89, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:01'),
(90, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:01'),
(91, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:01'),
(92, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:02'),
(93, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:02'),
(94, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:02'),
(95, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:02'),
(96, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:03'),
(97, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:06:03'),
(98, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:27:25'),
(99, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:27:27'),
(100, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:27:28'),
(101, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:27:29'),
(102, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:27:30'),
(103, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:33:14'),
(104, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:33:16'),
(105, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:33:17'),
(106, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:40:15'),
(107, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:40:55'),
(108, '🎁 Khuyến Mãi HOT: MEW', 'Nhập mã [MEW] để nhận ưu đãi ngay hôm nay. Số lượng có hạn!', 'khuyen_mai', 'san-pham.php', '2025-12-13 20:42:18');

-- --------------------------------------------------------

--
-- Table structure for table `user_notification_reads`
--

CREATE TABLE `user_notification_reads` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `notification_id` int(11) NOT NULL,
  `read_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `user_notification_reads`
--

INSERT INTO `user_notification_reads` (`id`, `user_id`, `notification_id`, `read_at`) VALUES
(1, 24, 6, '2025-12-02 04:21:23'),
(2, 24, 5, '2025-12-02 04:21:30'),
(3, 24, 1, '2025-12-02 04:21:35'),
(4, 24, 2, '2025-12-02 04:47:47'),
(5, 24, 3, '2025-12-02 04:47:54'),
(6, 28, 6, '2025-12-02 05:07:08'),
(7, 28, 1, '2025-12-02 05:07:15'),
(8, 28, 2, '2025-12-02 05:07:15'),
(9, 28, 3, '2025-12-02 05:07:15'),
(10, 28, 4, '2025-12-02 05:07:15'),
(11, 28, 5, '2025-12-02 05:07:15'),
(13, 24, 4, '2025-12-11 06:32:35'),
(14, 24, 7, '2025-12-11 06:32:35'),
(15, 24, 8, '2025-12-11 06:32:35'),
(16, 24, 9, '2025-12-11 06:32:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_is_read` (`is_read`);

--
-- Indexes for table `bai_viet`
--
ALTER TABLE `bai_viet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_trang_thai` (`trang_thai`),
  ADD KEY `idx_nguoi_dung` (`nguoi_dung_id`);

--
-- Indexes for table `bai_viet_yeuthich`
--
ALTER TABLE `bai_viet_yeuthich`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_like` (`nguoi_dung_id`,`bai_viet_id`),
  ADD KEY `bai_viet_id` (`bai_viet_id`);

--
-- Indexes for table `binh_luan_bai_viet`
--
ALTER TABLE `binh_luan_bai_viet`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bai_viet` (`bai_viet_id`),
  ADD KEY `idx_nguoi_dung` (`nguoi_dung_id`);

--
-- Indexes for table `chat_messages`
--
ALTER TABLE `chat_messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_session` (`session_id`);

--
-- Indexes for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `session_id` (`session_id`);

--
-- Indexes for table `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_don_hang` (`don_hang_id`),
  ADD KEY `idx_san_pham` (`san_pham_id`);

--
-- Indexes for table `danh_gia`
--
ALTER TABLE `danh_gia`
  ADD PRIMARY KEY (`id`),
  ADD KEY `san_pham_id` (`san_pham_id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_email` (`user_email`);

--
-- Indexes for table `danh_muc`
--
ALTER TABLE `danh_muc`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_don_hang` (`ma_don_hang`),
  ADD KEY `idx_nguoi_dung` (`nguoi_dung_id`),
  ADD KEY `idx_trang_thai` (`trang_thai`),
  ADD KEY `idx_ngay_dat` (`ngay_dat`);

--
-- Indexes for table `kho_movements`
--
ALTER TABLE `kho_movements`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `khuyen_mai`
--
ALTER TABLE `khuyen_mai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_khuyen_mai` (`ma_khuyen_mai`);

--
-- Indexes for table `khuyen_mai_danh_muc`
--
ALTER TABLE `khuyen_mai_danh_muc`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_promo_category` (`khuyen_mai_id`,`danh_muc_id`);

--
-- Indexes for table `khuyen_mai_san_pham`
--
ALTER TABLE `khuyen_mai_san_pham`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_promo_product` (`khuyen_mai_id`,`san_pham_id`);

--
-- Indexes for table `lich_su_kho`
--
ALTER TABLE `lich_su_kho`
  ADD PRIMARY KEY (`id`),
  ADD KEY `san_pham_id` (`san_pham_id`);

--
-- Indexes for table `lich_su_khuyen_mai`
--
ALTER TABLE `lich_su_khuyen_mai`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ten_dang_nhap` (`ten_dang_nhap`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_active` (`is_active`,`created_at`);

--
-- Indexes for table `san_pham`
--
ALTER TABLE `san_pham`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `ma_san_pham` (`ma_san_pham`),
  ADD KEY `danh_muc_id` (`danh_muc_id`);

--
-- Indexes for table `san_pham_danh_muc`
--
ALTER TABLE `san_pham_danh_muc`
  ADD PRIMARY KEY (`san_pham_id`,`danh_muc_id`),
  ADD KEY `danh_muc_id` (`danh_muc_id`);

--
-- Indexes for table `thong_bao`
--
ALTER TABLE `thong_bao`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `user_email` (`user_email`),
  ADD KEY `is_read` (`is_read`),
  ADD KEY `created_at` (`created_at`);

--
-- Indexes for table `thong_bao_chung`
--
ALTER TABLE `thong_bao_chung`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `user_notification_reads`
--
ALTER TABLE `user_notification_reads`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_user_notification` (`user_id`,`notification_id`),
  ADD KEY `idx_user` (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_notifications`
--
ALTER TABLE `admin_notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `bai_viet`
--
ALTER TABLE `bai_viet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=45;

--
-- AUTO_INCREMENT for table `bai_viet_yeuthich`
--
ALTER TABLE `bai_viet_yeuthich`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `binh_luan_bai_viet`
--
ALTER TABLE `binh_luan_bai_viet`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `chat_messages`
--
ALTER TABLE `chat_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `chat_sessions`
--
ALTER TABLE `chat_sessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=77;

--
-- AUTO_INCREMENT for table `danh_gia`
--
ALTER TABLE `danh_gia`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT for table `danh_muc`
--
ALTER TABLE `danh_muc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT for table `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `kho_movements`
--
ALTER TABLE `kho_movements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `khuyen_mai`
--
ALTER TABLE `khuyen_mai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `khuyen_mai_danh_muc`
--
ALTER TABLE `khuyen_mai_danh_muc`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `khuyen_mai_san_pham`
--
ALTER TABLE `khuyen_mai_san_pham`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `lich_su_kho`
--
ALTER TABLE `lich_su_kho`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `lich_su_khuyen_mai`
--
ALTER TABLE `lich_su_khuyen_mai`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=35;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `san_pham`
--
ALTER TABLE `san_pham`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=163;

--
-- AUTO_INCREMENT for table `thong_bao`
--
ALTER TABLE `thong_bao`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `thong_bao_chung`
--
ALTER TABLE `thong_bao_chung`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=109;

--
-- AUTO_INCREMENT for table `user_notification_reads`
--
ALTER TABLE `user_notification_reads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `bai_viet_yeuthich`
--
ALTER TABLE `bai_viet_yeuthich`
  ADD CONSTRAINT `bai_viet_yeuthich_ibfk_1` FOREIGN KEY (`nguoi_dung_id`) REFERENCES `nguoi_dung` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `bai_viet_yeuthich_ibfk_2` FOREIGN KEY (`bai_viet_id`) REFERENCES `bai_viet` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD CONSTRAINT `chi_tiet_don_hang_ibfk_1` FOREIGN KEY (`don_hang_id`) REFERENCES `don_hang` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `lich_su_kho`
--
ALTER TABLE `lich_su_kho`
  ADD CONSTRAINT `lich_su_kho_ibfk_1` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `san_pham`
--
ALTER TABLE `san_pham`
  ADD CONSTRAINT `san_pham_ibfk_1` FOREIGN KEY (`danh_muc_id`) REFERENCES `danh_muc` (`id`);

--
-- Constraints for table `san_pham_danh_muc`
--
ALTER TABLE `san_pham_danh_muc`
  ADD CONSTRAINT `san_pham_danh_muc_ibfk_1` FOREIGN KEY (`san_pham_id`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `san_pham_danh_muc_ibfk_2` FOREIGN KEY (`danh_muc_id`) REFERENCES `danh_muc` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
