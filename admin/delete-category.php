<?php 
    include('../config/constants.php');
    
    if(isset($_GET['id']) AND isset($_GET['image_name'])){
        $id = $_GET['id'];
        $image_name = $_GET['image_name'];

        if($image_name!= ""){
            $path = "../image/category/".$image_name;
            // Check if file exists before trying to delete
            if(file_exists($path)){
                $remove = unlink($path);
                if($remove==false){
                    // Image deletion failed, but continue with database deletion
                    $_SESSION['remove'] = "<div class='error'>Xóa hình ảnh danh mục thất bại</div>";
                }
            }
        }
        $sql = "DELETE FROM tbl_category WHERE id=$id";
        $res = mysqli_query($conn, $sql);
        if($res==TRUE){
            $_SESSION['delete'] = "<div class ='success'>Xóa danh mục thành công!</div>";
            header('location:'.SITEURL.'admin/manage-category.php');
        }
        else{
            $_SESSION['delete'] = "<div class='error'>Xóa danh mục thất bại. Vui lòng thử lại!</div>";
            header('location:'.SITEURL.'admin/manage-category.php');
        }
    }
    else{
        header('location:'.SITEURL.'admin/manage-category.php');
    }

?>