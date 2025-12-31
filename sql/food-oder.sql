-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th12 31, 2025 lúc 06:15 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `food-oder`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbl_admin`
--

CREATE TABLE `tbl_admin` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `username` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbl_admin`
--

INSERT INTO `tbl_admin` (`id`, `full_name`, `email`, `username`, `password`) VALUES
(14, 'Administrator', 'admin@wowfood.com', 'admin', 'admin123'),
(15, 'Bùi Đức Duy', 'buiducduy095@gmail.com', 'Đức Duy', '$2y$10$b6TwzMayV8a0w5SH4xjLYu.Lwxq6432m03As1vRMUUioGxmrML5gu');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbl_category`
--

CREATE TABLE `tbl_category` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(100) NOT NULL,
  `featured` varchar(10) NOT NULL,
  `active` varchar(10) NOT NULL,
  `image_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbl_category`
--

INSERT INTO `tbl_category` (`id`, `title`, `featured`, `active`, `image_name`) VALUES
(29, 'Pizza', 'Yes', 'Yes', 'Food_Category_735.jpg'),
(30, 'Buger', 'Yes', 'Yes', 'Food_Category_381.jpg'),
(31, 'Momo', 'Yes', 'Yes', 'Food_Category_438.jpg'),
(36, 'Chicken ', 'Yes', 'Yes', 'Food_Category_356.jpg');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbl_chat`
--

CREATE TABLE `tbl_chat` (
  `id` int(10) UNSIGNED NOT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `admin_id` int(10) UNSIGNED DEFAULT NULL,
  `sender_type` enum('user','admin') NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tbl_chat`
--

INSERT INTO `tbl_chat` (`id`, `user_id`, `admin_id`, `sender_type`, `message`, `is_read`, `created_at`) VALUES
(1, 3, NULL, 'user', 'alo', 1, '2025-12-31 10:59:59'),
(2, 3, 15, 'admin', 'sao vậy', 1, '2025-12-31 11:00:32'),
(3, 3, NULL, 'user', 'đơn hàng của tôi bị hỏng yêu cầu hoàn hàng', 1, '2025-12-31 11:01:27'),
(4, 4, NULL, 'user', 'alo', 1, '2025-12-31 11:02:49'),
(5, 4, 15, 'admin', 'sao vậy', 1, '2025-12-31 11:05:59'),
(6, 4, NULL, 'user', 'thôi không cần nữa vấn đề của tôi đã được giải quyết', 1, '2025-12-31 11:08:10'),
(7, 3, NULL, 'user', 'Xin chào, tôi cần hỗ trợ về Mã đơn hàng: ORD202512316B38F4 alo', 1, '2025-12-31 11:23:47'),
(8, 4, NULL, 'user', 'Xin chào, tôi cần hỗ trợ về Mã đơn hàng: ORD202512316334F2 cho tôi một cay và ba cái không cay', 1, '2025-12-31 11:25:45'),
(9, 3, 15, 'admin', 'ORD202512316B38F4 đơn hàng này đã được chúng tôi giao tới nơi', 1, '2025-12-31 11:42:11'),
(10, 4, 15, 'admin', 'chúng tôi đã giao đơn hàng tới nơi', 0, '2025-12-31 11:42:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbl_food`
--

CREATE TABLE `tbl_food` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `description` varchar(255) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `image_name` varchar(255) NOT NULL,
  `category_id` int(10) UNSIGNED NOT NULL,
  `featured` varchar(10) NOT NULL,
  `active` varchar(10) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbl_food`
--

INSERT INTO `tbl_food` (`id`, `title`, `description`, `price`, `image_name`, `category_id`, `featured`, `active`) VALUES
(50, 'Pizaa', 'Pizaa là một trong những thực phẩm phổ biến nhất trên thế giới. Pizza được bán tại nhiều nhà hàng', 12.00, 'Food-name-1319.jpg', 29, 'Yes', 'Yes'),
(52, 'Burger ', 'Các loại bánh kẹp có thịt xay, thịt gà, cá, hay cả các món chay ở giữa, nhưng vẫn có lát mì hình tròn.', 10.00, 'Food-name-4266.jpg', 30, 'Yes', 'Yes'),
(54, 'Momo', 'nguồn gốc xuất xứ từ nepal ', 11.00, 'Food-name-6706.jpg', 31, 'Yes', 'Yes'),
(60, 'Cánh gà chiên ', '', 5.00, 'Food-name-903.cms', 36, 'Yes', 'Yes');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbl_order`
--

CREATE TABLE `tbl_order` (
  `id` int(10) UNSIGNED NOT NULL,
  `order_code` varchar(20) DEFAULT NULL,
  `user_id` int(10) UNSIGNED DEFAULT NULL,
  `food` varchar(150) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `qty` int(11) NOT NULL,
  `total` decimal(10,2) NOT NULL,
  `order_date` datetime NOT NULL,
  `status` varchar(50) NOT NULL,
  `customer_name` varchar(150) NOT NULL,
  `customer_contact` varchar(20) NOT NULL,
  `customer_email` varchar(150) NOT NULL,
  `customer_address` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci;

--
-- Đang đổ dữ liệu cho bảng `tbl_order`
--

INSERT INTO `tbl_order` (`id`, `order_code`, `user_id`, `food`, `price`, `qty`, `total`, `order_date`, `status`, `customer_name`, `customer_contact`, `customer_email`, `customer_address`) VALUES
(24, 'ORD20251231D0013B', 3, 'Pizaa', 12.00, 1, 12.00, '2025-12-31 05:23:09', 'Delivered', 'Nguyễn Thế Phong ', '0983224809', 'buiducduy0848@gmail.com', 'ĐT - BTL - HÀ NỘI '),
(25, 'ORD202512316B38F4', 3, 'Momo', 11.00, 1, 11.00, '2025-12-31 05:23:34', 'Delivered', 'Nguyễn Thế Phong ', '0983224809', 'buiducduy0848@gmail.com', 'ĐT - BTL - HÀ NỘI '),
(26, 'ORD202512316334F2', 4, 'Cánh gà chiên', 5.00, 5, 25.00, '2025-12-31 05:25:26', 'Delivered', 'Ngụy Hữu Phúc', '0983224809', 'buiducduy3005@gmail.com', 'Mỹ Đình - Hà Nội ');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tbl_user`
--

CREATE TABLE `tbl_user` (
  `id` int(10) UNSIGNED NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `status` varchar(10) DEFAULT 'Active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tbl_user`
--

INSERT INTO `tbl_user` (`id`, `full_name`, `username`, `password`, `email`, `phone`, `address`, `status`, `created_at`) VALUES
(3, 'Nguyễn Thế Phong ', 'buiducduy0848', '$2y$10$4qjp.sjsWIE/qmShrPOkgOImEK3fqGglkQgXvm/07i/rMmx8HHGrC', 'buiducduy0848@gmail.com', '0983224809', 'ĐT - BTL - HÀ NỘI ', 'Active', '2025-12-31 09:20:44'),
(4, 'Ngụy Hữu Phúc', 'buiducduy3005', '$2y$10$veoTZt1y.2sJR2UqKCvphu.XPA2hivrjdlu892dzs4sj.38K.zxoC', 'buiducduy3005@gmail.com', '0983224809', 'Mỹ Đình - Hà Nội ', 'Active', '2025-12-31 09:23:37');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `tbl_admin`
--
ALTER TABLE `tbl_admin`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `tbl_category`
--
ALTER TABLE `tbl_category`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `tbl_chat`
--
ALTER TABLE `tbl_chat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Chỉ mục cho bảng `tbl_food`
--
ALTER TABLE `tbl_food`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `tbl_order`
--
ALTER TABLE `tbl_order`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_code` (`order_code`),
  ADD KEY `idx_order_code` (`order_code`),
  ADD KEY `idx_user_id` (`user_id`);

--
-- Chỉ mục cho bảng `tbl_user`
--
ALTER TABLE `tbl_user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `tbl_admin`
--
ALTER TABLE `tbl_admin`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT cho bảng `tbl_category`
--
ALTER TABLE `tbl_category`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT cho bảng `tbl_chat`
--
ALTER TABLE `tbl_chat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT cho bảng `tbl_food`
--
ALTER TABLE `tbl_food`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT cho bảng `tbl_order`
--
ALTER TABLE `tbl_order`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- AUTO_INCREMENT cho bảng `tbl_user`
--
ALTER TABLE `tbl_user`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `tbl_chat`
--
ALTER TABLE `tbl_chat`
  ADD CONSTRAINT `fk_chat_admin` FOREIGN KEY (`admin_id`) REFERENCES `tbl_admin` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_chat_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`id`) ON DELETE CASCADE;

--
-- Các ràng buộc cho bảng `tbl_order`
--
ALTER TABLE `tbl_order`
  ADD CONSTRAINT `fk_order_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
