<?php 
        require_once '../config/constants.php';
        // Xử lý update order trước khi output HTML
        if(isset($_POST['submit'])){
            $id = $_POST['id'];
            $price = $_POST['price'];
            $qty = $_POST['qty'];
            $total = $price * $qty;
            $status = $_POST['status'];
            $customer_name = $_POST['customer_name'];
            $customer_contact = $_POST['customer_contact'];
            $customer_email = $_POST['customer_email'];
            $customer_address = $_POST['customer_address'];
            $sql2 = "UPDATE tbl_order SET
                qty='$qty',
                total='$total',
                status='$status',
                customer_name='$customer_name',
                customer_contact='$customer_contact',
                customer_email='$customer_email',
                customer_address='$customer_address'
                WHERE id='$id'  
            ";
            $res2 = mysqli_query($conn, $sql2);
            if($res2==TRUE){
                $_SESSION['update'] = "Cập nhật đơn hàng thành công!";
                header('location:'.SITEURL.'admin/manage-order.php');
                exit();
            }
            else{
                $_SESSION['update'] = "Cập nhật đơn hàng thất bại!";
                header('location:'.SITEURL.'admin/manage-order.php');
                exit();
            }
        }

        require 'partials/menu.php' 
?>
<div class="main-content">
    <div class="wrapper">
        <h1>Cập nhật đơn hàng</h1>
        <br><br>
        <?php 
             if(isset($_GET['id'])){
                $id = $_GET['id'];
                $sql = "SELECT * FROM tbl_order WHERE id='$id'";
                $res = mysqli_query($conn, $sql);
                $count = mysqli_num_rows($res);
                if($count==1){
                    $row=mysqli_fetch_assoc($res);
                    $food = $row['food'];
                    $price = $row['price'];
                    $qty = $row['qty'];
                    $status = $row['status'];
                    $customer_name = $row['customer_name'];
                    $customer_contact = $row['customer_contact'];
                    $customer_email = $row['customer_email'];
                    $customer_address = $row['customer_address'];

                }
                else{
                    header('location:'.SITEURL.'admin/manage-order.php');
                    exit();
                }
             }
             else{
                header('location:'.SITEURL.'admin/manage-order.php');
                exit();
             }
        
        
        ?>




        <form action="" method="post">
            <table class = "tbn-30">
                <tr>
                    <td>Tên món ăn</td>
                    <td><b><?php echo $food; ?></b></td>
                </tr>
                <tr>
                    <td>Giá</td>
                    <td><b>$<?php echo $price; ?></b></td>
                </tr>
                <tr>
                    <td>Số lượng</td>
                    <td>
                        <input type="number" name="qty" value="<?php echo $qty; ?>" >
                    </td>
                </tr>
                <tr>
                    <td>Trạng thái</td>
                    <td>
                    <select name="status">
                            <option  value="Ordered">Đã đặt hàng</option>
                            <option  value="On Delivery">Đang giao hàng</option>
                            <option value="Delivered">Đã giao hàng</option>
                            <option  value="Cancelled">Đã hủy</option>
                        </select>


                    <?php 
                    if($status=="Ordered")
                    {
                        echo "<label>Đã đặt hàng</label>";
                    }
                    if($status=="On Delivery"){
                        echo "<label style='color:orange'>Đang giao hàng</label>";
                    }
                    if($status=="Delivered"){
                        echo "<label style='color:green'>Đã giao hàng</label>";
                    }
                    if($status=="Cancelled"){
                        echo "<label style='color:red'>Đã hủy</label>";
                    }
                    ?>
                    </td>
                </tr>
                <tr>
                    <td>Tên khách hàng</td>
                    <td>
                        <input type="text" name="customer_name" value="<?php echo $customer_name; ?>" id="">
                    </td>
                </tr>
                <tr>
                    <td>Liên hệ</td>
                    <td>
                        <input type="text" name="customer_contact" value="<?php echo $customer_contact; ?>" id="">
                    </td>
                </tr>
                <tr>
                    <td>Email</td>
                    <td>
                        <input type="text" name="customer_email" value="<?php echo $customer_email; ?>" id="">
                    </td>
                </tr>
                <tr>
                    <td>Địa chỉ</td>
                    <td>
                        <textarea name="customer_address"  cols="30" rows="5"><?php echo $customer_address; ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="price" value="<?php echo $price; ?>">
                        <input type="submit" name="submit" value="Cập nhật đơn hàng" class ="btn-secondary">
                    </td>
                </tr>

            </table>
        </form>
    </div>
</div>


<?php require 'partials/footer.php' ?>