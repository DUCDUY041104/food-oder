<?php include('partials/menu.php'); ?>

<div class = "main-content">
     <div class = "wrapper">
        <h1>Quản lý danh mục</h1>

        <br /><br />
        <br><br>

               <!-- Button to add Category -->
               <a href = "<?php echo SITEURL; ?>admin/add-category.php" class = "btn-primary">Thêm danh mục</a>

               <br /><br /><br />

               <table class="tbl-full">
                <tr>
                    <th>STT</th>
                    <th>Tên danh mục</th>
                    <th>Hình ảnh</th>
                    <th>Nổi bật</th>
                    <th>Hoạt động</th>
                    <th>Thao tác</th>
                </tr>
                <?php 
                    $sql = "SELECT * FROM tbl_category";
                    $res = mysqli_query($conn, $sql);
                    $count = mysqli_num_rows($res);
                    $sn = 1;
                    if($count>0){
                        while($row=mysqli_fetch_assoc($res)){
                            $id = $row['id'];
                            $title = $row['title'];
                            $image_name = $row['image_name'];
                            $featured = $row['featured'];
                            $active = $row['active'];
                            ?>
                            <tr>
                               <td><?php echo $sn++; ?></td>
                               <td><?php echo $title; ?></td>
                               <td>
                                   <?php
                                       if($image_name!=""){
                                        ?>
                                        <img src = "<?php echo SITEURL; ?>image/category/<?php echo $image_name; ?>" width="100px">
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
                                   <a href = "<?php echo SITEURL; ?>admin/update-category.php?id=<?php echo $id ?>" class = "btn-secondary">Cập nhật</a>
                                   <a href = "<?php echo SITEURL; ?>admin/delete-category.php?id=<?php echo $id ?>&image_name=<?php echo $image_name; ?>" class = "btn-danger">Xóa</a>
                               </td>
                            </tr>
                            <?php
                        }
                    }
                    else{
                        ?>
                        <tr>
                            <td colspan="6"><div class="error">No Category Added.</div></td>
                        </tr>
                        <?php
                    }
                
                ?>

              

               </table>

    </div>
</div>

<?php include('partials/footer.php') ?>