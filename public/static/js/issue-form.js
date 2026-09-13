(function () {
    // 检查项变化时自动带入标准扣分
    var itemSel = document.getElementById('check_item_id');
    var scoreInput = document.getElementById('deduct_score');
    if (itemSel && scoreInput) {
        itemSel.addEventListener('change', function () {
            var opt = itemSel.options[itemSel.selectedIndex];
            var s = opt && opt.getAttribute('data-score');
            if (s !== null && s !== '') {
                scoreInput.value = s;
            }
        });
    }

    // 选择区域时，优先筛选该区域员工（简化：仅提示，不隐藏）
    var areaSel = document.querySelector('select[name="area_id"]');
    var empSel = document.querySelector('select[name="employee_id"]');
    if (areaSel && empSel) {
        areaSel.addEventListener('change', function () {
            var area = areaSel.value;
            var picked = false;
            Array.prototype.forEach.call(empSel.options, function (o) {
                var ea = o.getAttribute('data-area');
                if (area && ea && String(ea) === String(area) && !picked) {
                    empSel.value = o.value;
                    picked = true;
                }
            });
        });
    }

    // 图片本地预览
    var photo = document.getElementById('photo');
    var preview = document.getElementById('preview');
    if (photo && preview) {
        photo.addEventListener('change', function () {
            var f = photo.files && photo.files[0];
            if (!f) { preview.style.display = 'none'; return; }
            var reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(f);
        });
    }
})();
