-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th6 10, 2026 lúc 05:27 PM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `chat`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chat_targets`
--

CREATE TABLE `chat_targets` (
  `id` varchar(255) NOT NULL,
  `is_group` int(11) NOT NULL DEFAULT 0,
  `group_name` text DEFAULT NULL,
  `is_pinned` int(11) NOT NULL DEFAULT 0,
  `pinned_by` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `chat_targets`
--

INSERT INTO `chat_targets` (`id`, `is_group`, `group_name`, `is_pinned`, `pinned_by`) VALUES
('g1', 1, 'GROUP ĐỒ ÁN IT', 0, ''),
('g_1779937408', 1, 'gt', 0, ''),
('g_1779937507', 1, 'qwe', 0, NULL),
('u1', 0, NULL, 1, NULL),
('u2', 0, NULL, 1, 'u1'),
('u3', 0, NULL, 0, NULL),
('u4', 0, NULL, 0, 'u1');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `messages`
--

CREATE TABLE `messages` (
  `id` varchar(255) NOT NULL,
  `sender_id` varchar(255) NOT NULL,
  `receiver_id` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `timestamp` bigint(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `messages`
--

INSERT INTO `messages` (`id`, `sender_id`, `receiver_id`, `content`, `timestamp`) VALUES
('m1', 'u2', 'u2', 'Gửi cho tớ xin file báo cáo với nhé!', 1778436900000),
('m2', 'u1', 'u1', 'Bạn: Ok, tớ vừa push lên Github rồi đó.', 1778430000000),
('m3', 'u3', 'u3', 'Hôm nay họp nhóm lúc mấy giờ thế?', 1778379600000),
('m4', 'u4', 'u4', 'Cảm ơn bạn nhiều nhé!', 1777947600000),
('m5', 'u6', 'g1', 'Cô giáo vừa dặn nộp bài trước thứ 6 nha cả nhà.', 1777515600000);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `users`
--

CREATE TABLE `users` (
  `id` varchar(255) NOT NULL,
  `name` text NOT NULL,
  `avatar` text NOT NULL,
  `is_active` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `users`
--

INSERT INTO `users` (`id`, `name`, `avatar`, `is_active`) VALUES
('u1', 'TRẦN ĐỨC MẠNH', 'avatar_manh.png', 1),
('u2', 'LÊ NGỌC HOÀNG ANH', 'avatar_hoanganh.png', 1),
('u3', 'NGUYỄN VÂN A', 'avatar_vana.png', 0),
('u4', 'PHẠM MINH B', 'avatar_minhb.png', 1),
('u5', 'LÊ TRANG', 'avatar_trang.png', 1),
('u6', 'VŨ TUẤN', 'avatar_tuan.png', 0);

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `chat_targets`
--
ALTER TABLE `chat_targets`
  ADD PRIMARY KEY (`id`);

--
-- Chỉ mục cho bảng `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sender_id` (`sender_id`),
  ADD KEY `receiver_id` (`receiver_id`);

--
-- Chỉ mục cho bảng `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `chat_targets` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
