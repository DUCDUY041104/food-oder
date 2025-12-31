<?php include('partials/menu.php'); ?>

        <!-- Main Content Section Starts -->
        <div class = "main-content">
            <div class = "wrapper">
               <h1>Quản lý quản trị viên</h1>

               <br />
               <br><br><br>

               <!-- Button to add Admin -->
               <a href = "add-admin.php" class = "btn-primary">Thêm quản trị viên</a>

               <br /><br /><br />

               <table class="tbl-full">
                <tr>
                    <th>STT</th>
                    <th>Họ tên</th>
                    <th>Tên đăng nhập</th>
                    <th>Thao tác</th>
                </tr>



                <?php 
                    $sql = "SELECT * FROM tbl_admin";
                    $res = mysqli_query($conn, $sql);
                    if($res==TRUE){
                        $count = mysqli_num_rows($res);

                        $sn=1;
                        if($count>0){
                            while($rows=mysqli_fetch_assoc($res)){
                                $id=$rows['id'];
                                $full_name=$rows['full_name'];
                                $username=$rows['username'];
                                ?>
                                  <tr>
                                      <td><?php echo $sn++; ?></td>
                                      <td><?php echo $full_name; ?></td>
                                      <td><?php echo $username; ?></td>
                                      <td>
                                        <a href = "<?php echo SITEURL; ?>admin/update-admin.php?id=<?php echo $id ?>" class = "btn-secondary">Cập nhật</a>
                                        <a href = "<?php echo SITEURL; ?>admin/delete-admin.php?id=<?php echo $id ?>" class = "btn-danger">Xóa</a>
                                     </td>
                                 </tr>
                                <?php
                            }
                        }
                    }
                ?>
               </table>


            </div>
        </div>
        <!-- Main Content Section Ends -->

<?php include('partials/footer.php'); ?>