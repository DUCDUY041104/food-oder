<?php
// Handle POST before outputting anything to avoid "Cannot modify header information"
require_once('../config/constants.php');
// Protect admin route
require_once('partials/login-check.php');

if (isset($_POST['submit'])) {
    $errors = [];

    $title = trim($_POST['title'] ?? '');
    $featured_raw = $_POST['featured'] ?? '';
    $active_raw = $_POST['active'] ?? '';

    $featured = $featured_raw === 'Yes' ? 'Yes' : ($featured_raw === 'No' ? 'No' : '');
    $active = $active_raw === 'Yes' ? 'Yes' : ($active_raw === 'No' ? 'No' : '');

    if ($title === '' || mb_strlen($title) < 3) {
        $errors[] = "Tên danh mục phải có ít nhất 3 ký tự.";
    }

    if ($featured === '') {
        $errors[] = "Vui lòng chọn 'Nổi bật' (Có/Không).";
    }

    if ($active === '') {
        $errors[] = "Vui lòng chọn 'Hoạt động' (Có/Không).";
    }

    // Handle image upload (optional)
    $image_name = "";
    if (isset($_FILES['image']['name']) && $_FILES['image']['name'] !== '') {
        $original_name = $_FILES['image']['name'];
        $image_parts = explode('.', $original_name);
        $ext = strtolower(end($image_parts));

        // Basic allowlist for image extensions
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];
        if (!in_array($ext, $allowed, true)) {
            $errors[] = "Định dạng ảnh không hợp lệ. Chỉ cho phép: " . implode(', ', $allowed) . ".";
        } else {
            $image_name = "Food_Category_" . rand(0, 999) . '.' . $ext;
            $source_path = $_FILES['image']['tmp_name'];
            $destination_path = "../image/category/" . $image_name;
            $upload = move_uploaded_file($source_path, $destination_path);
            if ($upload == false) {
                $errors[] = "Tải hình ảnh thất bại.";
            }
        }
    }

    if (!empty($errors)) {
        // Store errors to show via SweetAlert after page renders
        $_SESSION['form_errors'] = $errors;
        // Keep previous inputs (optional)
        $_SESSION['form_old'] = [
            'title' => $title,
            'featured' => $featured_raw,
            'active' => $active_raw,
        ];
        header('location:add-category.php');
        exit();
    }

    // Insert using prepared statement
    $sql = "INSERT INTO tbl_category (title, image_name, featured, active) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ssss", $title, $image_name, $featured, $active);
        $res = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    } else {
        $res = false;
    }

    if ($res == true) {
        $_SESSION['add'] = "<div class='success'>Thêm danh mục thành công!</div>";
        header('location:manage-category.php');
        exit();
    }

    $_SESSION['add'] = "<div class='error'>Thêm danh mục thất bại!</div>";
    header('location:add-category.php');
    exit();
}

require 'partials/menu.php';
?>

<div class="main-content">
    <div class="wrapper">
        <h1>Thêm danh mục</h1>
        
        <br><br>
        <!-- add category form starts -->
        <form action="" method="post" enctype="multipart/form-data" id="addCategoryForm">
            <table class="tbn-30">
                <tr>
                    <td>Tên danh mục: </td>
                    <td>
                        <input
                            type="text"
                            name="title"
                            id="check_category_title"
                            placeholder="Tên danh mục"
                            value="<?php echo htmlspecialchars($_SESSION['form_old']['title'] ?? ''); ?>"
                        >
                    </td>
                </tr>
                <tr>
                    <td>Chọn hình ảnh: </td>
                    <td>
                        <input type="file" name="image" id="">
                    </td>
                </tr>
                <tr>
                    <td>Nổi bật: </td>
                    <td>
                        <input type="radio" name="featured" value="Yes" <?php echo (($_SESSION['form_old']['featured'] ?? '') === 'Yes') ? 'checked' : ''; ?>>Có
                        <input type="radio" name="featured" value="No" <?php echo (($_SESSION['form_old']['featured'] ?? '') === 'No') ? 'checked' : ''; ?>>Không
                    </td>
                </tr>
                <tr>
                    <td>Hoạt động: </td>
                    <td>
                        <input type="radio" name="active" value="Yes" <?php echo (($_SESSION['form_old']['active'] ?? '') === 'Yes') ? 'checked' : ''; ?>>Có
                        <input type="radio" name="active" value="No" <?php echo (($_SESSION['form_old']['active'] ?? '') === 'No') ? 'checked' : ''; ?>>Không
                    </td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="submit" value="Thêm danh mục" name="submit" class="btn-secondary">
                    </td>
                </tr>
            </table>
        </form>

        <script>
            (function () {
                // Show server-side validation errors (if any)
                const errors = <?php echo json_encode($_SESSION['form_errors'] ?? []); ?>;
                if (errors && errors.length > 0) {
                    if (window.Swal) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Thiếu/không hợp lệ dữ liệu',
                            text: errors.join('\\n'),
                            confirmButtonText: 'OK'
                        });
                    } else {
                        alert(errors.join('\\n'));
                    }
                }
                <?php unset($_SESSION['form_errors'], $_SESSION['form_old']); ?>

                // Client-side validation (prevent submit)
                const form = document.getElementById('addCategoryForm');
                if (!form) return;
                form.addEventListener('submit', function (e) {
                    const title = (document.getElementById('check_category_title')?.value || '').trim();
                    const featured = document.querySelector('input[name="featured"]:checked')?.value || '';
                    const active = document.querySelector('input[name="active"]:checked')?.value || '';
                    const errs = [];
                    if (title.length < 3) errs.push('Tên danh mục phải có ít nhất 3 ký tự.');
                    if (!featured) errs.push("Vui lòng chọn 'Nổi bật' (Có/Không).");
                    if (!active) errs.push("Vui lòng chọn 'Hoạt động' (Có/Không).");
                    if (errs.length > 0) {
                        e.preventDefault();
                        if (window.Swal) {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Vui lòng kiểm tra lại',
                                text: errs.join('\\n'),
                                confirmButtonText: 'OK'
                            });
                        } else {
                            alert(errs.join('\\n'));
                        }
                    }
                });
            })();
        </script>


        <!-- add category form ends -->
    </div>
</div>

<?php require 'partials/footer.php' ?>