<style>
.plnfrm, .plnfrm * { font-family: Vazirmatn, Tahoma, 'Segoe UI', Arial, sans-serif; }
.plnfrm { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: 1.25rem; }
.plnfrm__head { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: justify; -ms-flex-pack: justify; justify-content: space-between; gap: .75rem; -ms-flex-wrap: wrap; flex-wrap: wrap; }
.plnfrm__title { font-size: 1.25rem; font-weight: 800; color: #1e293b; margin: 0 0 .2rem; }
.plnfrm__sub { font-size: .8rem; color: #64748b; margin: 0; }
.plnfrm__grid { display: -webkit-box; display: -ms-flexbox; display: flex; gap: 1.25rem; -webkit-box-align: start; -ms-flex-align: start; align-items: flex-start; }
.plnfrm__main { -webkit-box-flex: 1; -ms-flex: 1 1 0%; flex: 1 1 0%; min-width: 0; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: 1rem; }
.plnfrm__side { width: 260px; -ms-flex-negative: 0; flex-shrink: 0; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: 1rem; position: -webkit-sticky; position: sticky; top: 1.5rem; }
@media (max-width: 860px) { .plnfrm__grid { -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; } .plnfrm__side { width: 100%; position: static; } }
.plnfrm__card { background: #fff; border: 1px solid #e2e8f0; border-radius: 1rem; overflow: hidden; -webkit-box-shadow: 0 1px 4px rgba(0,0,0,.05); box-shadow: 0 1px 4px rgba(0,0,0,.05); }
.plnfrm__card-head { padding: .75rem 1.125rem; background: #f8fafc; border-bottom: 1px solid #e2e8f0; font-size: .875rem; font-weight: 700; color: #374151; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; }
.plnfrm__card-body { padding: 1.125rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .875rem; }
.plnfrm__step { width: 22px; height: 22px; border-radius: 50%; background: -webkit-linear-gradient(135deg,#4f46e5,#7c3aed); background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; font-size: .7rem; font-weight: 800; display: -webkit-inline-box; display: -ms-inline-flexbox; display: inline-flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.plnfrm__field { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .3rem; }
.plnfrm__label { font-size: .8rem; font-weight: 600; color: #475569; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .375rem; }
.plnfrm__req { color: #e11d48; }
.plnfrm__hint { font-size: .72rem; font-weight: 400; color: #94a3b8; }
.plnfrm__input { padding: .5625rem .75rem; border: 1.5px solid #e2e8f0; border-radius: .625rem; font-size: .875rem; font-family: Vazirmatn, Tahoma, sans-serif; color: #1e293b; outline: none; -webkit-transition: border-color .15s; transition: border-color .15s; width: 100%; -webkit-box-sizing: border-box; box-sizing: border-box; }
.plnfrm__input:focus { border-color: #4f46e5; -webkit-box-shadow: 0 0 0 3px rgba(79,70,229,.1); box-shadow: 0 0 0 3px rgba(79,70,229,.1); }
.plnfrm__input--mono { font-family: monospace,'Courier New'; }
.plnfrm__textarea { padding: .5625rem .75rem; border: 1.5px solid #e2e8f0; border-radius: .625rem; font-size: .875rem; font-family: Vazirmatn, Tahoma, sans-serif; color: #1e293b; outline: none; resize: vertical; width: 100%; -webkit-box-sizing: border-box; box-sizing: border-box; }
.plnfrm__textarea:focus { border-color: #4f46e5; }
.plnfrm__select { padding: .5625rem .75rem; border: 1.5px solid #e2e8f0; border-radius: .625rem; font-size: .875rem; font-family: Vazirmatn, Tahoma, sans-serif; color: #1e293b; outline: none; width: 100%; -webkit-box-sizing: border-box; box-sizing: border-box; background: #fff; }
.plnfrm__field-note { font-size: .72rem; color: #94a3b8; }
.plnfrm__badge-sale { background: #fef9c3; color: #854d0e; font-size: .65rem; font-weight: 700; padding: .1rem .4rem; border-radius: 9999px; }
.plnfrm__divider { height: 1px; background: #f1f5f9; margin: .125rem 0; }
.plnfrm__row-3 { display: -ms-grid; display: grid; -ms-grid-columns: 1fr 1fr 1fr; grid-template-columns: 1fr 1fr 1fr; gap: .875rem; }
.plnfrm__row-2 { display: -ms-grid; display: grid; -ms-grid-columns: 1fr 1fr; grid-template-columns: 1fr 1fr; gap: .875rem; }
@media (max-width: 640px) { .plnfrm__row-3, .plnfrm__row-2 { grid-template-columns: 1fr; } }
.plnfrm__input-group { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; }
.plnfrm__input-group .plnfrm__input { border-left: none; border-radius: .625rem 0 0 .625rem; }
.plnfrm__input-suffix { padding: .5625rem .75rem; background: #f8fafc; border: 1.5px solid #e2e8f0; border-right: none; border-radius: 0 .625rem .625rem 0; font-size: .875rem; color: #64748b; -ms-flex-negative: 0; flex-shrink: 0; }
.plnfrm__feats { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .5rem; }
.plnfrm__feat-row { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; }
.plnfrm__feat-icon { -ms-flex-negative: 0; flex-shrink: 0; }
.plnfrm__feat-input { -webkit-box-flex: 1; -ms-flex: 1; flex: 1; padding: .4375rem .625rem; border: 1.5px solid #e2e8f0; border-radius: .5rem; font-size: .8375rem; font-family: Vazirmatn, Tahoma, sans-serif; outline: none; }
.plnfrm__feat-input:focus { border-color: #4f46e5; }
.plnfrm__feat-del { background: #fff1f2; color: #be123c; border: 1px solid #fecdd3; border-radius: .375rem; width: 26px; height: 26px; cursor: pointer; font-size: 1rem; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; -webkit-box-pack: center; -ms-flex-pack: center; justify-content: center; -ms-flex-negative: 0; flex-shrink: 0; }
.plnfrm__feat-add { display: -webkit-inline-box; display: -ms-inline-flexbox; display: inline-flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .375rem; padding: .4375rem .875rem; background: #eef2ff; color: #4338ca; border: 1px solid #c7d2fe; border-radius: .625rem; font-size: .8125rem; font-weight: 600; cursor: pointer; font-family: Vazirmatn, Tahoma, sans-serif; margin-top: .375rem; }
.plnfrm__feat-add:hover { background: #e0e7ff; }
.plnfrm__preview { border: 1.5px solid #e2e8f0; border-radius: 1rem; overflow: hidden; -webkit-box-shadow: 0 2px 8px rgba(0,0,0,.07); box-shadow: 0 2px 8px rgba(0,0,0,.07); background: #fff; }
.plnfrm__preview-top { padding: 1.125rem; }
.plnfrm__preview-img { width: 56px; height: 56px; border-radius: .625rem; -o-object-fit: cover; object-fit: cover; display: block; margin: 0 auto .625rem; }
.plnfrm__preview-name { font-size: 1rem; font-weight: 800; color: #1e293b; margin: 0 0 .375rem; text-align: center; }
.plnfrm__preview-price { text-align: center; }
.plnfrm__preview-amount { font-size: 1.25rem; font-weight: 800; }
.plnfrm__preview-feats { padding: .75rem 1.125rem; font-size: .8rem; color: #475569; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .375rem; }
.plnfrm__preview-footer { padding: .875rem 1.125rem; }
.plnfrm__preview-btn { padding: .625rem; border-radius: .625rem; color: #fff; font-weight: 700; font-size: .875rem; text-align: center; }
.plnfrm__color-row { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .5rem; -webkit-box-align: center; -ms-flex-align: center; align-items: center; }
.plnfrm__color-input { width: 40px; height: 40px; border: 1.5px solid #e2e8f0; border-radius: .5rem; cursor: pointer; padding: 2px; background: none; -ms-flex-negative: 0; flex-shrink: 0; }
.plnfrm__swatches { display: -webkit-box; display: -ms-flexbox; display: flex; gap: .375rem; -ms-flex-wrap: wrap; flex-wrap: wrap; margin-top: .375rem; }
.plnfrm__swatch { width: 20px; height: 20px; border-radius: 50%; border: 2px solid transparent; cursor: pointer; -webkit-transition: -webkit-transform .15s; transition: transform .15s; }
.plnfrm__swatch:hover { -webkit-transform: scale(1.2); transform: scale(1.2); border-color: rgba(0,0,0,.15); }
.plnfrm__upload-area { border: 2px dashed #c7d2fe; border-radius: .75rem; padding: .875rem; text-align: center; cursor: pointer; -webkit-transition: border-color .15s; transition: border-color .15s; }
.plnfrm__upload-area:hover { border-color: #4f46e5; background: #f5f3ff; }
.plnfrm__upload-input { display: none; }
.plnfrm__upload-label { cursor: pointer; display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .375rem; }
.plnfrm__upload-label span { font-size: .78rem; color: #4f46e5; font-weight: 600; }
.plnfrm__upload-label small { font-size: .72rem; color: #94a3b8; }
.plnfrm__current-img__preview { width: 70px; height: 70px; object-fit: cover; border-radius: .5rem; display: block; margin: 0 auto .5rem; border: 1px solid #e2e8f0; }
.plnfrm__checks { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-orient: vertical; -webkit-box-direction: normal; -ms-flex-direction: column; flex-direction: column; gap: .5rem; }
.plnfrm__check { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; cursor: pointer; font-size: .8375rem; color: #374151; }
.plnfrm__check input { width: 15px; height: 15px; cursor: pointer; accent-color: #4f46e5; }
.plnfrm__submit { display: block; width: 100%; padding: .75rem; background: -webkit-linear-gradient(135deg,#4f46e5,#7c3aed); background: linear-gradient(135deg,#4f46e5,#7c3aed); color: #fff; font-size: .9375rem; font-weight: 700; border: none; border-radius: .875rem; cursor: pointer; font-family: Vazirmatn, Tahoma, sans-serif; -webkit-box-shadow: 0 4px 12px rgba(79,70,229,.3); box-shadow: 0 4px 12px rgba(79,70,229,.3); -webkit-transition: opacity .15s; transition: opacity .15s; }
.plnfrm__submit:hover { opacity: .9; }
.plnfrm__cancel { display: block; text-align: center; padding: .5rem; color: #94a3b8; font-size: .8125rem; text-decoration: none; margin-top: .25rem; }
.plnfrm__cancel:hover { color: #475569; }

/* products grid */
.plnfrm__products-grid { display: -ms-grid; display: grid; -ms-grid-columns: 1fr 1fr; grid-template-columns: 1fr 1fr; gap: .5rem; max-height: 280px; overflow-y: auto; }
@media (max-width: 600px) { .plnfrm__products-grid { grid-template-columns: 1fr; } }
.plnfrm__product-item { border: 1.5px solid #e2e8f0; border-radius: .625rem; padding: .5rem .75rem; -webkit-transition: border-color .15s, background .15s; transition: border-color .15s, background .15s; }
.plnfrm__product-item--active { border-color: #4f46e5; background: #f0f4ff; }
.plnfrm__product-label { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .5rem; cursor: pointer; }
.plnfrm__product-cb { width: 14px; height: 14px; accent-color: #4f46e5; cursor: pointer; -ms-flex-negative: 0; flex-shrink: 0; }
.plnfrm__product-name { font-size: .8125rem; color: #334155; font-weight: 500; }
.plnfrm__product-discount { display: -webkit-box; display: -ms-flexbox; display: flex; -webkit-box-align: center; -ms-flex-align: center; align-items: center; gap: .375rem; margin-top: .375rem; }
.plnfrm__disc-input { width: 70px; padding: .3rem .5rem; border: 1px solid #c7d2fe; border-radius: .375rem; font-size: .8rem; font-family: Vazirmatn,Tahoma,sans-serif; }
.plnfrm__disc-pct { font-size: .8rem; color: #64748b; }
</style>
