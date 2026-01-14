<?php
// Handle POST and GET before outputting anything to avoid "Cannot modify header information"
require_once('../config/constants.php');
// Protect admin route
require_once('partials/login-check.php');

// Validate and get food ID from GET
if (!isset($_GET['id'])) {
    header('location:' . SITEURL . 'admin/manage-food.php');
    exit();
}

$id = intval($_GET['id']);
if ($id <= 0) {
    header('location:' . SITEURL . 'admin/manage-food.php');
    exit();
}

// Get current food data using prepared statement
$sql2 = "SELECT * FROM tbl_food WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql2);
if (!$stmt) {
    header('location:' . SITEURL . 'admin/manage-food.php');
    exit();
}
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$row2 = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if (!$row2) {
    header('location:' . SITEURL . 'admin/manage-food.php');
    exit();
}

$title = $row2['title'];
$description = $row2['description'];
$price = $row2['price'];
$current_image = $row2['image_name'];
$current_category = $row2['category_id'];
$featured = $row2['featured'];
$active = $row2['active'];

// Handle POST update
if (isset($_POST['submit'])) {
    $errors = [];

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price_raw = $_POST['price'] ?? '';
    $category = intval($_POST['category'] ?? 0);
    $post_id = intval($_POST['id'] ?? 0);
    $current_image_post = $_POST['current_image'] ?? '';

    // Validate ID matches
    if ($post_id !== $id) {
        header('location:' . SITEURL . 'admin/manage-food.php');
        exit();
    }

    // IMPORTANT: never read $_POST['featured'] / $_POST['active'] directly without isset()
    $featured_raw = $_POST['featured'] ?? '';
    $active_raw = $_POST['active'] ?? '';
    $featured = $featured_raw === 'Yes' ? 'Yes' : ($featured_raw === 'No' ? 'No' : '');
    $active = $active_raw === 'Yes' ? 'Yes' : ($active_raw === 'No' ? 'No' : '');

    if ($title === '' || mb_strlen($title) < 3) {
        $errors[] = "Tên món phải có ít nhất 3 ký tự.";
    }

    if ($description === '' || mb_strlen($description) < 10) {
        $errors[] = "Mô tả phải có ít nhất 10 ký tự.";
    }

    if ($price_raw === '' || !is_numeric($price_raw)) {
        $errors[] = "Vui lòng nhập giá hợp lệ.";
    }

    $price = floatval($price_raw);
    if ($price < 0) {
        $errors[] = "Giá không được nhỏ hơn 0.";
    }

    if ($category <= 0) {
        $errors[] = "Vui lòng chọn danh mục.";
    }

    if ($featured === '') {
        $errors[] = "Vui lòng chọn 'Nổi bật' (Có/Không).";
    }

    if ($active === '') {
        $errors[] = "Vui lòng chọn 'Hoạt động' (Có/Không).";
    }

    // Handle image upload (optional)
    $image_name = $current_image_post;
    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] !== '') {
        $original_name = $_FILES['image']['name'];
        $image_parts = explode('.', $original_name);
        $ext = strtolower(end($image_parts));

        // Basic allowlist for image extensions
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        if (!in_array($ext, $allowed, true)) {
            $errors[] = "Định dạng ảnh không hợp lệ. Chỉ cho phép: " . implode(', ', $allowed) . ".";
        } else {
            $image_name = "Food-name-" . rand(0, 9999) . '.' . $ext;
            $src_path = $_FILES['image']['tmp_name'];
            $dest_path = "../image/food/" . $image_name;
            $upload = move_uploaded_file($src_path, $dest_path);

            if ($upload == false) {
                $errors[] = "Tải hình ảnh thất bại.";
            } else {
                // Remove old image if exists
                if ($current_image_post != "" && file_exists("../image/food/" . $current_image_post)) {
                    @unlink("../image/food/" . $current_image_post);
                }
            }
        }
    }

    if (!empty($errors)) {
        // Store errors to show via SweetAlert after page renders
        $_SESSION['form_errors'] = $errors;
        // Keep previous inputs
        $_SESSION['form_old'] = [
            'title' => $title,
            'description' => $description,
            'price' => $price_raw,
            'category' => $category,
            'featured' => $featured_raw,
            'active' => $active_raw,
        ];
        header('location:update-food.php?id=' . $id);
        exit();
    }

    // Update using prepared statement
    $sql3 = "UPDATE tbl_food SET
        title = ?,
        `description` = ?,
        price = ?,
        image_name = ?,
        category_id = ?,
        featured = ?,
        active = ?
        WHERE id = ?";

    $stmt = mysqli_prepare($conn, $sql3);
    if ($stmt) {
        mysqli_stmt_bind_param(
            $stmt,
            "ssdssssi",
            $title,
            $description,
            $price,
            $image_name,
            $category,
            $featured,
            $active,
            $id
        );
        $res3 = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $res3 = false;
    }

    if ($res3 == true) {
        $_SESSION['update'] = "<div class='success'>Cập nhật món ăn thành công!</div>";
        header('location:manage-food.php');
        exit();
    } else {
        $_SESSION['update'] = "<div class='error'>Cập nhật món ăn thất bại!</div>";
        header('location:update-food.php?id=' . $id);
        exit();
    }
}

// Now include menu and render form
include('partials/menu.php');
?>

<div class='main-content'>
    <div class='wrapper'>
        <h1>Cập nhật món ăn</h1>
        <br><br>

        <form action="" method="post" enctype="multipart/form-data">
            <table class='tbl-30'>
            <tr>
                    <td>Tên món: </td>
                    <td>
                        <input type="text" name="title" value="<?php echo htmlspecialchars(isset($_SESSION['form_old']['title']) ? $_SESSION['form_old']['title'] : $title); ?>">
                    </td>
                </tr>
                <tr>
                    <td>Mô tả: </td>
                    <td>
                        <textarea name="description" cols="30" rows="5"><?php echo htmlspecialchars(isset($_SESSION['form_old']['description']) ? $_SESSION['form_old']['description'] : $description); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <td>Giá: </td>
                    <td>
                        <input type="number" name="price" value="<?php echo htmlspecialchars(isset($_SESSION['form_old']['price']) ? $_SESSION['form_old']['price'] : $price); ?>" min="0">
                    </td>
                </tr>
                <tr>
                    <td>Hình ảnh hiện tại: </td>
                    <td>
                        <?php
                        if($current_image == "")
                        {
                            echo "<div class='error'>Chưa có hình ảnh.</div>";
                        }
                        else 
                        {
                            ?>
                            <img src="<?php echo SITEURL; ?>image/food/<?php echo htmlspecialchars($current_image); ?>" width="150px">
                            <?php
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td>Chọn hình ảnh mới: </td>
                    <td>
                        <input type="file" name="image">
                    </td>
                </tr>
                <tr>
                    <td>Danh mục: </td>
                    <td>
                        <select name="category">
                            <?php
                                $sql="SELECT * FROM tbl_category WHERE active='Yes'";
                                $res = mysqli_query($conn, $sql);
                                $count = mysqli_num_rows($res);
                                if($count>0)
                                {
                                    while($row=mysqli_fetch_assoc($res))
                                    {
                                        $category_id = $row['id'];
                                        $category_title = $row['title'];

                                        ?>
                                        <?php
                                        $selected_category = isset($_SESSION['form_old']['category']) ? intval($_SESSION['form_old']['category']) : $current_category;
                                        $selected = ($category_id == $selected_category) ? 'selected' : '';
                                        ?>
                                        <option <?php echo $selected; ?> value="<?php echo $category_id; ?>"><?php echo htmlspecialchars($category_title); ?></option>
                                        
                                        <?php

                                    }
                                }
                                else
                                {
                                    ?>
                                     <option value="0">Không tìm thấy danh mục</option>
                                     <?php
                                }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Nổi bật: </td>
                    <td>
                        <?php
                        $display_featured = isset($_SESSION['form_old']['featured']) ? $_SESSION['form_old']['featured'] : $featured;
                        ?>
                        <input <?php echo ($display_featured == "Yes") ? "checked" : ""; ?> type="radio" name="featured" value="Yes">Có
                        <input <?php echo ($display_featured == "No") ? "checked" : ""; ?> type="radio" name="featured" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td>Hoạt động: </td>
                    <td>
                        <?php
                        $display_active = isset($_SESSION['form_old']['active']) ? $_SESSION['form_old']['active'] : $active;
                        ?>
                        <input <?php echo ($display_active == "Yes") ? "checked" : ""; ?> type="radio" name="active" value="Yes">Có
                        <input <?php echo ($display_active == "No") ? "checked" : ""; ?> type="radio" name="active" value="No">Không
                    </td>
                </tr>
                <tr>
                    <td>
                        <input type="hidden" name="id" value="<?php echo $id; ?>">
                        <input type="hidden" name="current_image" value="<?php echo $current_image; ?>">
                        <input type="submit" name="submit" value="Cập nhật món ăn" class = "btn-secondary">
                    </td>
                </tr>
            </table>
        </form>
        
        <?php
        // Clear old form data after displaying
        unset($_SESSION['form_old']);
        
        // Show validation errors via SweetAlert if any
        if (isset($_SESSION['form_errors']) && !empty($_SESSION['form_errors'])) {
            $errors = $_SESSION['form_errors'];
            unset($_SESSION['form_errors']);
            $msg = implode("\\n", $errors);
            echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Thiếu/không hợp lệ dữ liệu',
                    text: '" . addslashes($msg) . "',
                    confirmButtonText: 'OK'
                });
            </script>";
        }
        ?>
    </div>
</div>
<?php include('partials/footer.php'); ?>