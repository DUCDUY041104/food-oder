
<?php 
    include('../config/constants.php');
    
    if(isset($_GET['id']) AND isset($_GET['image_name']))
    {
        $id = $_GET['id'];
        $image_name = $_GET['image_name'];

        if($image_name != "")
        {
            $path = "../image/food/".$image_name;
            // Check if file exists before trying to delete
            if(file_exists($path)){
                $remove = unlink($path);
                if($remove == false){
                    // Image deletion failed, but continue with database deletion
                    $_SESSION['upload'] = "<div class='error'>Xóa file hình ảnh món ăn thất bại.</div>";
                }
            }
        }
        $sql = "DELETE FROM tbl_food WHERE id=$id";
        $res = mysqli_query($conn, $sql);
        if($res == TRUE)
        {
            $_SESSION['delete'] = "<div class ='success'>Xóa món ăn thành công!</div>";
            header('location:'.SITEURL.'admin/manage-food.php');
        }
        else
        {
            $_SESSION['delete'] = "<div class='error'>Xóa món ăn thất bại. Vui lòng thử lại!</div>";
            header('location:'.SITEURL.'admin/manage-food.php');
        }
    }
    else
    {
        $_SESSION['unathorize'] = "<div class='error'>Truy cập trái phép.</div>";
        header('location:'.SITEURL.'admin/manage-food.php');
    }

?>