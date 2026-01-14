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
                    // Tổng doanh thu (đơn đã giao)
                    $sql4 = "SELECT SUM(total) AS total FROM tbl_order WHERE status='Delivered'";
                    $res4 = mysqli_query($conn,$sql4);
                    $row4 = mysqli_fetch_assoc($res4);
                    $total_revenue = $row4 && $row4['total'] !== null ? (float)$row4['total'] : 0;

                    // ====== THỐNG KÊ DOANH THU THEO THỜI GIAN (12 THÁNG / NĂM) ======
                    // Năm được chọn từ query string, mặc định là năm hiện tại
                    $currentYear  = (int)date('Y');
                    $revenue_year = isset($_GET['revenue_year']) ? (int)$_GET['revenue_year'] : $currentYear;

                    // Lấy danh sách năm có đơn hàng
                    $year_sql = "SELECT DISTINCT YEAR(order_date) AS y FROM tbl_order WHERE status='Delivered' ORDER BY y DESC";
                    $year_res = mysqli_query($conn, $year_sql);
                    $available_years = [];
                    if ($year_res && mysqli_num_rows($year_res) > 0) {
                        while ($yr = mysqli_fetch_assoc($year_res)) {
                            $available_years[] = (int)$yr['y'];
                        }
                    }
                    // Luôn thêm năm hiện tại (ví dụ 2026) để có thể chọn, kể cả khi chưa có dữ liệu
                    if (!in_array($currentYear, $available_years, true)) {
                        array_unshift($available_years, $currentYear);
                    }
                    if (empty($available_years)) {
                        $available_years[] = $currentYear;
                    }
                    if (!in_array($revenue_year, $available_years, true)) {
                        $revenue_year = $currentYear;
                    }

                    // Doanh thu theo tháng của năm được chọn
                    $sql_revenue = "SELECT MONTH(order_date) as m, SUM(total) as monthly_revenue 
                                    FROM tbl_order 
                                    WHERE status='Delivered' 
                                      AND YEAR(order_date) = $revenue_year
                                    GROUP BY MONTH(order_date) 
                                    ORDER BY m ASC";
                    $res_revenue = mysqli_query($conn, $sql_revenue);
                    $revenue_dates = [];   // Nhãn trục X: Tháng 1..12
                    $revenue_amounts = []; // Doanh thu từng tháng
                    $monthly_map = [];
                    if ($res_revenue) {
                        while($row_revenue = mysqli_fetch_assoc($res_revenue)) {
                            $monthIndex = (int)$row_revenue['m'];
                            $monthly_map[$monthIndex] = $row_revenue['monthly_revenue'] ? (float)$row_revenue['monthly_revenue'] : 0;
                        }
                    }
                    $max_monthly_revenue = 0;
                    for ($m = 1; $m <= 12; $m++) {
                        $revenue_dates[] = 'T'.$m;
                        $value = isset($monthly_map[$m]) ? $monthly_map[$m] : 0;
                        $revenue_amounts[] = $value;
                        if ($value > $max_monthly_revenue) {
                            $max_monthly_revenue = $value;
                        }
                    }

                    // ====== THỐNG KÊ TỔNG QUAN SỐ MÓN & SỐ ĐƠN THEO TUẦN/THÁNG/NĂM ======
                    $overview_period = isset($_GET['overview_period']) ? $_GET['overview_period'] : 'week';
                    $overview_where  = "status='Delivered'";
                    $overview_label  = '7 ngày gần nhất';
                    if ($overview_period === 'week') {
                        $overview_where .= " AND order_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                        $overview_label = '7 ngày gần nhất';
                    } elseif ($overview_period === 'month') {
                        $overview_where .= " AND YEAR(order_date) = $currentYear AND MONTH(order_date) = MONTH(CURDATE())";
                        $overview_label = 'Tháng này';
                    } else { // year
                        $overview_where .= " AND YEAR(order_date) = $revenue_year";
                        $overview_label = 'Năm '.$revenue_year;
                    }
                    $sql_overview = "SELECT COUNT(*) AS orders, COALESCE(SUM(qty),0) AS items 
                                     FROM tbl_order 
                                     WHERE $overview_where";
                    $res_overview = mysqli_query($conn, $sql_overview);
                    $overview_orders = 0;
                    $overview_items  = 0;
                    if ($res_overview) {
                        $row_ov = mysqli_fetch_assoc($res_overview);
                        if ($row_ov) {
                            $overview_orders = (int)$row_ov['orders'];
                            $overview_items  = (int)$row_ov['items'];
                        }
                    }

                    // Top món bán chạy theo cùng mốc thời gian tổng quan
                    $top_overview_rows = [];
                    $sql_top_overview = "SELECT food, SUM(qty) AS total_qty, SUM(total) AS total_revenue
                                         FROM tbl_order
                                         WHERE $overview_where
                                         GROUP BY food
                                         ORDER BY total_qty DESC
                                         LIMIT 5";
                    $res_top_overview = mysqli_query($conn, $sql_top_overview);
                    if ($res_top_overview && mysqli_num_rows($res_top_overview) > 0) {
                        while ($r = mysqli_fetch_assoc($res_top_overview)) {
                            $top_overview_rows[] = $r;
                        }
                    }

                    // Chuẩn bị dữ liệu cho biểu đồ tròn "Món bán chạy"
                    $top_food_labels = [];
                    $top_food_qty    = [];
                    if (!empty($top_overview_rows)) {
                        foreach ($top_overview_rows as $row_top) {
                            $top_food_labels[] = $row_top['food'];
                            $top_food_qty[]    = (int)$row_top['total_qty'];
                        }
                    }
                    ?>
                    <h1><?php echo number_format($total_revenue, 0, ',', '.'); ?> đ</h1>
                    <br/>
                    Doanh thu
                </div>
            <div class="clearfix"></div>

            <!-- Charts Section -->
            <div class="charts-container">
                <!-- Bộ lọc thời gian -->
                <form method="get" class="dashboard-filters" style="margin-bottom: 20px; display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                    <div>
                        <label>Thống kê tổng quan:</label>
                        <select name="overview_period">
                            <option value="week" <?php echo $overview_period === 'week' ? 'selected' : ''; ?>>Tuần (7 ngày gần nhất)</option>
                            <option value="month" <?php echo $overview_period === 'month' ? 'selected' : ''; ?>>Tháng này</option>
                            <option value="year" <?php echo $overview_period === 'year' ? 'selected' : ''; ?>>Năm <?php echo $revenue_year; ?></option>
                        </select>
                    </div>
                    <div>
                        <label>Doanh thu theo thời gian - Năm:</label>
                        <select name="revenue_year">
                            <?php foreach ($available_years as $y): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y === $revenue_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="btn-secondary">Xem</button>
                    </div>
                </form>

                <div style="display: flex; gap: 20px; align-items: flex-start; flex-wrap: wrap;">
                    <div class="chart-wrapper" style="flex: 2 1 60%; min-width: 280px;">
                        <h2>Thống kê tổng quan</h2>
                        <canvas id="overviewChart"></canvas>
                    </div>

                    <div class="chart-wrapper" style="flex: 1 1 35%; min-width: 260px;">
                        <h2>Món bán chạy (<?php echo htmlspecialchars($overview_label); ?>)</h2>
                        <canvas id="topFoodChart"></canvas>
                    </div>
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
    const overviewItems = <?php echo (int)$overview_items; ?>;
    const overviewOrders = <?php echo (int)$overview_orders; ?>;
    const overviewLabel  = <?php echo json_encode($overview_label); ?>;
    const revenueDates = <?php echo json_encode($revenue_dates); ?>;
    const revenueAmounts = <?php echo json_encode($revenue_amounts); ?>;
    const revenueYear = <?php echo (int)$revenue_year; ?>;
    const maxMonthlyRevenue = <?php echo (float)$max_monthly_revenue; ?>;
    const topFoodLabels = <?php echo json_encode($top_food_labels); ?>;
    const topFoodQty    = <?php echo json_encode($top_food_qty); ?>;

    // Biểu đồ tổng quan (Bar Chart)
    const overviewCtx = document.getElementById('overviewChart').getContext('2d');
    new Chart(overviewCtx, {
        type: 'bar',
        data: {
            labels: ['Số món', 'Số đơn'],
            datasets: [{
                label: `Số lượng (${overviewLabel})`,
                data: [overviewItems, overviewOrders],
                backgroundColor: [
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(255, 159, 64, 0.8)'
                ],
                borderColor: [
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
                    display: true,
                    text: `Thống kê (${overviewLabel})`
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
                },
                title: {
                    display: true,
                    text: `Doanh thu theo tháng - Năm ${revenueYear}`
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: maxMonthlyRevenue > 0 ? Math.ceil(maxMonthlyRevenue / 6) : 1,
                        callback: function(value) {
                            return new Intl.NumberFormat('vi-VN', { notation: 'compact', maximumFractionDigits: 1 }).format(value) + ' VND';
                        }
                    }
                }
            }
        }
    });

    // Biểu đồ tròn "Món bán chạy"
    const topFoodCtx = document.getElementById('topFoodChart').getContext('2d');
    const hasTopFoodData = topFoodLabels && topFoodLabels.length > 0;
    new Chart(topFoodCtx, {
        type: 'doughnut',
        data: {
            labels: hasTopFoodData ? topFoodLabels : ['Chưa có dữ liệu'],
            datasets: [{
                data: hasTopFoodData ? topFoodQty : [1],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.8)',
                    'rgba(54, 162, 235, 0.8)',
                    'rgba(255, 206, 86, 0.8)',
                    'rgba(75, 192, 192, 0.8)',
                    'rgba(153, 102, 255, 0.8)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(255, 206, 86, 1)',
                    'rgba(75, 192, 192, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'right'
                }
            }
        }
    });
</script>