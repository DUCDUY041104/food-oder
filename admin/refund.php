<?php
include('../config/constants.php');
require_once('partials/login-check.php');

// Xử lý hoàn tiền
if(isset($_POST['process_refund'])) {
    $order_code = $_POST['order_code'] ?? '';
    $refund_amount = floatval($_POST['refund_amount'] ?? 0);
    $refund_reason = mysqli_real_escape_string($conn, $_POST['refund_reason'] ?? '');
    $refund_method = $_POST['refund_method'] ?? 'original';
    
    if(empty($order_code) || $refund_amount <= 0 || empty($refund_reason)) {
        $_SESSION['refund-error'] = "Vui lòng điền đầy đủ thông tin!";
        header('location:'.SITEURL.'admin/refund.php');
        exit();
    }
    
    // Kiểm tra bảng payment có tồn tại không
    $payment_table_exists = false;
    $check_table_sql = "SHOW TABLES LIKE 'tbl_payment'";
    $table_result = mysqli_query($conn, $check_table_sql);
    if($table_result && mysqli_num_rows($table_result) > 0) {
        $payment_table_exists = true;
    }
    
    $payment = null;
    $payment_id = null;
    $user_id_from_order = null;
    
    // Lấy thông tin payment (nếu bảng tồn tại)
    if($payment_table_exists) {
        // Tìm payment với status 'success' hoặc 'paid'
        $payment_sql = "SELECT * FROM tbl_payment WHERE order_code = ? AND payment_status IN ('success', 'paid') ORDER BY id DESC LIMIT 1";
        $stmt = mysqli_prepare($conn, $payment_sql);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $order_code);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $payment = mysqli_fetch_assoc($result);
            mysqli_stmt_close($stmt);
            
            if($payment) {
                $payment_id = $payment['id'];
            }
        }
    }
    
    // Nếu không tìm thấy payment, lấy từ order
    if(!$payment) {
        $order_sql = "SELECT user_id, payment_status, payment_method FROM tbl_order WHERE order_code = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $order_sql);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $order_code);
            mysqli_stmt_execute($stmt);
            $order_result = mysqli_stmt_get_result($stmt);
            $order_data = mysqli_fetch_assoc($order_result);
            mysqli_stmt_close($stmt);
            
            if($order_data) {
                $user_id_from_order = $order_data['user_id'];
                $order_payment_status = $order_data['payment_status'] ?? 'paid';
                
                // Chỉ cho phép hoàn tiền nếu order đã thanh toán
                if($order_payment_status != 'paid' && $order_payment_status != 'success') {
                    $_SESSION['refund-error'] = "Đơn hàng này chưa được thanh toán hoặc đã được hoàn tiền!";
                    header('location:'.SITEURL.'admin/refund.php');
                    exit();
                }
            } else {
                $_SESSION['refund-error'] = "Không tìm thấy đơn hàng với mã: " . htmlspecialchars($order_code);
                header('location:'.SITEURL.'admin/refund.php');
                exit();
            }
        } else {
            $_SESSION['refund-error'] = "Lỗi kết nối database!";
            header('location:'.SITEURL.'admin/refund.php');
            exit();
        }
    }
    
    // Xác định user_id để dùng
    $refund_user_id = $payment ? $payment['user_id'] : $user_id_from_order;
    
    if(!$refund_user_id) {
        $_SESSION['refund-error'] = "Không tìm thấy thông tin khách hàng!";
        header('location:'.SITEURL.'admin/refund.php');
        exit();
    }
    
    // Kiểm tra đã hoàn tiền chưa (nếu có payment_id)
    if($payment_id) {
        $check_refund_sql = "SELECT * FROM tbl_refund WHERE payment_id = ? AND refund_status IN ('pending', 'processing', 'completed')";
        $stmt = mysqli_prepare($conn, $check_refund_sql);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "i", $payment_id);
            mysqli_stmt_execute($stmt);
            $refund_check = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($refund_check) > 0) {
                mysqli_stmt_close($stmt);
                $_SESSION['refund-error'] = "Đơn hàng này đã được xử lý hoàn tiền!";
                header('location:'.SITEURL.'admin/refund.php');
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Kiểm tra theo order_code nếu không có payment_id
        $check_refund_sql = "SELECT * FROM tbl_refund WHERE order_code = ? AND refund_status IN ('pending', 'processing', 'completed')";
        $stmt = mysqli_prepare($conn, $check_refund_sql);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $order_code);
            mysqli_stmt_execute($stmt);
            $refund_check = mysqli_stmt_get_result($stmt);
            if(mysqli_num_rows($refund_check) > 0) {
                mysqli_stmt_close($stmt);
                $_SESSION['refund-error'] = "Đơn hàng này đã được xử lý hoàn tiền!";
                header('location:'.SITEURL.'admin/refund.php');
                exit();
            }
            mysqli_stmt_close($stmt);
        }
    }
    
    // Kiểm tra bảng refund có tồn tại không
    $refund_table_exists = false;
    $check_refund_table_sql = "SHOW TABLES LIKE 'tbl_refund'";
    $refund_table_result = mysqli_query($conn, $check_refund_table_sql);
    if($refund_table_result && mysqli_num_rows($refund_table_result) > 0) {
        $refund_table_exists = true;
    }
    
    // Tạo refund record (nếu bảng tồn tại)
    if($refund_table_exists) {
        $refund_sql = "INSERT INTO tbl_refund (order_code, payment_id, user_id, refund_amount, refund_reason, refund_status, refund_method, processed_by) 
                       VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)";
        $stmt = mysqli_prepare($conn, $refund_sql);
        if($stmt) {
            $admin_id = $_SESSION['admin_id'] ?? null;
            mysqli_stmt_bind_param($stmt, "siidssi", $order_code, $payment_id, $refund_user_id, $refund_amount, $refund_reason, $refund_method, $admin_id);
    
            if(mysqli_stmt_execute($stmt)) {
                $refund_id = mysqli_insert_id($conn);
                mysqli_stmt_close($stmt);
                
                // Cập nhật payment status (nếu có payment_id)
                if($payment_id && $payment_table_exists) {
                    $update_payment_sql = "UPDATE tbl_payment SET payment_status = 'refunded' WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $update_payment_sql);
                    if($stmt) {
                        mysqli_stmt_bind_param($stmt, "i", $payment_id);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
                
                // Cập nhật order status
                $update_order_sql = "UPDATE tbl_order SET payment_status = 'refunded', status = 'Cancelled' WHERE order_code = ?";
                $stmt = mysqli_prepare($conn, $update_order_sql);
                if($stmt) {
                    mysqli_stmt_bind_param($stmt, "s", $order_code);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                } else {
                    // Fallback: chỉ cập nhật status nếu không có cột payment_status
                    $update_order_sql = "UPDATE tbl_order SET status = 'Cancelled' WHERE order_code = ?";
                    $stmt = mysqli_prepare($conn, $update_order_sql);
                    if($stmt) {
                        mysqli_stmt_bind_param($stmt, "s", $order_code);
                        mysqli_stmt_execute($stmt);
                        mysqli_stmt_close($stmt);
                    }
                }
                
                $_SESSION['refund-success'] = "Đã tạo yêu cầu hoàn tiền thành công! ID: " . $refund_id;
            } else {
                $_SESSION['refund-error'] = "Có lỗi xảy ra khi tạo yêu cầu hoàn tiền: " . mysqli_stmt_error($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            $_SESSION['refund-error'] = "Lỗi: Không thể tạo refund record. Bảng tbl_refund có thể chưa tồn tại. Vui lòng chạy file sql/payment_system.sql";
        }
    } else {
        // Nếu không có bảng refund, chỉ cập nhật order
        $update_order_sql = "UPDATE tbl_order SET payment_status = 'refunded', status = 'Cancelled' WHERE order_code = ?";
        $stmt = mysqli_prepare($conn, $update_order_sql);
        if($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $order_code);
            if(mysqli_stmt_execute($stmt)) {
                // Cập nhật payment nếu có
                if($payment_id && $payment_table_exists) {
                    $update_payment_sql = "UPDATE tbl_payment SET payment_status = 'refunded' WHERE id = ?";
                    $stmt2 = mysqli_prepare($conn, $update_payment_sql);
                    if($stmt2) {
                        mysqli_stmt_bind_param($stmt2, "i", $payment_id);
                        mysqli_stmt_execute($stmt2);
                        mysqli_stmt_close($stmt2);
                    }
                }
                $_SESSION['refund-success'] = "Đã cập nhật trạng thái hoàn tiền cho đơn hàng: " . $order_code;
            } else {
                $_SESSION['refund-error'] = "Có lỗi xảy ra khi cập nhật đơn hàng!";
            }
            mysqli_stmt_close($stmt);
        } else {
            $_SESSION['refund-error'] = "Lỗi: Không thể cập nhật đơn hàng. Vui lòng kiểm tra database.";
        }
    }
    
    header('location:'.SITEURL.'admin/refund.php');
    exit();
}

// Xử lý cập nhật trạng thái hoàn tiền
if(isset($_POST['update_refund_status'])) {
    $refund_id = intval($_POST['refund_id'] ?? 0);
    $refund_status = $_POST['refund_status'] ?? 'pending';
    $refund_transaction_id = mysqli_real_escape_string($conn, $_POST['refund_transaction_id'] ?? '');
    
    $update_sql = "UPDATE tbl_refund SET 
        refund_status = ?,
        refund_transaction_id = ?,
        processed_by = ?,
        processed_at = " . ($refund_status == 'completed' ? "NOW()" : "NULL") . ",
        updated_at = NOW()
        WHERE id = ?";
    
    $stmt = mysqli_prepare($conn, $update_sql);
    $admin_id = $_SESSION['admin_id'] ?? null;
    mysqli_stmt_bind_param($stmt, "ssii", $refund_status, $refund_transaction_id, $admin_id, $refund_id);
    
    if(mysqli_stmt_execute($stmt)) {
        $_SESSION['refund-success'] = "Cập nhật trạng thái hoàn tiền thành công!";
    } else {
        $_SESSION['refund-error'] = "Có lỗi xảy ra!";
    }
    mysqli_stmt_close($stmt);
    
    header('location:'.SITEURL.'admin/refund.php');
    exit();
}

require_once('partials/menu.php');
?>

<div class="main-content">
    <div class="wrapper">
        <h1 style="margin-bottom: 8px;">Quản lý hoàn tiền</h1>
        <p style="color:#747d8c;margin-bottom:25px;max-width:600px;">
            Theo dõi và xử lý các yêu cầu hoàn tiền từ khách hàng một cách nhanh chóng, rõ ràng và minh bạch.
        </p>
        
        <?php
        if(isset($_SESSION['refund-success'])) {
            echo '<div class="success">' . $_SESSION['refund-success'] . '</div>';
            unset($_SESSION['refund-success']);
        }
        if(isset($_SESSION['refund-error'])) {
            echo '<div class="error">' . $_SESSION['refund-error'] . '</div>';
            unset($_SESSION['refund-error']);
        }
        ?>
        
        <br>
        
        <!-- Form tạo hoàn tiền -->
        <div style="background:#ffffff;padding:22px 24px;border-radius:14px;margin-bottom:30px;box-shadow:0 4px 14px rgba(0,0,0,0.06);border:1px solid #ecf0f1;max-width:720px;">
            <h2 style="margin-bottom:14px;">Tạo yêu cầu hoàn tiền</h2>
            <p style="color:#95a5a6;font-size:13px;margin-bottom:18px;">
                Dùng form này khi cần tạo yêu cầu hoàn tiền thủ công cho một đơn hàng đã thanh toán.
            </p>
            <form method="POST" action="">
                <table class="tbn-30" style="width:100%;border-collapse:separate;border-spacing:0 10px;font-size:14px;">
                    <tr>
                        <td style="width:160px;color:#2f3542;font-weight:600;">Mã đơn hàng *</td>
                        <td>
                            <input type="text" name="order_code" required placeholder="ORD20251231XXXXXX"
                                   style="width:100%;padding:9px 11px;border:1px solid #dde1e7;border-radius:8px;box-sizing:border-box;font-size:13px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#2f3542;font-weight:600;">Số tiền hoàn *</td>
                        <td>
                            <input type="number" name="refund_amount" step="0.01" min="0" required
                                   style="width:100%;padding:9px 11px;border:1px solid #dde1e7;border-radius:8px;box-sizing:border-box;font-size:13px;">
                        </td>
                    </tr>
                    <tr>
                        <td style="vertical-align:top;color:#2f3542;font-weight:600;">Lý do hoàn tiền *</td>
                        <td>
                            <textarea name="refund_reason" rows="4" required
                                      placeholder="Ví dụ: Khách hàng hủy đơn, sản phẩm lỗi..."
                                      style="width:100%;padding:9px 11px;border:1px solid #dde1e7;border-radius:8px;box-sizing:border-box;font-size:13px;resize:vertical;min-height:90px;"></textarea>
                            <div style="margin-top:4px;font-size:12px;color:#95a5a6;">Mô tả càng chi tiết, việc đối soát sẽ càng nhanh.</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#2f3542;font-weight:600;">Phương thức hoàn</td>
                        <td>
                            <select name="refund_method"
                                    style="width:100%;padding:9px 11px;border:1px solid #dde1e7;border-radius:8px;box-sizing:border-box;font-size:13px;background:#fff;">
                                <option value="original">Hoàn về phương thức gốc</option>
                                <option value="bank_transfer">Chuyển khoản ngân hàng</option>
                                <option value="cash">Tiền mặt</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2" style="padding-top:6px;">
                            <input type="submit" name="process_refund" value="Tạo yêu cầu hoàn tiền"
                                   class="btn-secondary"
                                   style="padding:10px 22px;border-radius:999px;border:1px solid #27ae60;background:#2ecc71;color:#fff;font-weight:600;font-size:13px;cursor:pointer;box-shadow:0 6px 14px rgba(46,204,113,0.35);transition:all 0.15s;">
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        
        <!-- Danh sách hoàn tiền -->
        <h2 style="margin-bottom:10px;">Danh sách yêu cầu hoàn tiền</h2>
        <p style="color:#95a5a6;font-size:13px;margin-bottom:16px;">Các yêu cầu mới nhất sẽ hiển thị ở trên cùng.</p>
        
        <div style="background:#ffffff;border-radius:12px;padding:18px 20px;box-shadow:0 4px 14px rgba(0,0,0,0.06);border:1px solid #ecf0f1;overflow-x:auto;">
        <table class="tbl-full" style="width:100%;border-collapse:separate;border-spacing:0;font-size:13px;">
            <thead>
            <tr style="background:#f8f9fb;">
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:left;">ID</th>
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:left;">Mã đơn hàng</th>
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:left;">Khách hàng</th>
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:right;">Số tiền</th>
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:left;">Lý do</th>
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:left;">Trạng thái</th>
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:left;">Phương thức</th>
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:left;">Ngày tạo</th>
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:left;">Người xử lý</th>
                <th style="padding:10px;border-bottom:1px solid #e0e6ed;text-align:center;">Thao tác</th>
            </tr>
            </thead>
            <tbody>
            <?php
            $refund_sql = "SELECT r.*, a.full_name as admin_name, u.full_name as user_name, u.email as user_email 
                          FROM tbl_refund r 
                          LEFT JOIN tbl_admin a ON r.processed_by = a.id 
                          LEFT JOIN tbl_user u ON r.user_id = u.id 
                          ORDER BY r.id DESC";
            $refund_res = mysqli_query($conn, $refund_sql);
            
            if(mysqli_num_rows($refund_res) > 0) {
                while($refund = mysqli_fetch_assoc($refund_res)) {
                    $status_class = '';
                    $status_text = '';
                    switch($refund['refund_status']) {
                        case 'pending':
                            $status_class = 'style="color: orange;"';
                            $status_text = 'Chờ xử lý';
                            break;
                        case 'processing':
                            $status_class = 'style="color: blue;"';
                            $status_text = 'Đang xử lý';
                            break;
                        case 'completed':
                            $status_class = 'style="color: green;"';
                            $status_text = 'Hoàn thành';
                            break;
                        case 'failed':
                            $status_class = 'style="color: red;"';
                            $status_text = 'Thất bại';
                            break;
                    }
                    ?>
                    <tr>
                        <td style="padding:9px 8px;"><?php echo $refund['id']; ?></td>
                        <td style="padding:9px 8px;font-weight:600;color:#2c3e50;">
                            <?php echo htmlspecialchars($refund['order_code']); ?>
                        </td>
                        <td style="padding:9px 8px;">
                            <?php if($refund['user_name']): ?>
                                <strong><?php echo htmlspecialchars($refund['user_name']); ?></strong><br>
                                <small style="color:#666;"><?php echo htmlspecialchars($refund['user_email'] ?? ''); ?></small>
                            <?php else: ?>
                                <span style="color:#999;">N/A</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:9px 8px;text-align:right;font-weight:600;color:#ff6b81;">
                            <?php echo number_format($refund['refund_amount'], 0, ',', '.'); ?> đ
                        </td>
                        <td style="padding:9px 8px;max-width:240px;word-wrap:break-word;color:#34495e;">
                            <?php echo htmlspecialchars($refund['refund_reason']); ?>
                        </td>
                        <td <?php echo $status_class; ?> style="padding:9px 8px;">
                            <span style="display:inline-block;padding:4px 10px;border-radius:999px;font-size:12px;background:#f1f2f6;">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td style="padding:9px 8px;"><?php echo htmlspecialchars($refund['refund_method'] ?? 'N/A'); ?></td>
                        <td style="padding:9px 8px;"><?php echo date('d/m/Y H:i', strtotime($refund['created_at'])); ?></td>
                        <td style="padding:9px 8px;"><?php echo htmlspecialchars($refund['admin_name'] ?? ($refund['processed_by'] ? 'Đang xử lý' : 'Chờ xử lý')); ?></td>
                        <td style="padding:9px 8px;text-align:center;white-space:nowrap;">
                            <?php if($refund['refund_status'] != 'completed'): ?>
                            <button onclick="openRefundModal(<?php echo $refund['id']; ?>, '<?php echo htmlspecialchars($refund['order_code'], ENT_QUOTES); ?>', '<?php echo $refund['refund_status']; ?>')" 
                                    class="btn-secondary"
                                    style="padding:6px 12px;font-size:12px;border-radius:999px;border:1px solid #3498db;background:#3498ff;color:#fff;font-weight:500;cursor:pointer;transition:all 0.15s;">
                                Cập nhật
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php
                }
            } else {
                echo '<tr><td colspan="10" class="error" style="padding:14px 8px;text-align:center;">Chưa có yêu cầu hoàn tiền nào</td></tr>';
            }
            ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<!-- Modal cập nhật trạng thái hoàn tiền -->
<div id="refundModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);z-index:1000;">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#ffffff;padding:24px 26px;border-radius:14px;max-width:520px;width:92%;box-shadow:0 10px 30px rgba(0,0,0,0.25);border:1px solid #ecf0f1;">
        <h2 style="margin-bottom:14px;">Cập nhật trạng thái hoàn tiền</h2>
        <p style="color:#95a5a6;font-size:13px;margin-bottom:16px;">Chọn trạng thái mới và (nếu có) nhập mã giao dịch hoàn tiền từ cổng thanh toán.</p>
        <form method="POST" action="">
            <input type="hidden" name="refund_id" id="modal_refund_id">
            <table class="tbn-30" style="width:100%;border-collapse:separate;border-spacing:0 10px;font-size:13px;">
                <tr>
                    <td style="width:140px;color:#2f3542;font-weight:600;">Mã đơn hàng</td>
                    <td><strong id="modal_order_code"></strong></td>
                </tr>
                <tr>
                    <td style="color:#2f3542;font-weight:600;">Trạng thái *</td>
                    <td>
                        <select name="refund_status" id="modal_refund_status" required
                                style="width:100%;padding:9px 11px;border:1px solid #dde1e7;border-radius:8px;box-sizing:border-box;font-size:13px;background:#fff;">
                            <option value="pending">Chờ xử lý</option>
                            <option value="processing">Đang xử lý</option>
                            <option value="completed">Hoàn thành</option>
                            <option value="failed">Thất bại</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td style="color:#2f3542;font-weight:600;">Mã giao dịch hoàn tiền</td>
                    <td>
                        <input type="text" name="refund_transaction_id" placeholder="Nếu có"
                               style="width:100%;padding:9px 11px;border:1px solid #dde1e7;border-radius:8px;box-sizing:border-box;font-size:13px;">
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="padding-top:6px;">
                        <input type="submit" name="update_refund_status" value="Cập nhật"
                               class="btn-secondary"
                               style="padding:9px 20px;border-radius:999px;border:1px solid #3498db;background:#3498ff;color:#fff;font-weight:600;font-size:13px;cursor:pointer;box-shadow:0 6px 14px rgba(52,152,219,0.35);transition:all 0.15s;">
                        <button type="button" onclick="closeRefundModal()" class="btn-secondary"
                                style="margin-left:10px;padding:9px 20px;border-radius:999px;border:1px solid #dfe4ea;background:#ffffff;color:#2c3e50;font-size:13px;cursor:pointer;">
                            Hủy
                        </button>
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

<script>
function openRefundModal(refundId, orderCode, currentStatus) {
    document.getElementById('modal_refund_id').value = refundId;
    document.getElementById('modal_order_code').textContent = orderCode;
    document.getElementById('modal_refund_status').value = currentStatus;
    document.getElementById('refundModal').style.display = 'block';
}

function closeRefundModal() {
    document.getElementById('refundModal').style.display = 'none';
}

// Đóng modal khi click bên ngoài
document.getElementById('refundModal').addEventListener('click', function(e) {
    if(e.target === this) {
        closeRefundModal();
    }
});
</script>

<?php include('partials/footer.php'); ?>

