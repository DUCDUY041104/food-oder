<!-- footer section starts-->
<div class="footer">
            <div class="wrapper">
            <p class="text-center">All rights reserved. Designed By <a href="">5 anh em</a></p>
            </div>
         </div>
        <!-- footer section ends-->
        
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            <?php
            // Helper function to extract message from HTML div
            function extractMessage($html) {
                $html = strip_tags($html);
                return trim($html);
            }
            
            // Check for various session messages
            $sessionMessages = [
                'add', 'update', 'delete', 'remove', 'upload', 
                'login', 'register-success', 'login-success',
                'user-not-found', 'pwd-not-match', 'change-pwd',
                'no-category-found', 'failed-remove', 'order',
                'no-login-message'
            ];
            
            foreach($sessionMessages as $key) {
                if(isset($_SESSION[$key]) && !empty($_SESSION[$key])) {
                    $message = extractMessage($_SESSION[$key]);
                    if(!empty($message)) {
                        // Determine icon based on message content
                        $icon = 'info';
                        $title = 'Thông báo';
                        
                        if(strpos(strtolower($_SESSION[$key]), 'success') !== false || 
                           strpos(strtolower($message), 'thành công') !== false ||
                           strpos(strtolower($message), 'successfully') !== false) {
                            $icon = 'success';
                            $title = 'Thành công!';
                        } elseif(strpos(strtolower($_SESSION[$key]), 'error') !== false || 
                                 strpos(strtolower($message), 'lỗi') !== false ||
                                 strpos(strtolower($message), 'failed') !== false ||
                                 strpos(strtolower($message), 'not') !== false) {
                            $icon = 'error';
                            $title = 'Lỗi!';
                        } elseif(strpos(strtolower($message), 'warning') !== false) {
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
    </body>
</html>