<?php
require_once('partials/menu.php');
?>

<!-- Main Content Section Starts -->
<div class="main-content">
    <div class="wrapper">
        <h1>Quản lý người dùng</h1>

        <br/>
        <p>Xem thông tin tài khoản và hoạt động đặt hàng / trao đổi của người dùng.</p>
        <br/>

        <table class="tbl-full">
            <tr>
                <th>STT</th>
                <th>Họ tên</th>
                <th>Tên đăng nhập</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Số đơn hàng</th>
                <th>Tổng chi tiêu</th>
                <th>Đơn gần nhất</th>
                <th>Ngày tạo</th>
            </tr>

            <?php
            // Lấy danh sách người dùng kèm thống kê hoạt động
            $sql = "
                SELECT 
                    u.id,
                    u.full_name,
                    u.username,
                    u.email,
                    u.phone,
                    u.status,
                    u.created_at,
                    COALESCE(o.total_orders, 0) AS total_orders,
                    COALESCE(o.total_amount, 0) AS total_amount,
                    o.last_order_date,
                    c.last_chat_at
                FROM tbl_user u
                LEFT JOIN (
                    SELECT 
                        user_id,
                        COUNT(*) AS total_orders,
                        SUM(total) AS total_amount,
                        MAX(order_date) AS last_order_date
                    FROM tbl_order
                    GROUP BY user_id
                ) AS o ON u.id = o.user_id
                LEFT JOIN (
                    SELECT 
                        user_id,
                        MAX(created_at) AS last_chat_at
                    FROM tbl_chat
                    WHERE user_id IS NOT NULL
                    GROUP BY user_id
                ) AS c ON u.id = c.user_id
                ORDER BY u.created_at DESC
            ";

            $res = mysqli_query($conn, $sql);
            if ($res) {
                $sn = 1;
                if (mysqli_num_rows($res) > 0) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $id = $row['id'];
                        $full_name = $row['full_name'];
                        $username = $row['username'];
                        $email = $row['email'];
                        $phone = $row['phone'];
                        $total_orders = (int)$row['total_orders'];
                        $total_amount = (float)$row['total_amount'];
                        $last_order_date = $row['last_order_date'];
                        $last_chat_at = $row['last_chat_at']; // hiện không hiển thị nhưng vẫn có thể dùng sau này
                        $created_at = $row['created_at'];
                        ?>
                        <tr>
                            <td><?php echo $sn++; ?></td>
                            <td><?php echo htmlspecialchars($full_name); ?></td>
                            <td><?php echo htmlspecialchars($username); ?></td>
                            <td><?php echo htmlspecialchars($email); ?></td>
                            <td><?php echo htmlspecialchars($phone); ?></td>
                            <td><?php echo $total_orders; ?></td>
                            <td><?php echo number_format($total_amount, 0, ',', '.'); ?> đ</td>
                            <td>
                                <?php
                                if (!empty($last_order_date)) {
                                    echo date('d/m/Y H:i', strtotime($last_order_date));
                                } else {
                                    echo '<span class="error">Chưa có</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($created_at)); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    ?>
                    <tr>
                        <td colspan="9" class="error">Chưa có người dùng nào.</td>
                    </tr>
                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="9" class="error">Lỗi khi tải danh sách người dùng: <?php echo htmlspecialchars(mysqli_error($conn)); ?></td>
                </tr>
                <?php
            }
            ?>
        </table>
    </div>
</div>
<!-- Main Content Section Ends -->

<?php include('partials/footer.php'); ?>


