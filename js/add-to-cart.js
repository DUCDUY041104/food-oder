// Hàm thêm vào giỏ hàng
function addToCart(foodId, quantity = 1, note = '') {
    // Kiểm tra đăng nhập
    <?php if(!isset($_SESSION['user_id'])): ?>
    Swal.fire({
        icon: 'warning',
        title: 'Yêu cầu đăng nhập',
        text: 'Vui lòng đăng nhập để thêm vào giỏ hàng!',
        confirmButtonColor: '#ff6b81',
        showCancelButton: true,
        confirmButtonText: 'Đăng nhập',
        cancelButtonText: 'Hủy'
    }).then((result) => {
        if(result.isConfirmed) {
            window.location.href = '<?php echo SITEURL; ?>user/login.php';
        }
    });
    return;
    <?php endif; ?>

    // Hiển thị dialog để nhập số lượng và ghi chú
    Swal.fire({
        title: 'Thêm vào giỏ hàng',
        html: `
            <div style="text-align: left;">
                <label style="display: block; margin-bottom: 5px;">Số lượng:</label>
                <input type="number" id="swal-quantity" class="swal2-input" value="${quantity}" min="1" style="width: 100%;">
                <label style="display: block; margin-top: 15px; margin-bottom: 5px;">Ghi chú (tùy chọn):</label>
                <input type="text" id="swal-note" class="swal2-input" placeholder="VD: ăn cay, không cay, nhiều, ít..." style="width: 100%;">
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: 'Thêm vào giỏ',
        cancelButtonText: 'Hủy',
        confirmButtonColor: '#ff6b81',
        preConfirm: () => {
            const qty = document.getElementById('swal-quantity').value;
            const note = document.getElementById('swal-note').value;
            if(!qty || qty < 1) {
                Swal.showValidationMessage('Số lượng phải lớn hơn 0!');
                return false;
            }
            return {quantity: parseInt(qty), note: note};
        }
    }).then((result) => {
        if(result.isConfirmed) {
            const formData = new FormData();
            formData.append('food_id', foodId);
            formData.append('quantity', result.value.quantity);
            formData.append('note', result.value.note);

            fetch('<?php echo SITEURL; ?>api/add-to-cart.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Thành công!',
                        text: data.message,
                        confirmButtonColor: '#ff6b81',
                        showCancelButton: true,
                        confirmButtonText: 'Xem giỏ hàng',
                        cancelButtonText: 'Tiếp tục mua'
                    }).then((result) => {
                        if(result.isConfirmed) {
                            window.location.href = '<?php echo SITEURL; ?>user/cart.php';
                        }
                    });
                    // Cập nhật badge giỏ hàng nếu có
                    if(typeof updateCartBadge === 'function') {
                        updateCartBadge();
                    }
                } else {
                    Swal.fire('Lỗi!', data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Lỗi!', 'Có lỗi xảy ra, vui lòng thử lại!', 'error');
            });
        }
    });
}

