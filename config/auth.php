<?php
/**
 * Authentication Helper Functions
 * Hàm hỗ trợ phân quyền và kiểm tra đăng nhập
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Kiểm tra xem user có đang đăng nhập không
 * @return bool
 */
function isUserLoggedIn() {
    return isset($_SESSION['user_id']) && !isset($_SESSION['admin_id']);
}

/**
 * Kiểm tra xem admin có đang đăng nhập không
 * @return bool
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']) && !isset($_SESSION['user_id']);
}

/**
 * Kiểm tra xem có bất kỳ ai đang đăng nhập không (user hoặc admin)
 * @return bool
 */
function isLoggedIn() {
    return isUserLoggedIn() || isAdminLoggedIn();
}

/**
 * Lấy user ID hiện tại (chỉ cho user)
 * @return int|null
 */
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
}

/**
 * Lấy admin ID hiện tại (chỉ cho admin)
 * @return int|null
 */
function getCurrentAdminId() {
    return isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : null;
}

/**
 * Lấy username hiện tại (user hoặc admin)
 * @return string|null
 */
function getCurrentUsername() {
    if (isset($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    if (isset($_SESSION['user_full_name'])) {
        return $_SESSION['user_full_name'];
    }
    return null;
}

/**
 * Lấy role hiện tại
 * @return string 'user'|'admin'|null
 */
function getCurrentRole() {
    if (isAdminLoggedIn()) {
        return 'admin';
    }
    if (isUserLoggedIn()) {
        return 'user';
    }
    return null;
}

/**
 * Yêu cầu user phải đăng nhập (chỉ user, không phải admin)
 * Nếu chưa đăng nhập hoặc là admin, sẽ redirect
 */
function requireUserLogin() {
    if (!isUserLoggedIn()) {
        if (isAdminLoggedIn()) {
            $_SESSION['access-denied'] = "Bạn đang đăng nhập bằng tài khoản Admin. Vui lòng đăng xuất và đăng nhập bằng tài khoản User.";
            header('location: ' . SITEURL . 'user/login.php');
            exit();
        }
        $_SESSION['no-login-message'] = "Vui lòng đăng nhập để tiếp tục!";
        header('location: ' . SITEURL . 'user/login.php');
        exit();
    }
}

/**
 * Yêu cầu admin phải đăng nhập (chỉ admin, không phải user)
 * Nếu chưa đăng nhập hoặc là user, sẽ redirect
 */
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        if (isUserLoggedIn()) {
            $_SESSION['access-denied'] = "Bạn không có quyền truy cập trang Admin. Vui lòng đăng nhập bằng tài khoản Admin.";
            header('location: ' . SITEURL . 'admin/login.php');
            exit();
        }
        $_SESSION['no-login-message'] = "Vui lòng đăng nhập bằng tài khoản Admin để truy cập Admin Panel.";
        header('location: ' . SITEURL . 'admin/login.php');
        exit();
    }
}

/**
 * Yêu cầu bất kỳ ai đăng nhập (user hoặc admin)
 */
function requireLogin() {
    if (!isLoggedIn()) {
        $_SESSION['no-login-message'] = "Vui lòng đăng nhập để tiếp tục!";
        header('location: ' . SITEURL . 'user/login.php');
        exit();
    }
}

/**
 * Đăng xuất user (chỉ clear session của user)
 */
function logoutUser() {
    unset($_SESSION['user_id']);
    unset($_SESSION['user']);
    unset($_SESSION['user_full_name']);
    // Không xóa admin session nếu có
}

/**
 * Đăng xuất admin (chỉ clear session của admin)
 */
function logoutAdmin() {
    unset($_SESSION['admin_id']);
    unset($_SESSION['user']); // Admin cũng dùng session['user']
    // Không xóa user session nếu có
}

/**
 * Đăng xuất hoàn toàn (clear tất cả session)
 */
function logoutAll() {
    session_destroy();
    session_start();
}

/**
 * Set session cho user sau khi đăng nhập thành công
 */
function setUserSession($user_data) {
    $_SESSION['user_id'] = $user_data['id'];
    $_SESSION['user'] = $user_data['username'] ?? $user_data['full_name'];
    $_SESSION['user_full_name'] = $user_data['full_name'] ?? $user_data['username'];
    // Đảm bảo không có admin session
    unset($_SESSION['admin_id']);
}

/**
 * Set session cho admin sau khi đăng nhập thành công
 */
function setAdminSession($admin_data) {
    $_SESSION['admin_id'] = $admin_data['id'];
    $_SESSION['user'] = $admin_data['username'];
    // Đảm bảo không có user session
    unset($_SESSION['user_id']);
    unset($_SESSION['user_full_name']);
}

