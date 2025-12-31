<?php include("partials/menu.php");?>


        <!-- main content section starts-->
        <div class="main-content">
            <div class="wrapper">
                <h1>BẢNG ĐIỀU KHIỂN</h1>

                <div class="col-4 text-center">

                <?php 
        
        $sql = "SELECT * FROM tbl_category";

        $res = mysqli_query($conn,$sql);

        $count = mysqli_num_rows($res);
            ?>

                    <h1><?php echo $count;?></h1>
                    <br/>
                        Danh mục
                </div>

                <div class="col-4 text-center">

                <?php
          $sql2 = "SELECT * FROM tbl_food";

          $res2 = mysqli_query($conn,$sql2);

          $count2 = mysqli_num_rows($res2);
          ?>
                    <h1><?php echo $count2 ;?></h1>
                    <br/>
                        Món ăn
                </div>

                <div class="col-4 text-center">
                <?php 
        
        $sql3 = "SELECT * FROM tbl_order";

        $res3 = mysqli_query($conn,$sql3);

        $count3 = mysqli_num_rows($res3);
            ?>
                    <h1><?php echo $count3 ;?></h1>
                    <br  />
                    Tổng đơn hàng
                </div>

                <div class="col-4 text-center">
                    <?php
                    $sql4 = "SELECT SUM(total) AS total FROM tbl_order WHERE status='Delivered'";

                    $res4 = mysqli_query($conn,$sql4);
            
                    $row4 = mysqli_fetch_assoc($res4);

                    $total_revenue = $row4['total'];
                    
                    // Nếu không có đơn hàng hoặc total là NULL, đặt về 0
                    if($total_revenue == null || $total_revenue == '') {
                        $total_revenue = 0;
                    }

                    // Lấy dữ liệu doanh thu theo thời gian (7 ngày gần nhất)
                    $sql_revenue = "SELECT DATE(order_date) as date, SUM(total) as daily_revenue 
                                    FROM tbl_order 
                                    WHERE status='Delivered' 
                                    AND order_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                    GROUP BY DATE(order_date) 
                                    ORDER BY date ASC";
                    $res_revenue = mysqli_query($conn, $sql_revenue);
                    $revenue_dates = [];
                    $revenue_amounts = [];
                    while($row_revenue = mysqli_fetch_assoc($res_revenue)) {
                        $revenue_dates[] = date('d/m', strtotime($row_revenue['date']));
                        $revenue_amounts[] = $row_revenue['daily_revenue'] ? $row_revenue['daily_revenue'] : 0;
                    }
                     
                     ?>
                    <h1><?php echo $total_revenue ;?></h1>
                    <br/>
                    Doanh thu
                </div>
            <div class="clearfix"></div>

            <!-- Charts Section -->
            <div class="charts-container">
                <div class="chart-wrapper">
                    <h2>Thống kê tổng quan</h2>
                    <canvas id="overviewChart"></canvas>
                </div>

                <div class="chart-wrapper chart-full">
                    <h2>Doanh thu theo thời gian</h2>
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
            <div class="clearfix"></div>

            </div>
         </div>
        <!-- main content section ends-->

<?php include("partials/footer.php"); ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    <?php
    if(isset($_SESSION['login-success'])){
        echo "Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: '" . addslashes($_SESSION['login-success']) . "',
            showConfirmButton: true,
            timer: 3000
        });";
        unset($_SESSION['login-success']);
    }
    if(isset($_SESSION['register-success'])){
        echo "Swal.fire({
            icon: 'success',
            title: 'Thành công!',
            text: '" . addslashes($_SESSION['register-success']) . "',
            showConfirmButton: true,
            timer: 3000
        });";
        unset($_SESSION['register-success']);
    }
    if(isset($_SESSION['add'])){
        $addMsg = strip_tags($_SESSION['add']);
        if(!empty($addMsg)){
            $icon = strpos($addMsg, 'success') !== false ? 'success' : 'error';
            echo "Swal.fire({
                icon: '" . $icon . "',
                title: '" . ($icon == 'success' ? 'Thành công!' : 'Lỗi!') . "',
                text: '" . addslashes($addMsg) . "',
                showConfirmButton: true,
                timer: 3000
            });";
        }
        unset($_SESSION['add']);
    }
    if(isset($_SESSION['update'])){
        $updateMsg = strip_tags($_SESSION['update']);
        if(!empty($updateMsg)){
            $icon = strpos($updateMsg, 'success') !== false ? 'success' : 'error';
            echo "Swal.fire({
                icon: '" . $icon . "',
                title: '" . ($icon == 'success' ? 'Thành công!' : 'Lỗi!') . "',
                text: '" . addslashes($updateMsg) . "',
                showConfirmButton: true,
                timer: 3000
            });";
        }
        unset($_SESSION['update']);
    }
    if(isset($_SESSION['delete'])){
        $deleteMsg = strip_tags($_SESSION['delete']);
        if(!empty($deleteMsg)){
            $icon = strpos($deleteMsg, 'success') !== false ? 'success' : 'error';
            echo "Swal.fire({
                icon: '" . $icon . "',
                title: '" . ($icon == 'success' ? 'Thành công!' : 'Lỗi!') . "',
                text: '" . addslashes($deleteMsg) . "',
                showConfirmButton: true,
                timer: 3000
            });";
        }
        unset($_SESSION['delete']);
    }
    ?>
</script>

<!-- Charts Script -->
<script>
    // Dữ liệu từ PHP
    const categoryCount = <?php echo $count; ?>;
    const foodCount = <?php echo $count2; ?>;
    const orderCount = <?php echo $count3; ?>;
    const revenueDates = <?php echo json_encode($revenue_dates); ?>;
    const revenueAmounts = <?php echo json_encode($revenue_amounts); ?>;

    // Biểu đồ tổng quan (Bar Chart)
    const overviewCtx = document.getElementById('overviewChart').getContext('2d');
    new Chart(overviewCtx, {
        type: 'bar',
        data: {
            labels: ['Danh mục', 'Món ăn', 'Tổng đơn hàng'],
            datasets: [{
                label: 'Số lượng',
                data: [categoryCount, foodCount, orderCount],
                backgroundColor: [
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderColor: [
                    'rgba(54, 162, 235, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(255, 159, 64, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1
                    }
                }
            }
        }
    });

    // Biểu đồ doanh thu theo thời gian (Line Chart)
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: revenueDates.length > 0 ? revenueDates : ['Chưa có dữ liệu'],
            datasets: [{
                label: 'Doanh thu (VND)',
                data: revenueAmounts.length > 0 ? revenueAmounts : [0],
                borderColor: 'rgba(39, 174, 96, 1)',
                backgroundColor: 'rgba(39, 174, 96, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: 'rgba(39, 174, 96, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN').format(value) + ' VND';
                        }
                    }
                }
            }
        }
    });
</script>