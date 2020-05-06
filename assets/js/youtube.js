let resetInput = document.getElementsByClassName('clear-btn')[0];
let input = document.getElementById('dow_url');
let btn = document.getElementById('dow-submit');
let result = document.getElementById('result');

resetInput.onclick = function () {
    input.value = '';
    resetInput.style.display = "none";
};

input.addEventListener('input', function () {
    if (input.value.length !== 0) {
        resetInput.style.display = "block";
    } else {
        resetInput.style.display = "none";
    }
    if (input.value.match(/youtube.com/i) || input.value.match(/youtu.be/)) {
        btn.removeAttribute('disabled');
        input.style.borderColor = '#e9e9e9';
        input.style.color = '#242424';
    } else {
        btn.setAttribute('disabled', 'true');
        input.style.borderColor = 'red';
        input.style.color = 'red';
    }
});

if (input.value.length === 0) {
    btn.setAttribute('disabled', 'true');
}

btn.addEventListener('click', function (e) {
    result.innerHTML = '<div class="load"> <div id="dots4"><span></span><span></span><span></span><span></span></div></div>';
    jQuery(document).ready(function () {
        let data = {
            action: 'youtube',
            link: input.value
        };

        jQuery.post(myajax.url, data, function (response) {
            result.innerHTML = response;
        });
    });
    e.preventDefault();
});
