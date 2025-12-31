<?php include('partials/menu.php'); ?>

<div class = "main-content">
    <div class = "wrapper">
        <h1>Thêm quản trị viên</h1>
        <br><br>

        <form action = "" method = "POST">
            <table class = "tbn-30">
                <tr>
                    <td>Họ tên: </td>
                    <td><input type = "text" name = "full_name" placeholder = "Nhập họ tên" required></td>
                </tr>
                <tr>
                    <td>Email: </td>
                    <td><input type = "email" name = "email" placeholder = "Nhập email" required></td>
                </tr>
                <tr>
                    <td>Tên đăng nhập: </td>
                    <td><input type = "text" name = "username" placeholder = "Tên đăng nhập" required></td>
                </tr>
                <tr>
                    <td>Mật khẩu: </td>
                    <td><input type = "password" name = "password" placeholder = "Mật khẩu" required minlength="6"></td>
                </tr>
                <tr>
                    <td>Xác nhận mật khẩu: </td>
                    <td><input type = "password" name = "confirm_password" placeholder = "Xác nhận mật khẩu" required></td>
                </tr>
                <tr>
                    <td>Số điện thoại: </td>
                    <td><input type = "tel" name = "phone" placeholder = "Số điện thoại (Tùy chọn)"></td>
                </tr>
                <tr>
                    <td>Địa chỉ: </td>
                    <td><textarea name = "address" placeholder = "Địa chỉ (Tùy chọn)" rows="3"></textarea></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submit" value="Thêm quản trị viên" class = "btn-secondary">
                    </td>
                </tr>
            </table>
        </form>
    </div>
</div>

<?php include('partials/footer.php'); ?>

<?php 
    // Process the value from form and save it in database
    
    // Check whether the submit is clicked or not 
    
    if(isset($_POST['submit'])){
        $fullname = mysqli_real_escape_string($conn, $_POST['full_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        $phone = isset($_POST['phone']) ? mysqli_real_escape_string($conn, $_POST['phone']) : '';
        $address = isset($_POST['address']) ? mysqli_real_escape_string($conn, $_POST['address']) : '';
        
        // Validate password match
        if($password !== $confirm_password){
            $_SESSION['add'] = "<div class='error'>Mật khẩu không khớp!</div>";
            header('location:'.SITEURL.'admin/add-admin.php');
            exit();
        }
        
        // Validate password length
        if(strlen($password) < 6){
            $_SESSION['add'] = "<div class='error'>Mật khẩu phải có ít nhất 6 ký tự!</div>";
            header('location:'.SITEURL.'admin/add-admin.php');
            exit();
        }
        
        // Check if email or username already exists
        $check_sql = "SELECT * FROM tbl_admin WHERE email=? OR username=?";
        $stmt = mysqli_prepare($conn, $check_sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if(mysqli_num_rows($result) > 0){
            mysqli_stmt_close($stmt);
            $_SESSION['add'] = "<div class='error'>Email hoặc tên đăng nhập đã tồn tại!</div>";
            header('location:'.SITEURL.'admin/add-admin.php');
            exit();
        }
        mysqli_stmt_close($stmt);
        
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new admin
        $sql = "INSERT INTO tbl_admin SET
            full_name=?,
            email=?,
            username=?,
            password=?
        ";
        
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $fullname, $email, $username, $hashed_password);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    
        // Check insert or not 
        if($res==TRUE){
            //Create a session variable to display message
            $_SESSION['add'] = "<div class='success'>Thêm quản trị viên thành công!</div>";
            //Redirect page to manage admin
            header('location:'.SITEURL.'admin/manage-admin.php');
        }
        else{
            //Create a session variable to display message
            $_SESSION['add'] = "<div class='error'>Thêm quản trị viên thất bại!</div>";
            //Redirect page to add admin
            header('location:'.SITEURL.'admin/add-admin.php');
        }
    }
?>
