document.addEventListener('DOMContentLoaded', function(){
    function validateFormBeforeSubmit() {
        const title = (document.querySelector('#check_title')?.value || '').trim();
        const description = (document.querySelector('#check_description')?.value || '').trim();
        const price = (document.querySelector('#check_price')?.value || '').trim();
        const featured = document.querySelector('input[name="featured"]:checked')?.value || '';
        const active = document.querySelector('input[name="active"]:checked')?.value || '';
        const category = document.querySelector('select[name="category"]')?.value || '';

        const errors = [];
        if (title.length < 3) errors.push('Tên món phải có ít nhất 3 ký tự.');
        if (description.length < 10) errors.push('Mô tả phải có ít nhất 10 ký tự.');
        if (price === '') errors.push('Bạn chưa nhập giá tiền!');
        if (category === '' || category === '0') errors.push('Vui lòng chọn danh mục.');
        if (!featured) errors.push("Vui lòng chọn 'Nổi bật' (Có/Không).");
        if (!active) errors.push("Vui lòng chọn 'Hoạt động' (Có/Không).");

        if (errors.length > 0) {
            if (window.Swal) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Vui lòng kiểm tra lại',
                    text: errors.join('\n'),
                    confirmButtonText: 'OK'
                });
            } else {
                alert(errors.join('\n'));
            }
            return false;
        }
        return true;
    }

    const form = document.querySelector('form');
    if (form) {
        form.addEventListener('submit', function (e) {
            if (!validateFormBeforeSubmit()) {
                e.preventDefault();
            }
        });
    }

    document.querySelector('#check_title').onkeyup = function(){
        if(document.querySelector('#check_title').value.length >= 3)
            document.querySelector('#warning_title').innerHTML ='';
        else
            document.querySelector('#warning_title').innerHTML = 'Nội dung phải lớn hơn 2 kí tự!';                        
        };

    document.querySelector('#check_description').onkeyup = function(){
        if(document.querySelector('#check_description').value.length >= 10)
            document.querySelector('#warning_description').innerHTML ='';
        else
            document.querySelector('#warning_description').innerHTML = 'Nội dung phải lớn hơn 10 kí tự!';                        
    };

    document.querySelector('#check_price').onkeyup = function(){
        if(document.querySelector('#check_price').value == '')
            document.querySelector('#warning_price').innerHTML = 'Bạn chưa nhập giá tiền!';
        else
            document.querySelector('#warning_price').innerHTML = '';                        
    };

    document.querySelector('#submit_hover').onmouseover = function(){
        this.style.backgroundColor = "red";
    };
        
    document.querySelector('#submit_hover').onmouseout = function(){
        this.style.backgroundColor = "#7bed9f";
    };
});