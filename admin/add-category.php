<?php require 'partials/menu.php' ?>

<div class="main-content">
    <div class="wrapper">
        <h1>Thêm danh mục</h1>
        
        <br><br>
        <!-- add category form starts -->
        <form action="" method="post" enctype="multipart/form-data">
            <table class="tbn-30">
                <tr>
                    <td>Tên danh mục: </td>
                    <td><input type="text" name="title" placeholder="Tên danh mục"></td>
                </tr>
                <tr>
                    <td>Chọn hình ảnh: </td>
                    <td>
                        <input type="file" name="image" id="">
                    </td>
                </tr>
                <tr>
                    <td>Nổi bật: </td>
                    <td>
                        <input type="radio" name="featured" id="" value="Yes">Có
                        <input type="radio" name="featured" id="" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td>Hoạt động: </td>
                    <td>
                        <input type="radio" name="active" id="" value="Yes">Có
                        <input type="radio" name="active" id="" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Thêm danh mục" name="submit" class="btn-secondary">
                    </td>
                </tr>
            </table>
        </form>

<?php 
    if(isset($_POST['submit'])){
        $title = $_POST['title'];
        if(isset($_POST['featured'])){
            $featured = $_POST['featured'];
        }
        else {
            $featured = "No";
        }
        if(isset($_POST['active'])){
            $active = $_POST['active'];
        }
        else {
            $active = "No";
        }
        if(isset($_FILES['image']['name'])){
            $image_name = $_FILES['image']['name'];
            if($image_name!=""){
                $image_parts = explode('.', $image_name);
                $ext = end($image_parts);
                $image_name = "Food_Category_".rand(000, 999).'.'.$ext;
                $source_path = $_FILES['image']['tmp_name'];
                $destination_path = "../image/category/" . $image_name;
                $upload = move_uploaded_file($source_path, $destination_path);
                if($upload==false){
                    $_SESSION['upload'] = "<div class='error'>Tải hình ảnh thất bại</div>";
                    header('location:add-category.php');
                    die();
                }
            }
            
        }
        else{
            $image_name="";
        }
        $sql = "INSERT INTO tbl_category SET title='$title', image_name='$image_name', featured='$featured', active='$active'";
        $res = mysqli_query($conn, $sql);
        if($res==TRUE){
            $_SESSION['add'] = "<div class='success'>Thêm danh mục thành công!</div>";
            header('location:manage-category.php');
        }
        else{
            $_SESSION['add'] = "<div class='error'>Thêm danh mục thất bại!</div>";
            header('location:add-category.php');
        }
    }
?>


        <!-- add category form ends -->
    </div>
</div>

<?php require 'partials/footer.php' ?>