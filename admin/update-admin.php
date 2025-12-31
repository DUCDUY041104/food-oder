<?php 
    // Xử lý POST trước khi output HTML
    if(isset($_POST['submit'])){
        require_once('../config/constants.php');
        require_once('partials/login-check.php');
        
        $id = $_POST['id'];
        $full_name = $_POST['full_name'];
        $username = $_POST['username'];
        
        $sql = "UPDATE tbl_admin SET
        full_name = '$full_name',
        username = '$username'
        WHERE id = '$id'
        ";
        
        $res = mysqli_query($conn, $sql);
        if($res==TRUE){
            $_SESSION['update'] = "<div class ='success'>Cập nhật quản trị viên thành công!</div>";
            header('location: '.SITEURL.'admin/manage-admin.php');
            exit();
        }
        else{
            $_SESSION['update'] = "<div class ='error'>Cập nhật quản trị viên thất bại!</div>";
            header('location: '.SITEURL.'admin/manage-admin.php');
            exit();
        }
    }
    
    // Nếu không phải POST, tiếp tục hiển thị form
    require_once('../config/constants.php');
    require_once('partials/login-check.php');
    
    $id = $_GET['id'];
    $sql = "SELECT * FROM tbl_admin WHERE id = $id"; 
    $res = mysqli_query($conn, $sql);
    
    if ($res == TRUE) {
        $count = mysqli_num_rows($res);
        if ($count == 1) {
            $row = mysqli_fetch_assoc($res);
            $fullname = $row['full_name'];
            $username = $row['username'];
        }
        else{
            header('location: '.SITEURL.'admin/manage-admin.php');
            exit();
        }
    }
    
    require 'partials/menu.php';
?>

<div class="main-content">
    <div class="wrapper">
        <h1>Cập nhật quản trị viên</h1>

        <br><br>

        <form action="" method="post">
            <table class="tbn-30">
                <tr>
                    <td>Họ tên: </td>
                    <td><input type="text" name="full_name" value="<?php echo $fullname; ?>"></td>
                </tr>
                <tr>
                    <td>Tên đăng nhập: </td>
                    <td><input type="text" name="username" value="<?php echo $username; ?>"></td>
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