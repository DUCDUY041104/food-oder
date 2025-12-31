
<?php include('partials/menu.php'); ?>
<div class = "main-content">
     <div class = "wrapper">
        <h1>Quản lý món ăn</h1>

        <br /><br />

               <!-- Button to add Food -->
               <a href = "add-food.php" class = "btn-primary">Thêm món ăn</a>

               <br /><br /><br />



               <table class="tbl-full">
                <tr>
                    <th>STT</th>
                    <th>Tên món</th>
                    <th>Giá</th>
                    <th>Hình ảnh</th>
                    <th>Nổi bật</th>
                    <th>Hoạt động</th>
                    <th>Thao tác</th>
                </tr>
                <?php
                    $sql="SELECT * FROM tbl_food";
                    $res = mysqli_query($conn, $sql);
                    $count = mysqli_num_rows($res);
                    $sn = 1;
                    if($count > 0)
                    {
                        while($row=mysqli_fetch_assoc($res))
                        {
                            $id = $row['id'];
                            $title = $row['title'];
                            $price = $row['price'];
                            $image_name = $row['image_name'];
                            $featured = $row['featured'];
                            $active = $row['active'];
                            ?>

                            
                            <tr>
                            <td><?php echo $sn++; ?></td>
                            <td><?php echo $title; ?></td>
                            <td><?php echo $price; ?></td>
                            <td>
                                <?php
                                    if($image_name!=""){
                                     ?>
                                     <img src = "<?php echo SITEURL; ?>image/food/<?php echo $image_name; ?>" width="100px">
                                     <?php
                                    } 
                                    else{
                                     echo "<div class='error'>Chưa có hình ảnh</div>";
                                    }
                                ?>
                             </td>
                            <td><?php echo $featured; ?></td>
                            <td><?php echo $active; ?></td>
                            <td>
                                <a href = "<?php echo SITEURL; ?>admin/update-food.php?id=<?php echo $id ?>" class = "btn-secondary">Cập nhật</a>
                                <a href = "<?php echo SITEURL; ?>admin/delete-food.php?id=<?php echo $id ?>&image_name=<?php echo $image_name; ?>" class = "btn-danger">Xóa</a>
                            </td>
                         </tr>
                         <?php
                        }
                    }
                    else
                    {
                        echo " <tr> <td colspan='7' class='error'>Food Not Added Yet.</td> </tr>";
                    }
                ?>
               </table>

    </div>
</div>

<?php include('partials/footer.php') ?>