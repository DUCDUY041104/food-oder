<?php 
    include('../config/constants.php');
    
    $id = $_GET['id'];
    $sql = "DELETE FROM tbl_admin WHERE id=$id";
    $res = mysqli_query($conn, $sql);
    if($res==TRUE){
        $_SESSION['delete'] = "<div class ='success'>Xóa quản trị viên thành công!</div>";
        header('location:'.SITEURL.'admin/manage-admin.php');
    }
    else{
        $_SESSION['delete'] = "<div class='error'>Xóa quản trị viên thất bại. Vui lòng thử lại!</div>";
        header('location:'.SITEURL.'admin/manage-admin.php');
    }

?>