-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: localhost
-- Thời gian đã tạo: Th10 06, 2026 lúc 09:19 PM
-- Phiên bản máy phục vụ: 10.4.28-MariaDB
-- Phiên bản PHP: 8.2.4

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `cocoon_db`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `categories`
--

INSERT INTO `categories` (`id`, `name`) VALUES
(1, 'Chăm sóc da'),
(2, 'Chăm sóc tóc'),
(3, 'Chăm sóc cơ thể');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `customer_name` varchar(100) NOT NULL,
  `customer_phone` varchar(20) NOT NULL,
  `customer_address` varchar(255) NOT NULL,
  `total_amount` decimal(12,0) NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'Chờ duyệt',
  `payment_method` varchar(30) NOT NULL DEFAULT 'COD',
  `user_id` int(11) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `orders`
--

INSERT INTO `orders` (`id`, `customer_name`, `customer_phone`, `customer_address`, `total_amount`, `status`, `payment_method`, `user_id`, `note`, `created_at`) VALUES
(1, 'Nguyễn Văn Khách', '0912345678', '79 Hồ Tùng Mậu, Cầu Giấy, Hà Nội', 415000, 'Đã giao', 'COD', 2, NULL, '2026-10-07 02:09:34'),
(2, 'Trần Thị Mai', '0987654321', '12 Láng Hạ, Đống Đa, Hà Nội', 290000, 'Đang giao', 'Chuyển khoản', NULL, NULL, '2026-10-07 02:09:34'),
(3, 'Lê Minh Anh', '0934567890', '25 Xuân Thủy, Cầu Giấy, Hà Nội', 255000, 'Chờ duyệt', 'COD', NULL, NULL, '2026-10-07 02:09:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) DEFAULT NULL,
  `product_name` varchar(255) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`) VALUES
(1, 1, 10, 'Cà phê Đắk Lắk làm sạch da chết cơ thể', 125000, 1),
(2, 1, 7, 'Nước dưỡng tóc tinh dầu bưởi', 165000, 1),
(3, 1, 6, 'Son dưỡng dầu dừa Bến Tre', 45000, 1),
(4, 1, 12, 'Cà phê Đắk Lắk làm sạch da chết môi', 75000, 1),
(5, 2, 10, 'Cà phê Đắk Lắk làm sạch da chết cơ thể', 125000, 1),
(6, 2, 7, 'Nước dưỡng tóc tinh dầu bưởi', 165000, 1),
(7, 3, 1, 'Nước bí đao cân bằng da', 255000, 1);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `price` decimal(12,0) NOT NULL,
  `image` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category_id` int(11) NOT NULL,
  `ingredients` text DEFAULT NULL,
  `usage_guide` text DEFAULT NULL,
  `volume` varchar(50) DEFAULT NULL,
  `stock` int(11) NOT NULL DEFAULT 100,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `products`
--

INSERT INTO `products` (`id`, `name`, `price`, `image`, `description`, `category_id`, `ingredients`, `usage_guide`, `volume`, `stock`, `is_featured`, `created_at`) VALUES
(1, 'Nước bí đao cân bằng da', 255000, 'Nuoc_bi_dao_can_bang_da.jpg', 'Toner bí đao giúp làm sạch sâu, cân bằng độ pH, se khít lỗ chân lông và giảm dầu thừa. Phù hợp da dầu, da mụn.', 1, 'Chiết xuất bí đao, rau má, tràm trà, niacinamide (vitamin B3)', 'Sau bước rửa mặt, cho một lượng vừa đủ ra bông tẩy trang và lau nhẹ toàn mặt, dùng sáng và tối.', '310ml', 120, 1, '2026-10-07 02:09:34'),
(2, 'Gel bí đao rửa mặt', 145000, 'Gel_bi_dao_rua_mat.jpg', 'Sữa rửa mặt dạng gel dịu nhẹ, làm sạch bụi bẩn và dầu thừa mà không làm khô căng da, hỗ trợ giảm mụn.', 1, 'Chiết xuất bí đao, rau má, tràm trà, chất làm sạch dịu nhẹ', 'Làm ướt mặt, lấy một lượng gel vừa đủ tạo bọt, massage nhẹ 30-60 giây rồi rửa sạch với nước.', '140ml', 150, 0, '2026-10-07 02:09:34'),
(3, 'Tinh chất bí đao N15', 275000, 'Tinh_chat_bi_dao.jpg', 'Serum chứa 15% niacinamide giúp giảm mụn, mờ thâm, kiểm soát dầu và làm đều màu da.', 1, 'Niacinamide 15%, chiết xuất bí đao, rau má', 'Sau bước toner, lấy 2-3 giọt thoa đều lên mặt. Dùng buổi tối, ban ngày nhớ dùng kem chống nắng.', '70ml', 80, 0, '2026-10-07 02:09:34'),
(4, 'Nước tẩy trang hoa hồng', 185000, 'Nuoc_tay_trang_hoa_hong.jpg', 'Nước tẩy trang dạng micellar làm sạch lớp trang điểm và kem chống nắng, cấp ẩm, dịu nhẹ cho da nhạy cảm.', 1, 'Nước cất hoa hồng, micellar, glycerin', 'Thấm nước tẩy trang ra bông, đặt lên da vài giây rồi lau nhẹ. Rửa lại bằng sữa rửa mặt.', '310ml', 100, 0, '2026-10-07 02:09:34'),
(5, 'Mặt nạ nghệ Hưng Yên', 155000, 'Mat_na_nghe.jpg', 'Mặt nạ nghệ giúp da sáng mịn, đều màu và hỗ trợ làm mờ vết thâm sau mụn.', 1, 'Nghệ Hưng Yên, cao đất sét, mật ong', 'Thoa một lớp mỏng lên da sạch, để 10-15 phút rồi rửa sạch. Dùng 2-3 lần mỗi tuần.', '100ml', 90, 0, '2026-10-07 02:09:34'),
(6, 'Son dưỡng dầu dừa Bến Tre', 45000, 'Son_duong_dau_dua.jpg', 'Son dưỡng giúp môi mềm mịn, giảm khô nứt nẻ, an toàn cho cả môi nhạy cảm.', 1, 'Dầu dừa Bến Tre, bơ hạt mỡ, sáp thực vật', 'Thoa trực tiếp lên môi khi cần, đặc biệt trước khi ngủ.', '5g', 200, 0, '2026-10-07 02:09:34'),
(7, 'Nước dưỡng tóc tinh dầu bưởi', 165000, 'Nuoc_duong_toc_tinh_dau_buoi.jpg', 'Sản phẩm bán chạy nhất của Cocoon: giảm gãy rụng, kích thích mọc tóc, giúp tóc chắc khỏe và bóng mượt.', 2, 'Tinh dầu vỏ bưởi, vitamin B5, xylishine, baicapil', 'Xịt trực tiếp lên da đầu khi tóc khô hoặc hơi ẩm, massage nhẹ. Không cần gội lại. Dùng 2 lần/ngày.', '140ml', 200, 1, '2026-10-07 02:09:34'),
(8, 'Dầu gội bưởi không sulfate', 195000, 'Dau_goi_buoi.jpg', 'Dầu gội không chứa sulfate, làm sạch dịu nhẹ, giảm gãy rụng và giúp tóc bồng bềnh.', 2, 'Tinh dầu bưởi, vitamin B5, chất làm sạch dịu nhẹ không sulfate', 'Làm ướt tóc, lấy lượng vừa đủ massage da đầu 2-3 phút rồi xả sạch với nước.', '310ml', 120, 0, '2026-10-07 02:09:34'),
(9, 'Dầu xả bưởi', 195000, 'Dau_xa_buoi.jpg', 'Dầu xả giúp tóc mềm mượt, dễ chải, giảm xơ rối và gãy rụng.', 2, 'Tinh dầu bưởi, vitamin B5, dầu thực vật', 'Sau khi gội, thoa đều lên thân và ngọn tóc, để 2-3 phút rồi xả sạch.', '310ml', 110, 0, '2026-10-07 02:09:34'),
(10, 'Cà phê Đắk Lắk làm sạch da chết cơ thể', 125000, 'Ca_phe_dak_lak.jpg', 'Tẩy da chết toàn thân từ hạt cà phê Đắk Lắk xay nhuyễn, giúp da mịn màng, đều màu và tràn đầy năng lượng.', 3, 'Hạt cà phê Đắk Lắk, bơ ca cao, dầu dừa, vitamin E', 'Sau khi tắm, lấy lượng vừa đủ massage nhẹ nhàng lên da ướt 1-2 phút, tránh vùng mắt, rồi tắm sạch. Dùng 2-3 lần mỗi tuần.', '200ml', 180, 1, '2026-10-07 02:09:34'),
(11, 'Bơ dưỡng thể cà phê Đắk Lắk', 245000, 'Bo_duong_the_cafe.png', 'Bơ dưỡng thể giàu ẩm, giúp da mềm mịn, săn chắc và lưu hương cà phê dễ chịu.', 3, 'Cà phê Đắk Lắk, bơ ca cao, bơ hạt mỡ, caffeine', 'Sau khi tắm, thoa một lượng vừa đủ lên toàn thân và massage đến khi thấm.', '200ml', 90, 0, '2026-10-07 02:09:34'),
(12, 'Cà phê Đắk Lắk làm sạch da chết môi', 75000, 'Son_moi_cafe.jpg', 'Tẩy da chết môi giúp loại bỏ da khô bong tróc, giúp môi mềm và lên màu son đẹp hơn.', 3, 'Cà phê Đắk Lắk, đường, dầu dừa, bơ ca cao', 'Lấy một lượng nhỏ massage nhẹ lên môi 30 giây rồi lau sạch. Dùng 1-2 lần mỗi tuần.', '5g', 150, 0, '2026-10-07 02:09:34'),
(13, 'Sữa tắm bí đao', 175000, 'Gel_tam_bi_dao.jpg', 'Sữa tắm dịu nhẹ giúp làm sạch, hỗ trợ giảm mụn lưng và giữ ẩm cho da.', 3, 'Chiết xuất bí đao, rau má, tràm trà', 'Tạo bọt với bông tắm, thoa toàn thân rồi tắm sạch với nước.', '310ml', 100, 0, '2026-10-07 02:09:34');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(10) NOT NULL DEFAULT 'customer',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `username`, `full_name`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'Quản trị viên', 'admin@cocoon.vn', '0900000000', '$2y$10$bVi0O3IgWRopPw5O1ghPP.LhaxkXrvR5.EsObLrojVFznoolP8yye', 'admin', '2026-10-07 02:09:34'),
(2, 'khach', 'Nguyễn Văn Khách', 'khach@gmail.com', '0912345678', '$2y$10$uN5dgDbEuU2P6qnopw6LXO5V0q3efT28FyceeGFul.XfYdt0eX6tG', 'customer', '2026-10-07 02:09:34');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_orders_user` (`user_id`);

--
-- Chỉ mục cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_items_order` (`order_id`),
  ADD KEY `fk_items_product` (`product_id`);

--
-- Chỉ mục cho bảng `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_products_category` (`category_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);


--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT cho bảng `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT cho bảng `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE SET NULL;

--
-- Các ràng buộc cho bảng `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON UPDATE CASCADE;
-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `user_addresses`
-- (đặt cuối file: phải tạo sau khi bảng users đã có AUTO_INCREMENT,
--  nếu không MariaDB 10.4 của XAMPP báo lỗi khóa ngoại)
--

CREATE TABLE `user_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `label` varchar(80) NOT NULL DEFAULT 'Nhà riêng',
  `recipient_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` varchar(255) NOT NULL,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_user_addresses_user` (`user_id`),
  CONSTRAINT `fk_user_addresses_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
