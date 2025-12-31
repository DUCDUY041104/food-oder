document.addEventListener('DOMContentLoaded', function(){
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