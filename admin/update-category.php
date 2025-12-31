<?php require 'partials/menu.php';?>

<div class="main-content">
    <div class="wrapper">
        <h1>Cập nhật danh mục</h1>
        <br><br>


        <?php
            if(isset($_GET['id'])){
                $id = $_GET['id'];
                $sql = "SELECT * FROM tbl_category WHERE id='$id'";
                $res = mysqli_query($conn, $sql);
                $count = mysqli_num_rows($res);
                if($count==1){
                    $row = mysqli_fetch_assoc($res);
                    $title = $row['title'];
                    $current_image = $row['image_name'];
                    $featured = $row['featured'];
                    $active = $row['active'];
                }
                else{
                    $_SESSION['no-category-found'] = "<div class='error'>Không tìm thấy danh mục</div>";
                    header('location: '.SITEURL.'admin/manage-category.php');
                }
            }
            else{
                header('location: '.SITEURL.'admin/manage-category.php');
            }        
        ?>

        <form action="" method="post" enctype="multipart/form-data">
            <table class="tbn-30">
                <tr>
                    <td>Tên danh mục: </td>
                    <td><input type="text" name="title" value="<?php echo $title; ?>"></td>
                </tr>
                <tr>
                    <td>Hình ảnh hiện tại: </td>
                    <td>
                        <?php 
                            if($current_image!=""){
                                ?>
                                <img src="<?php echo SITEURL; ?>images/category/<?php echo $current_image; ?>" width="150px">
                                <?php
                            }
                            else{
                                echo "<div class='error'>Chưa có hình ảnh</div>";
                            }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Hình ảnh mới: </td>
                    <td><input type="file" name="image" id=""></td>
                </tr>
                <tr>
                    <td>Nổi bật: </td>
                    <td>
                        <input <?php if($featured=="Yes"){echo "checked";} ?> type="radio" name="featured" id="" value="Yes">Có
                        <input <?php if($featured=="No"){echo "checked";} ?> type="radio" name="featured" id="" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td>Hoạt động: </td>
                    <td>
                        <input <?php if($active=="Yes"){echo "checked";} ?> type="radio" name="active" id="" value="Yes">Có
                        <input <?php if($active=="No"){echo "checked";} ?> type="radio" name="active" id="" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="current_image" value="<?php echo $current_image; ?>">
                        <input type="submit" value="Cập nhật danh mục" name="submit" class="btn-secondary">
                    </td>
                </tr>
            </table>
        </form>

        <?php
            if(isset($_POST['submit'])){
                $id = $_POST['id'];
                $title = $_POST['title'];
                $current_image = $_POST['current_image'];
                $featured = $_POST['featured'];
                $active = $_POST['active'];
                //new image if selected
                if(isset($_FILES['image']['name'])){
                    $image_name = $_FILES['image']['name'];
                    if($image_name != ""){
                        $image_parts = explode('.', $image_name);
                        $ext = end($image_parts);
                        $image_name = "Food_Category_".rand(000, 999).'.'.$ext;
                        $source_path = $_FILES['image']['tmp_name'];
                        $destination_path = "../image/category/" . $image_name;
                        $upload = move_uploaded_file($source_path, $destination_path);
                        if($upload==false){
                            $_SESSION['upload'] = "<div class='error'>Failed to Upload Image</div>";
                            header('location: '.SITEURL.'admin/manage-category.php');
                            die();
                        }

                        if($current_image!=""){ //Remove the current image if available
                            $remove_path = "../image/category/".$current_image;
                            $remove = unlink($remove_path);
                            if($remove==false){
                                $_SESSION['failed-remove'] = "<div class='error'>Failed to remove current Image</div>";
                                header('location: '.SITEURL.'admin/manage-category.php');
                                die();
                            }
                        }
                    }
                    else{
                        $image_name = $current_image;
                    }
                }
                else{
                    $image_name = $current_image;
                }
                // update database
                $sql2 = "UPDATE tbl_category SET
                        title='$title',
                        image_name='$image_name',
                        featured='$featured',
                        active='$active'
                        WHERE id='$id'
                        ";
                $res = mysqli_query($conn, $sql2);
                if($res==TRUE){
                    $_SESSION['update'] = "<div class='success'>Cập nhật danh mục thành công!</div>";
                    header('location: '.SITEURL.'admin/manage-category.php');
                }
                else{
                    $_SESSION['update'] = "<div class='error'>Cập nhật danh mục thất bại!</div>";
                    header('location: '.SITEURL.'admin/manage-category.php');
                }

            }
        ?>

    </div>
</div>


<?php require 'partials/footer.php';?>