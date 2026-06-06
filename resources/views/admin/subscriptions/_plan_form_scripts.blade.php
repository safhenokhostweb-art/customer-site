<script>
function toggleDiscount(productId, checked) {
    var item = document.getElementById('prod-item-' + productId);
    var disc = document.getElementById('disc-' + productId);
    if (item) item.classList.toggle('plnfrm__product-item--active', checked);
    if (disc) disc.style.display = checked ? '' : 'none';
}

(function () {
    var colorPicker = document.getElementById('color-picker');
    var colorText   = document.getElementById('color-text');
    var prevTop     = document.getElementById('prev-top');
    var prevPrice   = document.getElementById('prev-price');
    var prevBtn     = document.getElementById('prev-top-color');

    function setColorVal(v) {
        colorPicker.value = v; colorText.value = v;
        if (prevTop)   prevTop.style.borderTopColor = v;
        if (prevPrice) prevPrice.style.color = v;
        if (prevBtn)   prevBtn.style.background = v;
    }
    window.setColor = setColorVal;
    colorPicker.addEventListener('input', function () { setColorVal(this.value); });
    colorText.addEventListener('input', function () {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) setColorVal(this.value);
    });

    var nameInput = document.getElementById('plan-name');
    var slugInput = document.getElementById('plan-slug');
    var prevName  = document.getElementById('prev-name');
    if (nameInput) {
        nameInput.addEventListener('input', function () {
            if (prevName) prevName.textContent = this.value || 'نام پلن';
        });
    }
    if (slugInput) {
        slugInput.addEventListener('input', function () { this.dataset.manual = '1'; });
    }

    var priceMain = document.getElementById('price-main');
    if (priceMain && prevPrice) {
        priceMain.addEventListener('input', function () {
            var v = parseInt(this.value) || 0;
            prevPrice.textContent = v === 0 ? 'رایگان' : v.toLocaleString('fa-IR') + ' تومان';
        });
    }

    var featsContainer = document.getElementById('features-container');
    var prevFeats = document.getElementById('prev-feats');
    function updateFeats() {
        if (!featsContainer || !prevFeats) return;
        var inputs = featsContainer.querySelectorAll('input[name="features[]"]');
        prevFeats.innerHTML = '';
        inputs.forEach(function (inp) {
            if (inp.value.trim()) {
                var d = document.createElement('div');
                d.style.cssText = 'display:flex;align-items:center;gap:.375rem';
                d.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="12" height="12"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>' + inp.value;
                prevFeats.appendChild(d);
            }
        });
    }
    if (featsContainer) featsContainer.addEventListener('input', updateFeats);

    var addBtn = document.getElementById('add-feature');
    if (addBtn && featsContainer) {
        addBtn.addEventListener('click', function () {
            var div = document.createElement('div');
            div.className = 'plnfrm__feat-row';
            div.innerHTML = '<svg fill="none" stroke="currentColor" viewBox="0 0 24 24" width="14" height="14" class="plnfrm__feat-icon" style="color:#4f46e5"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>'
                + '<input type="text" name="features[]" placeholder="امکان جدید..." class="plnfrm__feat-input">'
                + '<button type="button" onclick="this.closest(\'.plnfrm__feat-row\').remove()" class="plnfrm__feat-del">×</button>';
            featsContainer.appendChild(div);
            div.querySelector('input').focus();
            div.querySelector('input').addEventListener('input', updateFeats);
        });
    }

    var imgInput   = document.getElementById('img-input');
    var imgPreview = document.getElementById('img-preview');
    var prevImgWrap= document.getElementById('prev-img-wrap');
    var prevImg    = document.getElementById('prev-img');
    if (imgInput) {
        imgInput.addEventListener('change', function () {
            var file = this.files[0]; if (!file) return;
            var url = URL.createObjectURL(file);
            if (imgPreview) { imgPreview.src = url; imgPreview.style.display = 'block'; }
            if (prevImg)    { prevImg.src = url; }
            if (prevImgWrap) { prevImgWrap.style.display = ''; }
        });
    }
})();
</script>
