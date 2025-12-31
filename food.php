<?php include("partials-front/menu.php");?>
    <!-- fOOD sEARCH Section Starts Here -->
    <section class="food-search text-center">
        <div class="container">
            
            <form action="<?php echo SITEURL;?>food-search.php" method="POST">
                <input type="search" name="search" placeholder="Tìm kiếm món ăn.." required>
                <input type="submit" name="submit" value="Tìm kiếm" class="btn btn-primary">
            </form>

        </div>
    </section>
    <!-- fOOD sEARCH Section Ends Here -->



    <!-- fOOD MEnu Section Starts Here -->
    <section class="food-menu">
        <div class="container">
            <h2 class="text-center">Thực đơn</h2>

            <?php
          $sql2 = "SELECT * FROM tbl_food";

          $res2 = mysqli_query($conn,$sql2);

          $count2 = mysqli_num_rows($res2);

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

                    <a href="<?php echo SITEURL ;?>order.php?food_id=<?php echo $id;?>" class="btn btn-primary">Đặt ngay</a>
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

    </section>
    <!-- fOOD Menu Section Ends Here -->

    <?php include("partials-front/footer.php"); ?>