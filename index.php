<?php
session_start();
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
require __DIR__ . '/includes/connect.php';
require __DIR__ . '/includes/product_catalog.php';
require_once __DIR__ . '/includes/coupon_common.php';
$featuredProducts = get_catalog_products($conn, 0, '', true, 4);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
<title>Cocoon Vietnam - Mỹ phẩm 100% thuần chay</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;1,500&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400;1,500&display=swap" rel="stylesheet">
<link href="assets/css/style.css" rel="stylesheet">
<style>
/* ===== Design tokens (màu rút ra từ ảnh) ===== */
:root{
  --primary:#7D3F1E; --primary-dark:#5a2d15; --secondary:#C5A25D; --bg:#f6f1ea;
  --text:#333333; --muted:#8a7f74; --sale:#b5471f; --radius:10px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Barlow',system-ui,sans-serif;color:var(--text);background:#fff;line-height:1.5}
a{color:inherit;text-decoration:none}
img{max-width:100%;display:block}
button{font:inherit;cursor:pointer;border:0}
.wrap{max-width:1200px;margin:0 auto;padding:0 20px}

/* ===== Header ===== */
.header{position:sticky;top:0;z-index:110;background:#fff;padding:14px 0 12px;box-shadow:0 1px 0 #eee}
.header .row{display:grid;grid-template-columns:1fr auto 1fr;align-items:center}
.logo{grid-column:2;grid-row:1;display:flex;flex-direction:column;align-items:center;font-family:"Cormorant Garamond",Georgia,serif;font-style:italic;font-weight:500;color:#3E3228}
.logo-the{margin-bottom:-4px;font-size:13px;line-height:1}
.logo-name{font-size:28px;line-height:.9}
.logo-country{margin-top:4px;color:#8D6E63;font-family:Barlow,system-ui,sans-serif;font-size:8px;font-style:normal;letter-spacing:.38em}
.actions{grid-column:3;grid-row:1;justify-self:end;display:flex;align-items:center;gap:18px;font-size:14px;font-weight:600}
.actions a,.actions button{transition:.2s}
.actions a:hover{color:var(--primary)}
.cart{background:#fffaf0;border:1px solid var(--primary);color:var(--primary);padding:8px 14px;border-radius:6px;font-weight:600}
.cart:hover{background:var(--primary);color:#fff}
.badge{display:inline-block;min-width:18px;height:18px;border-radius:9px;background:#222;color:#fff;font-size:10px;line-height:18px;text-align:center;margin-left:4px}
.burger{background:none;display:grid;gap:5px;padding:6px}
.burger i{display:block;width:20px;height:2px;background:#444}
.burger i:nth-child(2){width:14px;margin-left:auto}
.menu-wrap{position:relative}
.menu-panel{position:absolute;top:calc(100% + 12px);right:0;z-index:60;min-width:190px;padding:6px;background:#fff;border:1px solid #e8e2d9;box-shadow:0 8px 22px rgba(0,0,0,.12)}
.menu-panel[hidden]{display:none}
.menu-panel a{display:block;padding:10px 12px;font-size:14px;color:var(--text);white-space:nowrap}
.menu-panel a:hover{background:var(--bg);color:var(--primary)}
.menu-panel form{margin:0}
.menu-panel button{width:100%;padding:10px 12px;text-align:left;background:transparent;color:var(--text);font:inherit;cursor:pointer}
.menu-panel button:hover{background:var(--bg);color:var(--primary)}
.search{margin-top:12px;position:relative}
.search input{width:100%;background:var(--bg);border:0;border-radius:8px;padding:12px 44px 12px 14px;font:inherit;font-size:14px;outline:none;transition:.2s}
.search input:focus{box-shadow:0 0 0 2px var(--primary)}
.search > button{position:absolute;right:14px;top:50%;transform:translateY(-50%);padding:0;background:none;border:0;color:inherit}
.search svg{display:block}
.search-suggestions{position:absolute;top:calc(100% + 6px);left:0;right:0;z-index:70;max-height:360px;overflow-y:auto;background:#fff;border:1px solid #e8e2d9;border-radius:8px;box-shadow:0 10px 28px rgba(0,0,0,.14)}
.search-suggestions[hidden]{display:none}
.search-suggestion{display:flex;width:100%;align-items:center;justify-content:space-between;gap:16px;padding:12px 14px;text-align:left;background:#fff;border:0;border-bottom:1px solid #f0ebe1;color:var(--text)}
.search-suggestion:last-child{border-bottom:0}
.search-suggestion:hover,.search-suggestion[aria-selected="true"]{background:var(--bg)}
.search-suggestion{position:static;transform:none;border-radius:0;font:inherit;cursor:pointer}
.search-suggestion-name{font-size:14px;font-weight:600}
.search-suggestion-meta{display:block;margin-top:2px;color:var(--muted);font-size:12px}
.search-suggestion-price{flex:none;color:var(--primary);font-size:13px;font-weight:600}
.search-suggestion-empty{padding:12px 14px;color:var(--muted);font-size:13px}

/* ===== Features ===== */
.features{display:flex;justify-content:center;gap:60px;padding:36px 20px 20px;flex-wrap:wrap}
.feat{text-align:center;width:190px}
.feat .ic{width:80px;height:80px;border-radius:50%;margin:0 auto 12px;display:grid;place-items:center;font-size:30px;transition:.3s}
.feat:hover .ic{transform:translateY(-4px) scale(1.05)}
.feat h3{font-size:16px;font-weight:500}
.feat p{font-size:11px;color:var(--muted);margin-top:4px}

/* ===== Giới thiệu thương hiệu ===== */
.brand-philosophy-section{position:relative;isolation:isolate;display:grid;min-height:440px;place-items:center;overflow:hidden;padding:80px 24px;background:#f4eee5}
.philosophy-bg,.philosophy-bg::after{position:absolute;inset:0}
.philosophy-bg{z-index:-2}
.philosophy-bg img{width:100%;height:100%;object-fit:cover;object-position:center 42%;opacity:.42}
.philosophy-bg::after{content:"";background:linear-gradient(90deg,rgba(246,241,234,.72),rgba(246,241,234,.36) 50%,rgba(246,241,234,.74))}
.philosophy-watermark{position:absolute;z-index:-1;top:50%;left:50%;width:max-content;max-width:100%;transform:translate(-50%,-50%);color:rgba(125,63,30,.09);font-size:clamp(42px,10vw,140px);font-weight:800;line-height:1;letter-spacing:.04em;white-space:nowrap}
.philosophy-card{width:min(100%,700px);padding:clamp(28px,5vw,58px);background:rgba(255,255,255,.91);box-shadow:0 14px 45px rgba(58,40,26,.14);text-align:center;backdrop-filter:blur(5px)}
.philosophy-card .card-title{color:var(--primary-dark);font-family:"Cormorant Garamond",Georgia,serif;font-size:clamp(34px,5vw,54px);font-weight:500;line-height:1.1}
.philosophy-card .card-title span{display:block;color:var(--primary);font-family:Barlow,system-ui,sans-serif;font-size:.48em;font-weight:700;letter-spacing:.2em}
.philosophy-card .card-desc{max-width:560px;margin:24px auto;color:#62564c;font-size:16px;line-height:1.8}
.philosophy-card .btn-explore{display:inline-flex;align-items:center;gap:12px;padding:13px 22px;background:var(--primary);color:#fff;font-size:12px;font-weight:700;letter-spacing:.1em;transition:background .2s}
.philosophy-card .btn-explore:hover{background:var(--primary-dark)}
.about{position:relative;padding:50px 0 40px;display:grid;grid-template-columns:1fr 1fr;gap:30px;align-items:start}
.card{background:#fff;border-radius:var(--radius);box-shadow:0 4px 16px rgba(0,0,0,.15)}
.tag{padding:60px 40px;position:relative;overflow:hidden;min-height:220px}
.tag small{font-family:'Barlow',sans-serif;font-style:italic;font-size:11px;color:var(--primary);position:absolute;top:14px;left:20px}
.tag q{font-size:clamp(28px,3.4vw,40px);color:var(--primary);display:block;line-height:1.25;quotes:"“" "”"}
.tag q em{font-family:'Barlow',sans-serif}
.tag .ghost{position:absolute;inset:0;font-family:'Barlow',sans-serif;font-style:italic;font-size:110px;line-height:.95;color:#f6efdf;z-index:0;padding:10px}
.tag q{position:relative;z-index:1}
.about .txt small{color:var(--primary);font-size:20px;font-weight:500}
.about .txt h2{color:var(--primary);font-size:clamp(22px,2.6vw,28px);line-height:1.3;margin:8px 0}
.about .txt p{color:var(--muted);font-size:20px}
.lead{grid-column:1/-1;margin:-20px 0 0 12%;padding:26px;display:grid;grid-template-columns:.8fr 1.2fr;gap:20px;position:relative;z-index:2}
.lead h4{font-family:'Barlow',sans-serif;font-style:italic;font-weight:400;font-size:34px;line-height:1.1;color:#C5A25D}
.lead ul{list-style:none}
.lead li{display:grid;grid-template-columns:100px 1fr;gap:20px;padding:20px 0;border-top:1px solid #eadfc3;font-size:20px;color:var(--primary)}
.lead li:first-child{border:0}
.lead li b{font-family:'Barlow',sans-serif;font-style:italic;font-weight:400;font-size:15px;line-height:1.2}
/* Đưa khối "COCOON CUNG CẤP" xuống dưới khung trích dẫn */
.about .tag{grid-column:1/-1}      /* khung trích dẫn chiếm cả hàng */
.about .txt{grid-column:1/-1}      /* khối chữ chiếm cả hàng, nằm bên dưới */
.lead{margin-top:0}                /* bỏ phần đè lên khối chữ ở trên */
/* ===== Coupon ===== */
.coupons{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;padding:30px 0}
.coupon{position:relative;display:flex;border:1px solid #e2e2e2;border-radius:8px;overflow:hidden;background:#fff;transition:.25s}
.coupon:hover{transform:translateY(-3px);box-shadow:0 8px 20px rgba(0,0,0,.1)}
.coupon .l{flex:1;padding:22px 14px 12px}
.coupon .code{color:var(--sale);font-size:11px;font-weight:700}
.coupon .big{display:flex;align-items:baseline;gap:8px;margin:2px 0 6px}
.coupon .big span{background:#f3e9d3;color:var(--primary);font-style:italic;font-family:'Barlow',sans-serif;font-size:20px;padding:0 8px}
.coupon .big strong{font-family:'Barlow',sans-serif;font-style:italic;font-size:56px;font-weight:400;color:var(--primary);line-height:1}
.coupon p{font-size:11px;color:#555}
.coupon .foot{display:flex;justify-content:space-between;align-items:center;margin-top:8px}
.coupon .foot a{color:var(--primary);font-size:11px;text-decoration:underline}
.coupon .copy{background:var(--primary);color:#fff;font-size:10px;font-weight:600;padding:6px 10px;border-radius:4px;transition:.2s}
.coupon .copy:hover{background:var(--primary-dark)}
.coupon .r{width:40px;border-left:2px dashed #ddd;display:grid;place-items:center;color:#C5A25D;font-weight:800;writing-mode:vertical-rl;letter-spacing:3px;font-size:14px}
.coupon::before{content:"COUPON";position:absolute;top:8px;left:-26px;background:#C5A25D;color:#333;font-size:9px;font-weight:700;padding:2px 26px;transform:rotate(-45deg)}

/* ===== Sản phẩm ===== */
.collection{background:var(--bg);padding:40px 0 60px}
.collection .head{display:flex;justify-content:space-between;align-items:end;margin-bottom:24px}
.collection small{color:var(--primary);font-size:13px;font-weight:500}
.collection h2{color:var(--primary);font-size:26px;font-weight:600}
.collection .more{font-size:13px;font-weight:500;transition:.2s}
.collection .more:hover{color:var(--primary);letter-spacing:.5px}
.grid{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
.product{background:#fff;border-radius:8px;padding:10px 10px 18px;position:relative;text-align:center;transition:.25s;display:flex;flex-direction:column}
.product:hover{transform:translateY(-4px);box-shadow:0 10px 24px rgba(0,0,0,.12)}
.product .pic{position:relative;border-radius:6px;overflow:hidden;aspect-ratio:1/1;background:#efe6d8}
.product .pic img{width:100%;height:100%;object-fit:cover;transition:.4s}
.product:hover .pic img{transform:scale(1.06)}
.off{position:absolute;top:8px;left:8px;background:var(--sale);color:#fff;font-size:11px;font-weight:700;padding:2px 8px;border-radius:3px;z-index:2}
.heart{position:absolute;top:8px;right:8px;width:34px;height:34px;border-radius:50%;background:#fff;z-index:2;font-size:16px;transition:.2s}
.heart:hover,.heart.on{color:var(--sale);transform:scale(1.1)}
.bubble{position:absolute;right:8px;bottom:8px;background:#C5A25D;color:#fff;font-weight:800;font-size:15px;width:56px;height:56px;border-radius:50%;display:grid;place-items:center;z-index:2}
.product .brand{color:var(--muted);font-size:12px;margin:12px 0 10px}
.product .volume-label{align-self:center;margin:0 0 8px;padding:3px 10px;border:1px solid #e5d8c8;border-radius:999px;color:var(--primary);font-size:11px;line-height:1.4}
.product h3{font-size:14px;font-weight:500;line-height:1.9;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-transform:uppercase;padding:0 6px}
.cur{color:var(--sale);font-weight:700;font-size:15px;margin-top:8px}
.cur s{color:var(--muted);font-weight:400;font-size:12px;margin-left:6px}
.btns{display:none;gap:6px;justify-content:center;margin-top:14px}
.product:hover .btns{display:flex}
.btns .q{background:var(--secondary);padding:8px 12px;font-size:13px;font-weight:500;transition:.2s}
.btns .b{background:var(--primary);color:#fff;padding:8px 12px;font-size:13px;font-weight:600;transition:.2s}
.btns button:hover{filter:brightness(.92)}

/* ===== Newsletter + Footer ===== */
.news{background:var(--bg);position:relative;overflow:hidden;padding:60px 0 70px;clip-path:polygon(0 0,100% 0,100% 88%,0 100%)}
.news h2{color:var(--primary);font-size:clamp(34px,5vw,50px);font-weight:600;line-height:1.15;max-width:380px}
.news p{color:var(--primary);font-size:13px;margin:14px 0}
.news form{display:flex;gap:10px;max-width:420px}
.news input{flex:1;padding:12px;border:1px solid #ddd;border-radius:6px;font:inherit;font-size:13px;outline:none}
.news input:focus{border-color:var(--primary)}
.news button{background:var(--primary);color:#fff;font-weight:600;font-size:12px;padding:0 24px;border-radius:6px;transition:.2s}
.news button:hover{background:var(--primary-dark)}
.news small{color:var(--muted);font-size:12px}
.lemon{position:absolute;right:8%;top:40px;width:200px;height:200px;border-radius:48% 52% 50% 50%;background:var(--secondary);display:grid;place-items:center}
.lemon::after{content:"";width:100px;height:100px;border-radius:50%;background:var(--bg)}
.footer-layout{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1fr);background:#1f1c18;color:#fff}
.footer-art{min-height:360px;overflow:hidden;background:#8d6e24}
.footer-art img{width:100%;height:100%;object-fit:cover;object-position:center}
.footer{max-width:none;margin:0;padding:40px clamp(24px,4vw,64px);display:grid;grid-template-columns:1.2fr 1fr 1fr;align-content:center;gap:30px;font-size:13px}
.footer h5{color:var(--secondary);font-size:13px;font-weight:600;padding-bottom:6px;margin-bottom:14px;position:relative}
.footer h5::after{content:"";position:absolute;left:0;bottom:0;width:80px;height:1px;background:var(--secondary)}
.footer .blue{color:#e9dece;line-height:1.8}
.footer li{list-style:none;margin-bottom:10px}
.footer a{transition:.2s}
.footer a:hover{color:var(--primary)}
.soc{display:flex;gap:10px;margin-top:12px}
.soc a{width:28px;height:28px;border-radius:50%;display:grid;place-items:center;color:#fff;font-style:normal;font-size:10px;font-weight:700;transition:transform .2s}
.soc a:hover{color:#fff;transform:translateY(-2px)}
.verified{margin-top:20px;display:inline-flex;align-items:center;gap:8px;background:var(--primary);color:#fff;padding:8px 12px;border-radius:6px;font-size:11px;font-weight:700}

/* ===== Liên hệ cố định ===== */
.contact-widget{position:fixed;right:24px;bottom:24px;z-index:120}
.contact-widget summary{list-style:none;display:flex;min-width:96px;min-height:50px;align-items:center;justify-content:center;padding:0 22px;border-radius:999px;background:#fffaf0;color:var(--secondary);box-shadow:0 4px 16px rgba(0,0,0,.2);font-size:14px;font-weight:700;cursor:pointer;transition:transform .2s,background .2s}
.contact-widget summary::-webkit-details-marker{display:none}
.contact-widget summary:hover{transform:translateY(-2px);background:#fff}
.contact-options{position:absolute;right:0;bottom:calc(100% + 10px);display:grid;min-width:160px;gap:6px;padding:8px;background:#fff;border:1px solid #eee5d9;border-radius:12px;box-shadow:0 6px 22px rgba(0,0,0,.16)}
.contact-options a{padding:10px 12px;border-radius:7px;font-size:14px;font-weight:600;transition:background .2s}
.contact-options a:hover{background:var(--bg);color:var(--primary)}

/* ===== Responsive ===== */
@media(max-width:992px){
  .grid{grid-template-columns:repeat(2,1fr)}
  .about{grid-template-columns:1fr}.lead{margin:0;grid-template-columns:1fr}
  .coupons{grid-template-columns:1fr}
  .footer{grid-template-columns:1fr 1fr}
  .lemon{width:120px;height:120px}.lemon::after{width:60px;height:60px}
}
@media(max-width:600px){
  .brand-philosophy-section{min-height:400px;padding:48px 18px}
  .philosophy-watermark{white-space:normal;width:100%;text-align:center}
  .philosophy-card .card-desc{font-size:14px}
  .actions .fav:not(.product-nav-toggle){display:none}
  .features{gap:20px}.feat{width:140px}
  .footer-layout{grid-template-columns:1fr}
  .footer-art{min-height:clamp(220px,55vw,340px)}
  .footer{grid-template-columns:1fr}
  .lead li{grid-template-columns:1fr}
  .btns{display:flex}
  .tag{padding:50px 24px}
}
@media(prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
</head>
<body>

<?php require __DIR__ . '/includes/header.php'; ?>

<!-- ===== Product banner slider ===== -->
<section class="banner-slider-container" id="bannerSlider" aria-label="Sản phẩm nổi bật">
  <div class="banner-track">
    <article class="banner-slide active" aria-hidden="false">
      <div class="banner-image">
        <img src="assets/images/Gel_bi_dao_rua_mat.jpg" alt="Gel bí đao rửa mặt">
        <span class="visual-note">BÍ ĐAO · RAU MÁ · TRÀM TRÀ</span>
      </div>
      <div class="banner-content">
        <div class="product-category">BÍ ĐAO</div>
        <h2 class="product-title">Gel bí đao rửa mặt</h2>
        <p class="product-desc">Làm sạch bụi bẩn và bã nhờn, cho cảm giác thông thoáng mà vẫn dịu nhẹ trên da.</p>
        <div class="banner-action-row">
          <a href="products.php?search=<?= rawurlencode('Gel bí đao rửa mặt') ?>" class="btn-shop-now"><span>XEM NGAY</span></a>
          <div class="price-box"><span class="new-price">145K</span></div>
        </div>
      </div>
    </article>

    <article class="banner-slide" aria-hidden="true">
      <div class="banner-image">
        <img src="assets/images/Nuoc_bi_dao_can_bang_da.jpg" alt="Nước bí đao cân bằng da">
        <span class="visual-note">BÍ ĐAO · RAU MÁ · TRÀM TRÀ</span>
      </div>
      <div class="banner-content">
        <div class="product-category">BÍ ĐAO</div>
        <h2 class="product-title">Nước bí đao cân bằng da</h2>
        <p class="product-desc">Nước bí đao giúp cân bằng da, mang lại cảm giác tươi mát và thông thoáng.</p>
        <div class="banner-action-row">
          <a href="products.php?search=<?= rawurlencode('Nước bí đao cân bằng da') ?>" class="btn-shop-now"><span>XEM NGAY</span></a>
          <div class="price-box"><span class="new-price">255K</span></div>
        </div>
      </div>
    </article>
  </div>

  <button class="slider-btn prev-btn" type="button" aria-label="Slide trước">&#10094;</button>
  <button class="slider-btn next-btn" type="button" aria-label="Slide tiếp theo">&#10095;</button>

  <div class="slider-dots" aria-label="Chọn slide">
    <button class="dot active" type="button" data-slide="0" aria-label="Slide 1" aria-current="true"></button>
    <button class="dot" type="button" data-slide="1" aria-label="Slide 2" aria-current="false"></button>
  </div>
</section>

<!-- ===== 3 chính sách ===== -->
<section class="features">
  <div class="feat"><div class="ic" style="background:#f6ece2">🚚</div><h3>Giao hàng toàn quốc</h3><p>Miễn phí giao hàng với hoá đơn từ 99.000 đ</p></div>
  <div class="feat"><div class="ic" style="background:#f3ede0;box-shadow:0 0 0 3px #e8dcc6">♻️</div><h3 style="color:var(--primary)">Điểm xanh</h3><p>Đổi vỏ chai cũ, nhận túi xanh</p></div>
  <div class="feat"><div class="ic" style="background:#f8f1de">🌿</div><h3>100% thuần chay</h3><p>Không thử nghiệm trên động vật</p></div>
</section>

<!-- ===== Triết lý thương hiệu ===== -->
<section class="brand-philosophy-section" id="about" aria-labelledby="philosophyTitle">
  <div class="philosophy-bg">
    <img src="assets/images/my-pham-thuan-chay.jpg" alt="Triết lý thương hiệu Cocoon">
  </div>
  <div class="philosophy-watermark" aria-hidden="true">
    <span>MỸ PHẨM THUẦN CHAY</span>
  </div>
  <div class="philosophy-card">
    <h2 class="card-title" id="philosophyTitle">Triết lý <span>THƯƠNG HIỆU</span></h2>
    <p class="card-desc">
      Là những người yêu thiên nhiên và đam mê khám phá các nguồn nguyên liệu đặc hữu của Việt Nam, chúng tôi luôn kiên định với những triết lý trên hành trình tìm vẻ đẹp thật sự của làn da.
    </p>
    <a href="brand-story.php" class="btn-explore">
      <span>TÌM HIỂU THÊM</span>
      <span aria-hidden="true">→</span>
    </a>
  </div>
</section>

<!-- ===== Coupon ===== -->
<section class="wrap coupons" id="coupons"></section>

<!-- ===== Bộ sưu tập sản phẩm ===== -->
<section class="collection">
  <div class="wrap">
    <div class="head">
      <div><small>THUẦN CHAY TỪ THIÊN NHIÊN</small><h2>SẢN PHẨM NỔI BẬT</h2></div>
      <a href="products.php" class="more">XEM THÊM →</a>
    </div>
    <div class="grid" id="products">
      <?php foreach ($featuredProducts as $product): ?>
        <?php $productUrl = 'products.php?search=' . rawurlencode($product['name']); ?>
        <article class="product">
          <a href="<?= htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Xem <?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>">
            <div class="pic">
              <img src="<?= htmlspecialchars($product['image'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
            </div>
          </a>
          <div class="brand">the cocoon</div>
          <h3><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h3>
          <?php if (!empty($product['volume'])): ?>
            <span class="volume-label"><?= htmlspecialchars($product['volume'], ENT_QUOTES, 'UTF-8') ?></span>
          <?php endif; ?>
          <div class="cur"><?= htmlspecialchars($product['price_text'], ENT_QUOTES, 'UTF-8') ?></div>
          <div class="btns">
            <a class="q" href="<?= htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8') ?>">Xem sản phẩm</a>
            <a class="b" href="<?= htmlspecialchars($productUrl, ENT_QUOTES, 'UTF-8') ?>">Mua ngay</a>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ===== Newsletter ===== -->
<section class="news">
  <div class="wrap">
    <h2>NHẬN THÔNG TIN KHUYẾN MÃI</h2>
    <p>Đừng bỏ lỡ các ưu đãi hấp dẫn của Cocoon bạn nhé</p>
    <form onsubmit="event.preventDefault();alert('Cảm ơn bạn đã đăng ký!');this.reset()">
      <input type="email" placeholder="Nhập địa chỉ email của bạn..." required>
      <button>ĐĂNG KÝ</button>
    </form>
    <small>*Đăng ký để nhận ưu đãi sớm nhất*</small>
  </div>
  <div class="lemon"></div>
</section>

<!-- ===== Footer ===== -->
<footer class="footer-layout">
  <div class="footer-art">
    <img src="assets/images/cocoon.jpg" alt="Hình ảnh thương hiệu Cocoon Vietnam" loading="lazy">
  </div>
  <div class="wrap footer">
    <div>
      <h5>ABOUT US</h5>
      <p class="blue">COCOON VIETNAM - CÔNG TY CỔ PHẦN Y&amp;B. Địa chỉ: 14D1, KP 1A, Quốc lộ 1A, P. Tân Thới Hiệp, TP. Hồ Chí Minh. Hotline: 1900 9300.</p>
      <p style="margin-top:14px"><a href="brand-story.php">Câu chuyện thương hiệu</a></p>
      <span class="verified">✔ ĐÃ THÔNG BÁO · BỘ CÔNG THƯƠNG</span>
    </div>
    <div>
      <h5>CHÍNH SÁCH</h5>
      <ul>
        <li><a href="#">Hỏi đáp</a></li><li><a href="#">Chính sách bán hàng</a></li>
        <li><a href="#">Điều khoản bảo mật</a></li><li><a href="#">Điều kiện chung</a></li>
        <li><a href="#">Chính sách giải quyết khiếu nại</a></li>
      </ul>
    </div>
    <div>
      <h5>FANPAGE</h5>
      <p>Cocoon Vietnam</p>
      <div class="soc">
        <a href="https://www.facebook.com/CocoonVietnamOfficial" target="_blank" rel="noopener noreferrer" aria-label="Facebook Cocoon Vietnam" style="background:#1877f2">f</a>
        <a href="https://www.instagram.com/cocoon.vietnam/" target="_blank" rel="noopener noreferrer" aria-label="Instagram Cocoon Vietnam" style="background:#e1306c">IG</a>
        <a href="https://www.youtube.com/channel/UChaXgZ8kSpQDFgfm2X-kwTg" target="_blank" rel="noopener noreferrer" aria-label="Youtube Cocoon Vietnam" style="background:#c4302b">▶</a>
      </div>
    </div>
  </div>
</footer>

<details class="contact-widget">
  <summary>Liên hệ</summary>
  <nav class="contact-options" aria-label="Phương thức liên hệ">
    <a href="https://zalo.me/1351328921239799141" target="_blank" rel="noopener noreferrer">Zalo</a>
    <a href="https://m.me/CocoonVietnamOfficial" target="_blank" rel="noopener noreferrer">Messenger</a>
  </nav>
</details>

<script src="assets/js/cart-api.js"></script>
<script src="assets/js/product-search.js"></script>
<script src="assets/js/banner.js"></script>
<script src="assets/js/mega-menu.js"></script>
<script>
const couponRules=<?= json_encode(cocoon_coupon_catalog(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
const formatCouponCurrency=amount=>new Intl.NumberFormat('vi-VN').format(amount)+' ₫';
const coupons=Object.entries(couponRules).map(([code,coupon])=>({
  code,
  big:(coupon.discount/1000)+'k',
  min:(coupon.minimum/1000)+'k',
  txt:'Giảm '+formatCouponCurrency(coupon.discount)+' cho đơn hàng từ '+formatCouponCurrency(coupon.minimum)
}));
/* ===== Render coupon ===== */
document.getElementById('coupons').innerHTML=coupons.map(c=>`
  <div class="coupon"><div class="l">
    <div class="code">Mã: ${c.code}</div>
    <div class="big"><span>Giảm<br><small style="font-size:10px">Đơn từ ${c.min}</small></span><strong>${c.big}</strong></div>
    <p>${c.txt}</p>
    <div class="foot"><a href="#">Điều kiện áp dụng</a><button class="copy" data-code="${c.code}">Sao chép mã</button></div>
  </div><div class="r">COUPON</div></div>`).join('');
const savedCouponsKey='cocoon_saved_coupons';
function saveCoupon(coupon){
  let saved=[];
  const stored=localStorage.getItem(savedCouponsKey);
  if(stored!==null){
    saved=JSON.parse(stored);
    if(!Array.isArray(saved)) throw new Error('Danh sách mã giảm giá đã lưu không hợp lệ.');
  }
  saved=saved.filter(item=>item&&item.code!==coupon.code);
  saved.push(coupon);
  localStorage.setItem(savedCouponsKey,JSON.stringify(saved));
}
document.querySelectorAll('.copy[data-code]').forEach(button=>button.addEventListener('click',async()=>{
  const coupon=coupons.find(item=>item.code===button.dataset.code);
  if(!coupon) return;
  let saved=false;
  try{
    saveCoupon(coupon);
    saved=true;
  }catch(error){
    console.error('Không thể lưu mã giảm giá.',error);
    alert('Không thể lưu mã giảm giá trên thiết bị này.');
  }
  try{
    if(!navigator.clipboard) throw new Error('Clipboard API không khả dụng.');
    await navigator.clipboard.writeText(coupon.code);
    button.textContent=saved?'Đã chép & lưu!':'Đã chép!';
  }catch(error){
    console.error('Không thể sao chép mã giảm giá.',error);
    button.textContent=saved?'Đã lưu mã':'Không thể sao chép';
  }
  setTimeout(()=>button.textContent='Sao chép mã',1500);
}));

const cartCount=document.getElementById('cartCount');
const cartCsrfToken=document.querySelector('meta[name="csrf-token"]').content;
window.CocoonCart.updateCount(cartCount,cartCsrfToken).catch(error=>{console.error('Không thể tải số lượng giỏ hàng:',error);cartCount.textContent='0';});
const menuToggle=document.getElementById('menuToggle');
const menuPanel=document.getElementById('menuPanel');
function closeMenu(){menuPanel.hidden=true;menuToggle.setAttribute('aria-expanded','false')}
menuToggle.addEventListener('click',()=>{
  const isOpen=menuToggle.getAttribute('aria-expanded')==='true';
  menuPanel.hidden=isOpen;
  menuToggle.setAttribute('aria-expanded',String(!isOpen));
});
document.addEventListener('click',event=>{
  if(!event.target.closest('.menu-wrap')) closeMenu();
});
document.addEventListener('keydown',event=>{
  if(event.key==='Escape') closeMenu();
});

</script>
</body>
</html>
