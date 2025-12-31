
<?php include('partials/menu.php'); ?>
<?php 
    if(isset($_GET['id']))
    {
        $id=$_GET['id'];
        $sql2="SELECT * FROM tbl_food WHERE id='$id'";
        $res2=mysqli_query($conn, $sql2);
        $row2=mysqli_fetch_assoc($res2);

        $title = $row2['title'];
        $description = $row2['description'];
        $price = $row2['price'];
        $current_image = $row2['image_name'];
        $current_category = $row2['category_id'];
        $featured = $row2['featured'];
        $active = $row2['active'];
    }
    else
    {
        header('location:'.SITEURL.'admin/manage-food.php');
    }
?>

<div class='main-content'>
    <div class='wrapper'>
        <h1>Cập nhật món ăn</h1>
        <br><br>

        <form action="" method="post" enctype="multipart/form-data">
            <table class='tbl-30'>
            <tr>
                    <td>Tên món: </td>
                    <td>
                        <input type="text" name="title" value="<?php echo $title; ?>">
                    </td>
                </tr>
                <tr>
                    <td>Mô tả: </td>
                    <td>
                        <textarea name="description" cols="30" rows="5"><?php echo $description; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>Giá: </td>
                    <td>
                        <input type="number" name="price" value="<?php echo $price; ?>" min="0">
                    </td>
                </tr>
                <tr>
                    <td>Hình ảnh hiện tại: </td>
                    <td>
                        <?php
                        if($current_image == "")
                        {
                            echo "<div class='error'>Chưa có hình ảnh.</div>";
                        }
                        else 
                        {
                            ?>
                            <img src="<?php echo SITEURL; ?>image/food/<?php echo $current_image ?>" width="150px">
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Chọn hình ảnh mới: </td>
                    <td>
                        <input type="file" name="image">
                    </td>
                </tr>
                <tr>
                    <td>Danh mục: </td>
                    <td>
                        <select name="category">
                            <?php
                                $sql="SELECT * FROM tbl_category WHERE active='Yes'";
                                $res = mysqli_query($conn, $sql);
                                $count = mysqli_num_rows($res);
                                if($count>0)
                                {
                                    while($row=mysqli_fetch_assoc($res))
                                    {
                                        $category_id = $row['id'];
                                        $category_title = $row['title'];

                                        ?>
                                        <option <?php if($current_category==$category_id){echo "selected";} ?> value="<?php echo $category_id; ?>"><?php echo $category_title; ?></option>
                                        
                                        <?php

                                    }
                                }
                                else
                                {
                                    ?>
                                     <option value="0">Không tìm thấy danh mục</option>
                                     <?php
                                }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Nổi bật: </td>
                    <td>
                        <input <?php if($featured == "Yes"){echo "checked";} ?> type="radio" name="featured" value="Yes">Có
                        <input <?php if($featured == "No"){echo "checked";}?> type="radio" name="featured" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td>Hoạt động: </td>
                    <td>
                        <input <?php if($active == "Yes"){echo "checked";} ?> type="radio" name="active" value="Yes">Có
                        <input <?php if($active == "No"){echo "checked";} ?> type="radio" name="active" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="current_image" value="<?php echo $current_image; ?>">
                        <input type="submit" name="submit" value="Cập nhật món ăn" class = "btn-secondary">
                    </td>
                </tr>
            </table>
        </form>
        <?php
            if(isset($_POST['submit']))
            {
                $title = $_POST['title'];
                $description = $_POST['description'];
                $price = $_POST['price'];
                $current_image = $_POST['current_image'];
                $category = $_POST['category'];
                $featured = $_POST['featured'];
                $active = $_POST['active'];

                if(isset($_FILES['image']['name']))
                {
                    $image_name = $_FILES['image']['name'];
                    if($image_name != "")
                    {
                        $image_parts = explode('.', $image_name);
                        $ext = end($image_parts);
                        $image_name = "Food-name-".rand(0000,9999).'.'.$ext;
                        $src_path = $_FILES['image']['tmp_name'];
                        $dest_path = "../image/food/".$image_name;
                        $upload = move_uploaded_file($src_path, $dest_path);

                        if($upload == false)
                        {
                            $_SESSION['upload'] = "<div class='error'>Fail to Upload new Image.</div>";
                            header('location:admin/manage-food.php');
                            die();
                        }

                        if($current_image != "")
                        {
                            $remove_path = "../image/food/".$current_image;
                            $remove = unlink($remove_path);

                            if($remove == false)
                            {
                                $_SESSION['$remove-failed'] = "<div class='error'>Fail to remove current image.</div>";
                                header('location:admin/manage-food.php');
                                die();
                            }
                        }
                    }
                }
                else
                {
                    $image_name = $current_image;
                }

                $sql3 = "UPDATE tbl_food SET
                    title = '$title',
                    `description` = '$description',
                    price = '$price',
                    image_name = '$image_name',
                    category_id = '$category',
                    featured = '$featured',
                    active = '$active'
                    WHERE id = $id
                ";
                $res3 = mysqli_query($conn, $sql3);
                if($res3 == true)
                {
                    $_SESSION['update'] = "<div class='success'>Cập nhật món ăn thành công!</div>";
                    header('location:manage-food.php');
                    
                }
                else
                {
                    $_SESSION['update'] = "<div class='error'>Cập nhật món ăn thất bại!</div>";
                    header('location:manage-food.php');
                }
            }
        ?>
    </div>
</div>
<?php include('partials/footer.php'); ?>