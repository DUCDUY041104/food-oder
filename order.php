<?php 
        // Include constants để có $conn và SITEURL
        include('config/constants.php');
        
        // Hàm tạo mã đơn hàng tự động
        function generateOrderCode($conn) {
            $prefix = 'ORD';
            $date = date('Ymd');
            $max_attempts = 10;
            $attempt = 0;
            
            do {
                $random = strtoupper(substr(uniqid(), -6));
                $order_code = $prefix . $date . $random;
                
                // Kiểm tra mã đã tồn tại chưa
                $check_sql = "SELECT id FROM tbl_order WHERE order_code = '$order_code'";
                $check_result = mysqli_query($conn, $check_sql);
                
                if (mysqli_num_rows($check_result) == 0) {
                    return $order_code;
                }
                
                $attempt++;
            } while ($attempt < $max_attempts);
            
            // Nếu vẫn trùng sau nhiều lần, thêm timestamp
            return $prefix . $date . strtoupper(substr(md5(time() . rand()), 0, 6));
        }

        // Kiểm tra đăng nhập TRƯỚC KHI xử lý bất kỳ điều gì
        if(!isset($_SESSION['user']) || !isset($_SESSION['user_id'])) {
            $_SESSION['no-login-message'] = "Vui lòng đăng nhập để đặt hàng!";
            // Lưu lại food_id để quay lại sau khi đăng nhập
            if(isset($_GET['food_id'])) {
                $_SESSION['redirect_food_id'] = $_GET['food_id'];
            }
            header('location:'.SITEURL.'user/login.php');
            exit();
        }

        // Xử lý đặt hàng trước khi output HTML
        if(isset($_POST['submit'])){
            $food = trim($_POST['food']);
            $price = $_POST['price'];
            $qty = $_POST['qty'];
            $total = $price * $qty;
            $order_date = date("Y-m-d h:i:sa");
            $status = "ordered";
            $customer_name = $_POST['full-name'];
            $customer_contact = $_POST['contact'];
            $customer_email = $_POST['email'];
            $customer_address = $_POST['address'];
            $user_id = $_SESSION['user_id'] ?? null;
            
            // Tạo mã đơn hàng
            $order_code = generateOrderCode($conn);
            
            // Escape các giá trị để tránh SQL injection
            $food = mysqli_real_escape_string($conn, $food);
            $customer_name = mysqli_real_escape_string($conn, $customer_name);
            $customer_contact = mysqli_real_escape_string($conn, $customer_contact);
            $customer_email = mysqli_real_escape_string($conn, $customer_email);
            $customer_address = mysqli_real_escape_string($conn, $customer_address);
            $order_code = mysqli_real_escape_string($conn, $order_code);

            $sql2 = "INSERT INTO tbl_order SET
                order_code = '$order_code',
                user_id = " . ($user_id ? intval($user_id) : "NULL") . ",
                food = '$food',
                price = " . floatval($price) . ",
                qty = " . intval($qty) . ",
                total = " . floatval($total) . ",
                order_date = '$order_date',
                status = '$status',
                customer_name = '$customer_name',
                customer_contact = '$customer_contact',
                customer_email = '$customer_email',
                customer_address = '$customer_address'
             ";

             $res2 = mysqli_query($conn,$sql2);

             if($res2==true)
             {
                $_SESSION['order'] = "Đặt hàng thành công! Mã đơn hàng: " . $order_code;
                $_SESSION['order_code'] = $order_code;
                header('location:'.SITEURL.'user/order-history.php');
                exit();
             }
             else
             {
                $_SESSION['order'] = "Đặt hàng thất bại. Vui lòng thử lại!";
                header('location:'.SITEURL.'index.php');
                exit();
             }
        }

        include("partials-front/menu.php");
?>

        <?php

        // Lấy thông tin người dùng từ database
        $user_id = $_SESSION['user_id'];
        $sql_user = "SELECT * FROM tbl_user WHERE id=$user_id";
        $res_user = mysqli_query($conn, $sql_user);
        $user_data = null;
        if($res_user) {
            $user_data = mysqli_fetch_assoc($res_user);
        }

        if(isset($_GET['food_id'])) 
        {
                $food_id = $_GET['food_id'];

                $sql = "SELECT * FROM tbl_food WHERE id=$food_id";

                $res = mysqli_query($conn,$sql);

                $count = mysqli_num_rows($res);

                if($count==1){
                    $row = mysqli_fetch_assoc($res);

                    $title = $row['title'];
                    $price = $row['price'];
                    $image_name = $row['image_name'];

                }
        }
        else
        {
            header('location:'.SITEURL);
            exit();
        }
        ?>

    <!-- fOOD sEARCH Section Starts Here -->
    <section class="food-search">
        <div class="container">
            
            <h2 class="text-center text-white">Điền form để xác nhận đơn hàng của bạn.</h2>

            <form action="" method="POST" class="order">
                <fieldset>
                    <legend>Món ăn đã chọn</legend>
    
                    <div class="food-menu-desc">
                        <?php
                        if($image_name==""){
                            echo "<div class='error'>Chưa có hình ảnh</div>";
                        }
                        else{
                            ?>
                            <img Src="<?php echo SITEURL; ?>image/food/<?php echo $image_name;?>" alt="Chicke Hawain Pizza" class="img-responsive img-curve">
                            <?php
                        }
                        

                         ?>

                        <h3><?php echo $title; ?></h3>
                        <input type="hidden" name="food" value=" <?php echo $title; ?>">


                        <p class="food-price"><?php echo $price; ?></p>
                        <input type="hidden" name="price" value="<?php echo $price; ?>">

                        <div class="order-label">Số lượng</div>
                        <input type="number" name="qty" class="input-responsive" value="1" required>
                        
                    </div>

                </fieldset>
                
                <fieldset>
                    <legend>Thông tin giao hàng</legend>
                    <div class="order-label">Họ tên</div>
                    <input type="text" name="full-name" placeholder="" class="input-responsive" value="<?php echo isset($user_data['full_name']) ? htmlspecialchars($user_data['full_name']) : ''; ?>" required>

                    <div class="order-label">Số điện thoại</div>
                    <input type="tel" name="contact" placeholder="" class="input-responsive" value="<?php echo isset($user_data['phone']) ? htmlspecialchars($user_data['phone']) : ''; ?>" required>

                    <div class="order-label">Email</div>
                    <input type="email" name="email" placeholder="" class="input-responsive" value="<?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email']) : ''; ?>" required>

                    <div class="order-label">Địa chỉ</div>
                    <textarea name="address" rows="10" placeholder="" class="input-responsive" required><?php echo isset($user_data['address']) ? htmlspecialchars($user_data['address']) : ''; ?></textarea>

                    <input type="submit" name="submit" value="Xác nhận đơn hàng" class="btn btn-primary">
                </fieldset>

            </form>

        </div>
    </section>
    <!-- fOOD sEARCH Section Ends Here -->

    <?php include("partials-front/footer.php"); ?>