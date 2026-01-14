<?php 
    // Xử lý POST trước khi output HTML
    require_once('../config/constants.php');
    require_once('partials/login-check.php');

    if(isset($_POST['submit'])){
        $id        = (int)$_POST['id'];
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');

        // Validation cơ bản
        $errors = [];
        if ($full_name === '') {
            $errors[] = 'Họ tên không được để trống.';
        }
        if ($phone === '') {
            $errors[] = 'Số điện thoại không được để trống.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Email không hợp lệ.';
        }

        // Kiểm tra trùng email / số điện thoại (ngoại trừ chính admin đang sửa)
        $check_sql = "SELECT id FROM tbl_admin WHERE (email = ? OR phone = ?) AND id <> ?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "ssi", $email, $phone, $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $errors[] = 'Email hoặc số điện thoại đã được sử dụng bởi tài khoản khác.';
        }
        mysqli_stmt_close($stmt);

        if (!empty($errors)) {
            $_SESSION['update'] = "<div class='error'>".implode('<br>', array_map('htmlspecialchars', $errors))."</div>";
            header('location: '.SITEURL.'admin/update-admin.php?id='.$id);
            exit();
        }

        // Cập nhật thông tin
        // Yêu cầu bảng tbl_admin có cột `phone`
        $sql = "UPDATE tbl_admin SET full_name = ?, email = ?, phone = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $full_name, $email, $phone, $id);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        if($res){
            $_SESSION['update'] = "<div class='success'>Cập nhật quản trị viên thành công!</div>";
        } else {
            $_SESSION['update'] = "<div class='error'>Cập nhật quản trị viên thất bại!</div>";
        }
        header('location: '.SITEURL.'admin/manage-admin.php');
        exit();
    }
    
    // Nếu không phải POST, load thông tin admin để hiển thị form
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id <= 0) {
        header('location: '.SITEURL.'admin/manage-admin.php');
        exit();
    }

    // Lấy thêm số điện thoại (cột phone)
    $sql = "SELECT id, full_name, email, phone FROM tbl_admin WHERE id = ?"; 
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);

    if ($res && mysqli_num_rows($res) === 1) {
        $row       = mysqli_fetch_assoc($res);
        $fullname  = $row['full_name'];
        $email     = $row['email'];
        $phone     = $row['phone'];
    } else {
        header('location: '.SITEURL.'admin/manage-admin.php');
        exit();
    }

    require 'partials/menu.php';
?>

<div class="main-content">
    <div class="wrapper">
        <h1>Cập nhật quản trị viên</h1>

        <br><br>

        <?php
        if (isset($_SESSION['update']) && !empty($_SESSION['update'])) {
            echo $_SESSION['update'];
            unset($_SESSION['update']);
        }
        ?>

        <form action="" method="post">
            <table class="tbn-30">
                <tr>
                    <td>Họ tên: </td>
                    <td><input type="text" name="full_name" value="<?php echo htmlspecialchars($fullname); ?>" required></td>
                </tr>
                <tr>
                    <td>Email: </td>
                    <td><input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required></td>
                </tr>
                <tr>
                    <td>Số điện thoại: </td>
                    <td><input type="tel" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="submit" name="submit" value="Cập nhật" class="btn-secondary">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

<?php require 'partials/footer.php' ?>