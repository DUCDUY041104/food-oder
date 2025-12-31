
<?php include('partials/menu.php'); ?>
<div class="main-content">
    <div class="wrapper">
        <h1>Thêm món ăn</h1>

        <br><br>
        <form action="" method="post" enctype="multipart/form-data">
            <table class="tbl-30">
            
                <tr>
                    <td>Tên món: </td>
                    <td>
                        <input type="text" id='check_title' name="title" placeholder="Tên món ăn">
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td><span id='warning_title'></span></td>
                </tr>
                <tr>
                    <td>Mô tả: </td>
                    <td>
                        <textarea name="description" id='check_description' cols="30" rows="5" placeholder="Mô tả món ăn"></textarea>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td><span id='warning_description'></span></td>
                </tr>
                <tr>
                    <td>Giá: </td>
                    <td>
                        <input type="number" id='check_price' name="price" placeholder="Giá món ăn" min="0">
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td><span id='warning_price'></span></td>
                </tr>
                <tr>
                    <td>Chọn hình ảnh: </td>
                    <td>
                        <input type="file" name="image">
                    </td>
                </tr>
                <tr>
                    <td>Danh mục: </td>
                    <td>
                        <select name="category">
                            <?php
                                $sql="SELECT * FROM `tbl_category` WHERE active='Yes'";
                                $res = mysqli_query($conn, $sql);
                                $count = mysqli_num_rows($res);
                                if($count>0)
                                {
                                    while($row=mysqli_fetch_assoc($res))
                                    {
                                        $id = $row['id'];
                                        $title = $row['title'];

                                        ?>
                                        <option value="<?php echo $id; ?>"><?php echo $title; ?></option>
                                        
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
                        <input type="radio" name="featured" value="Yes">Có
                        <input type="radio" name="featured" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td>Hoạt động: </td>
                    <td>
                        <input type="radio" name="active" value="Yes">Có
                        <input type="radio" name="active" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" name="submit" value="Thêm món ăn" class = "btn-secondary" id='submit_hover'>
                    </td>
                </tr>
            </table>
        </form>
        <?php
            if(isset($_POST['submit']))
            {
                $title=$_POST['title'];
                $description=$_POST['description'];
                $price=$_POST['price'];
                $category=$_POST['category'];
                $featured=$_POST['featured'];
                $active=$_POST['active'];

                if(isset($_POST['featured']))
                {
                    $featured=$_POST['featured'];
                }
                else
                {
                    $featured="No";
                }

                if(isset($_POST['active']))
                {
                    $active=$_POST['active'];
                }
                else
                {
                    $active="No";
                }

                if(isset($_FILES['image']['name']))
                {
                    $image_name=$_FILES['image']['name'];
                    if($image_name!="")
                    {
                        $image_parts = explode('.',$image_name);
                        $ext = end($image_parts);
                        $image_name="Food-name-".rand(0000,9999).".".$ext;
                        $source_path = $_FILES['image']['tmp_name'];
                        $destination_path = "../image/food/" . $image_name;
                        $upload = move_uploaded_file($source_path, $destination_path);

                        if($upload == false)
                        {
                            $_SESSION['upload']="<div class='error'>Tải hình ảnh thất bại.</div>";
                            header("location:add-food.php");
                            die();
                        }
                    }
                }
                else
                {
                    $image_name="";
                }

                $sql2 = "INSERT INTO tbl_food SET
                    title='$title',
                    `description`='$description',
                    price='$price',
                    image_name='$image_name',
                    category_id='$category',
                    featured='$featured',
                    active='$active'
                ";
                
                $res2 = mysqli_query($conn, $sql2);

                if($res2==TRUE){
                    $_SESSION['add'] = "<div class='success'>Thêm món ăn thành công!</div>";
                    
                }
                else{
                    $_SESSION['add'] = "<div class='error'>Thêm món ăn thất bại!</div>";
                    
                }
            }
        ?>                        
    </div>
</div>
<!-- Footer Section Starts -->
<div class = "footer">
            <div class = "wrapper">
                <p class = "text-center"> 2023 All rights resered, Some restaurant. Developed By - <a href ="#">5 anh em </a></p>
            </div>
        </div>
        <!-- Footer Section Ends -->
    </body>
    <script src="partials/add-food.js"></script>
</html>