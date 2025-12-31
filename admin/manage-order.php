<?php include('partials/menu.php'); ?>

<div class = "main-content">
     <div class = "wrapper">
        <h1>Quản lý đơn hàng</h1>


               <br /><br /><br />
              <br><br>
               <table class="tbl-full">
                <tr>
                    <th>STT</th>
                    <th>Mã đơn hàng</th>
                    <th>Món ăn</th>
                    <th>Giá</th>
                    <th>Số lượng</th>
                    <th>Tổng tiền</th>
                    <th>Ngày đặt</th>
                    <th>Trạng thái</th>
                    <th>Tên khách hàng</th>
                    <th>Liên hệ</th>
                    <th>Email</th>
                    <th>Địa chỉ</th>
                    <th>Thao tác</th>
                </tr>
                <?php 
                    $sql = "SELECT * FROM tbl_order ORDER BY id DESC"; //display the lastest order at first
                    $res = mysqli_query($conn, $sql);
                    $count = mysqli_num_rows($res);
                    $sn = 1;
                    if($count>0){
                        while($row=mysqli_fetch_assoc($res)){
                            $id = $row['id'];
                            $order_code = isset($row['order_code']) ? $row['order_code'] : 'N/A';
                            $food = $row['food'];
                            $price = $row['price'];
                            $qty = $row['qty'];
                            $total = $row['total'];
                            $order_date = $row['order_date'];
                            $status = $row['status'];
                            $customer_name = $row['customer_name'];
                            $customer_contact = $row['customer_contact'];
                            $customer_email = $row['customer_email'];
                            $customer_address = $row['customer_address'];
                            ?>
                                <tr>
                                   <td><?php echo $sn++; ?></td>
                                   <td style="white-space: nowrap;">
                                       <strong style="color: #ff6b81;"><?php echo htmlspecialchars($order_code); ?></strong>
                                       <?php if($order_code != 'N/A'): ?>
                                       <button onclick="copyOrderCode('<?php echo htmlspecialchars($order_code); ?>')" 
                                               style="margin-left: 8px; padding: 3px 8px; background: #f1f2f6; border: none; border-radius: 3px; cursor: pointer; font-size: 12px; display: inline-block; vertical-align: middle;" 
                                               title="Copy mã đơn hàng">
                                           📋
                                       </button>
                                       <?php endif; ?>
                                   </td>
                                   <td><?php echo $food; ?></td>
                                   <td><?php echo $price; ?></td>
                                   <td><?php echo $qty; ?></td>
                                   <td><?php echo $total; ?></td>
                                   <td><?php echo $order_date; ?></td>
                                   <td>
                                       <?php  
                                           //order, on delivery, delivered, cancelled
                                           if($status == "Ordered"){
                                              echo"<lable>Đã đặt hàng</lable>";
                                           }
                                           elseif($status=="On Delivery"){
                                              echo"<lable style='color: orange;'>Đang giao hàng</lable>";
                                           }
                                           elseif($status=="Delivered"){
                                            echo"<lable style='color: green;'>Đã giao hàng</lable>";
                                           }
                                           elseif($status=="Cancelled"){
                                            echo"<lable style='color: red;'>Đã hủy</lable>";
                                           }
                                       ?>
                                   </td>
                                   <td><?php echo $customer_name; ?></td>
                                   <td><?php echo $customer_contact; ?></td>
                                   <td><?php echo $customer_email; ?></td>
                                   <td><?php echo $customer_address; ?></td>
                                   <td>
                                       <a href = "<?php echo SITEURL; ?>admin/update-order.php?id=<?php echo $id ?>" class = "btn-secondary">Cập nhật đơn hàng</a>
                                   </td>
                                </tr>
                            <?php
                        }
                    }
                    else{
                        echo "<tr><td colspan='13' class='error'>Chưa có đơn hàng nào</td></tr>";
                    }
                ?>



               </table>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function copyOrderCode(orderCode) {
        navigator.clipboard.writeText(orderCode).then(function() {
            Swal.fire({
                icon: 'success',
                title: 'Đã copy!',
                text: 'Mã đơn hàng: ' + orderCode,
                timer: 2000,
                showConfirmButton: false
            });
        }, function(err) {
            // Fallback cho trình duyệt không hỗ trợ clipboard API
            const textArea = document.createElement('textarea');
            textArea.value = orderCode;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            Swal.fire({
                icon: 'success',
                title: 'Đã copy!',
                text: 'Mã đơn hàng: ' + orderCode,
                timer: 2000,
                showConfirmButton: false
            });
        });
    }
</script>

<?php include('partials/footer.php') ?>