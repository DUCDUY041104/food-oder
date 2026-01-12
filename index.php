

<?php include("partials-front/menu.php");?>
    <!-- fOOD sEARCH Section Starts Here -->
    <section class="food-search text-center">
        <div class="container">
            
            <form action="<?php echo SITEURL ;?>food-search.php" method="POST">
                <input type="search" name="search" placeholder="Tìm kiếm món ăn.." required>
                <input type="submit" name="submit" value="Tìm kiếm" class="btn btn-primary">
            </form>

        </div>
    </section>
    <!-- fOOD sEARCH Section Ends Here -->


    <!-- CAtegories Section Starts Here -->
    <section class="categories">
        <div class="container">
            <h2 class="text-center">Khám phá món ăn</h2>

            <?php 
            $sql = "SELECT * FROM tbl_category LIMIT 3";

            $res = mysqli_query($conn,$sql);
            
            if($res === false) {
                echo "<div class='error'>Lỗi database: " . mysqli_error($conn) . "</div>";
                $count = 0;
            } else {
                $count = mysqli_num_rows($res);
            }

            if($count>0)
            {
                while($row=mysqli_fetch_assoc($res)){
                    $id = $row['id'];
                    $title = $row['title'];
                    $image_name = $row['image_name'];
                ?>

                    <a href="<?php echo SITEURL; ?>category-food.php?category_id=<?php echo $id; ?> ">
            <div class="box-3 float-container">
                <?php


                if($image_name=="")
                {
                    echo"<div class='error'>Chưa có hình ảnh</div>";

                }
                else{
                    ?>
                    <img src="<?php echo SITEURL;?>image/category/<?php echo $image_name;?>" alt="Pizza" class="img-responsive img-curve">
                    <?php
                }
                ?>
                

                <h3 class="float-text text-white"><?php echo $title; ?></h3>
            </div>
            </a>

                <?php



                }

            }
            else{

                echo"<div class='error'>Chưa có danh mục nào.</div>";
            }
             ?>

           

        

            <div class="clearfix"></div>
        </div>
    </section>
    <!-- Categories Section Ends Here -->




    <!-- fOOD MEnu Section Starts Here -->
    <section class="food-menu">
        <div class="container">
            <h2 class="text-center">Thực đơn</h2>
            <?php
          $sql2 = "SELECT * FROM tbl_food LIMIT 6";

          $res2 = mysqli_query($conn,$sql2);
          
          if($res2 === false) {
              echo "<div class='error'>Lỗi database: " . mysqli_error($conn) . "</div>";
              $count2 = 0;
          } else {
              $count2 = mysqli_num_rows($res2);
          }

          if($count2>0){
            while($row=mysqli_fetch_assoc($res2))
            {
                $id = $row['id'];
                $title = $row['title'];
                $price = $row['price'];
                $description=$row['description'];
                $image_name = $row['image_name']; 
            ?>
                <div class="food-menu-box">
                <div class="food-menu-img">

                <?php 
                if($image_name=="")
                {
                        echo "<div class='error'>Chưa có hình ảnh</div>";
                }
                else
                {
                    ?>
                    <img src="<?php echo SITEURL;?>image/food/<?php echo $image_name; ?>" alt="Chicke Hawain Pizza" class="img-responsive img-curve">
                    <?php

                }
                ?>
                    
                </div>

                <div class="food-menu-desc">
                    <h4><?php echo $title ;?></h4>
                    <p class="food-price"><?php echo $price ;?></p>
                    <p class="food-detail">
                        <?php echo  $description ;?>
                    </p>
                    <br>

                    <?php if(isset($_SESSION['user_id'])): ?>
                        <button onclick="addToCart(<?php echo $id; ?>)" class="btn btn-primary">🛒 Thêm vào giỏ</button>
                    <?php else: ?>
                        <a href="<?php echo SITEURL; ?>user/login.php" class="btn btn-primary">Đặt ngay</a>
                    <?php endif; ?>
                </div>
                </div>


            <?php
            }

          }
          else{
            echo "<div class='error'>Chưa có món ăn nào</div>";
          }
        



            ?>



            




            <div class="clearfix"></div>

            

        </div>

        <p class="text-center">
            <a href="<?php echo SITEURL; ?>food.php">Xem tất cả món ăn</a>
        </p>
    </section>
    <!-- fOOD Menu Section Ends Here -->
    <?php include("partials-front/footer.php"); ?>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php
        function extractMessage($html) {
            $html = strip_tags($html);
            return trim($html);
        }
        
        $sessionMessages = ['login-success', 'register-success', 'order', 'login', 'no-login-message', 'access-denied'];
        
        foreach($sessionMessages as $key) {
            if(isset($_SESSION[$key]) && !empty($_SESSION[$key])) {
                $message = extractMessage($_SESSION[$key]);
                if(!empty($message)) {
                    $icon = 'info';
                    $title = 'Thông báo';
                    
                    if(strpos(strtolower($_SESSION[$key]), 'success') !== false || 
                       strpos(strtolower($message), 'thành công') !== false ||
                       strpos(strtolower($message), 'successfully') !== false ||
                       strpos(strtolower($message), 'ordered') !== false) {
                        $icon = 'success';
                        $title = 'Thành công!';
                    } elseif(strpos(strtolower($_SESSION[$key]), 'error') !== false || 
                             strpos(strtolower($message), 'lỗi') !== false ||
                             strpos(strtolower($message), 'failed') !== false) {
                        $icon = 'error';
                        $title = 'Lỗi!';
                    } elseif(strpos(strtolower($_SESSION[$key]), 'access-denied') !== false ||
                             strpos(strtolower($message), 'không có quyền') !== false ||
                             strpos(strtolower($message), 'warning') !== false) {
                        $icon = 'warning';
                        $title = 'Cảnh báo!';
                    }
                    
                    echo "Swal.fire({
                        icon: '" . $icon . "',
                        title: '" . $title . "',
                        text: '" . addslashes($message) . "',
                        showConfirmButton: true,
                        timer: 3000
                    });";
                }
                unset($_SESSION[$key]);
            }
        }
        ?>
    </script>
    