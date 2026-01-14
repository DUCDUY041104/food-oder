<?php include('partials/menu.php'); ?>

        <!-- Main Content Section Starts -->
        <div class="main-content">
            <div class="wrapper">
               <h1>Quản lý quản trị viên</h1>
       
               <br />
               <br><br><br>
       
               <!-- Button to add Admin -->
               <a href="add-admin.php" class="btn-primary">Thêm quản trị viên</a>
       
               <br /><br /><br />
       
               <table class="tbl-full">
                    <tr>
                        <th>STT</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Số điện thoại</th>
                        <th>Thao tác</th>
                    </tr>

                    <?php 
                        // Lấy danh sách admin với thông tin quan trọng
                        // YÊU CẦU: bảng tbl_admin cần có cột `phone`
                        $sql = "SELECT id, full_name, email, phone FROM tbl_admin ORDER BY id ASC";
                        $res = mysqli_query($conn, $sql);
                        if($res instanceof mysqli_result){
                            $sn = 1;
                            if(mysqli_num_rows($res) > 0){
                                while($rows = mysqli_fetch_assoc($res)){
                                    $id        = $rows['id'];
                                    $full_name = $rows['full_name'];
                                    $email     = $rows['email'];
                                    $phone     = $rows['phone'];
                                    ?>
                                    <tr>
                                        <td><?php echo $sn++; ?></td>
                                        <td><?php echo htmlspecialchars($full_name); ?></td>
                                        <td><?php echo htmlspecialchars($email); ?></td>
                                        <td><?php echo htmlspecialchars($phone); ?></td>
                                        <td>
                                            <a href="<?php echo SITEURL; ?>admin/update-admin.php?id=<?php echo $id; ?>" class="btn-secondary">Cập nhật</a>
                                            <a href="<?php echo SITEURL; ?>admin/delete-admin.php?id=<?php echo $id; ?>" class="btn-danger">Xóa</a>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="5" class="error">Chưa có quản trị viên nào.</td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr>
                                <td colspan="5" class="error">Lỗi khi tải danh sách quản trị viên.</td>
                            </tr>
                            <?php
                        }
                    ?>
               </table>
       
            </div>
        </div>
        <!-- Main Content Section Ends -->
       
<?php include('partials/footer.php'); ?>