-- ============================================
-- HỆ THỐNG THANH TOÁN HOÀN CHỈNH
-- ============================================

-- 1. Cập nhật bảng tbl_order để thêm các trường thanh toán
ALTER TABLE `tbl_order` 
ADD COLUMN IF NOT EXISTS `payment_method` VARCHAR(50) DEFAULT 'cash' COMMENT 'cash, online, vnpay, momo, bank',
ADD COLUMN IF NOT EXISTS `payment_status` VARCHAR(50) DEFAULT 'pending' COMMENT 'pending, paid, failed, refunded',
ADD COLUMN IF NOT EXISTS `note` TEXT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `payment_id` INT(10) UNSIGNED DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `expires_at` DATETIME DEFAULT NULL COMMENT 'Thời gian hết hạn thanh toán';

-- 2. Tạo bảng lưu lịch sử thanh toán
CREATE TABLE IF NOT EXISTS `tbl_payment` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(20) NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `payment_method` VARCHAR(50) NOT NULL COMMENT 'vnpay, momo, bank, cash',
  `amount` DECIMAL(10,2) NOT NULL,
  `payment_status` VARCHAR(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, success, failed, cancelled, refunded',
  `transaction_id` VARCHAR(255) DEFAULT NULL COMMENT 'Mã giao dịch từ cổng thanh toán',
  `payment_gateway_response` TEXT DEFAULT NULL COMMENT 'Response từ cổng thanh toán',
  `failure_reason` TEXT DEFAULT NULL COMMENT 'Lý do thất bại',
  `paid_at` DATETIME DEFAULT NULL COMMENT 'Thời gian thanh toán thành công',
  `expires_at` DATETIME DEFAULT NULL COMMENT 'Thời gian hết hạn thanh toán',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_code` (`order_code`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_payment_status` (`payment_status`),
  KEY `idx_transaction_id` (`transaction_id`),
  CONSTRAINT `fk_payment_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Tạo bảng lưu lịch sử hoàn tiền
CREATE TABLE IF NOT EXISTS `tbl_refund` (
  `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order_code` VARCHAR(20) NOT NULL,
  `payment_id` INT(10) UNSIGNED NOT NULL,
  `user_id` INT(10) UNSIGNED NOT NULL,
  `refund_amount` DECIMAL(10,2) NOT NULL,
  `refund_reason` TEXT NOT NULL,
  `refund_status` VARCHAR(50) NOT NULL DEFAULT 'pending' COMMENT 'pending, processing, completed, failed',
  `refund_method` VARCHAR(50) DEFAULT NULL COMMENT 'original, bank_transfer, cash',
  `refund_transaction_id` VARCHAR(255) DEFAULT NULL,
  `processed_by` INT(10) UNSIGNED DEFAULT NULL COMMENT 'Admin ID xử lý',
  `processed_at` DATETIME DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_code` (`order_code`),
  KEY `idx_payment_id` (`payment_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_refund_status` (`refund_status`),
  CONSTRAINT `fk_refund_payment` FOREIGN KEY (`payment_id`) REFERENCES `tbl_payment` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_refund_user` FOREIGN KEY (`user_id`) REFERENCES `tbl_user` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_refund_admin` FOREIGN KEY (`processed_by`) REFERENCES `tbl_admin` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Tạo index cho hiệu suất
CREATE INDEX IF NOT EXISTS `idx_order_payment_status` ON `tbl_order` (`payment_status`);
CREATE INDEX IF NOT EXISTS `idx_order_payment_method` ON `tbl_order` (`payment_method`);

