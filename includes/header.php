<?php
require_once __DIR__ . '/mega_menu_data.php';
$megaMenuCategories = get_mega_menu_categories($conn);
$megaMenuIngredients = get_mega_menu_ingredients();
?>
<header class="header">
  <div class="wrap">
    <div class="row">
      <a href="index.php" class="logo" aria-label="the cocoon Vietnam - Trang chủ">
        <span class="logo-the">the</span>
        <span class="logo-name">cocoon</span>
        <span class="logo-country">VIETNAM</span>
      </a>
      <nav class="actions" aria-label="Điều hướng chính">
        <button class="product-nav-toggle fav" id="megaMenuToggle" type="button"
                aria-controls="productMegaMenu" aria-expanded="false">Sản phẩm</button>
        <a href="cart.php" class="cart">Giỏ hàng <span class="badge" id="cartCount">0</span></a>
        <div class="menu-wrap">
          <button class="burger" id="menuToggle" type="button" aria-label="Mở menu" aria-controls="menuPanel" aria-expanded="false"><i></i><i></i><i></i></button>
          <nav class="menu-panel" id="menuPanel" aria-label="Menu chính" hidden>
            <a href="products.php">Tất cả sản phẩm</a>
            <a href="saved-coupons.php">Mã giảm giá đã lưu</a>
            <?php if (isset($_SESSION['user_id'])): ?>
              <a href="addresses.php">Địa chỉ giao hàng</a>
              <form action="logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit">Đăng xuất</button>
              </form>
            <?php else: ?>
              <a href="login.php">Đăng nhập</a>
              <a href="register.php">Đăng ký</a>
            <?php endif; ?>
          </nav>
        </div>
      </nav>
    </div>
    <form class="search" role="search" method="get" action="products.php" data-product-search>
      <input type="search" name="search" placeholder="Tìm kiếm sản phẩm..." aria-label="Tìm kiếm sản phẩm" autocomplete="off" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="homeSearchSuggestions">
      <button type="submit" aria-label="Tìm kiếm">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#333" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4-4"/></svg>
      </button>
      <div class="search-suggestions" id="homeSearchSuggestions" role="listbox" aria-label="Gợi ý sản phẩm" hidden></div>
    </form>
  </div>
</header>

<div class="mega-menu-backdrop" data-mega-menu-close aria-hidden="true"></div>
<aside class="product-mega-menu" id="productMegaMenu" aria-label="Danh mục sản phẩm"
       aria-hidden="true" data-products-url="api/mega_menu_products.php">
  <div class="mega-menu-heading">
    <span class="mega-menu-kicker">THE COCOON VIETNAM</span>
    <button class="mega-menu-close" type="button" aria-label="Đóng menu sản phẩm">&times;</button>
  </div>
  <div class="mega-menu-columns">
    <section class="mega-menu-column mega-menu-categories" aria-labelledby="megaCategoriesTitle">
      <h2 id="megaCategoriesTitle">Danh mục</h2>
      <button class="mega-menu-category is-active" type="button" data-category-id="0" aria-pressed="true">
        Tất cả sản phẩm <span aria-hidden="true">→</span>
      </button>
      <?php foreach ($megaMenuCategories as $category): ?>
        <button class="mega-menu-category" type="button"
                data-category-id="<?= (int) $category['id'] ?>" aria-pressed="false">
          <?= htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') ?>
          <span aria-hidden="true">→</span>
        </button>
      <?php endforeach; ?>
    </section>

    <section class="mega-menu-column mega-menu-ingredients" aria-labelledby="megaIngredientsTitle">
      <h2 id="megaIngredientsTitle">Tìm hiểu nguyên liệu</h2>
      <button class="mega-menu-ingredient mega-menu-ingredient-all is-active"
              type="button" aria-pressed="true">Tất cả</button>
      <div class="mega-menu-ingredient-list" role="list">
        <?php foreach ($megaMenuIngredients as $index => $ingredient): ?>
          <button class="mega-menu-ingredient<?= $index === 0 ? ' is-active' : '' ?>"
                  type="button" role="listitem"
                  data-ingredient="<?= htmlspecialchars($ingredient, ENT_QUOTES, 'UTF-8') ?>"
                  aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>">
            <?= htmlspecialchars($ingredient, ENT_QUOTES, 'UTF-8') ?>
          </button>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="mega-menu-column mega-menu-products" aria-labelledby="megaProductsTitle">
      <h2 id="megaProductsTitle">Sản phẩm nổi bật</h2>
      <p class="mega-menu-products-status" aria-live="polite">Chọn nguyên liệu để khám phá sản phẩm.</p>
      <div class="mega-menu-product-list"></div>
      <a class="mega-menu-view-all" href="products.php">
        <span class="mega-menu-view-all-label">Tìm hiểu thêm</span>
        <span aria-hidden="true">→</span>
      </a>
    </section>
  </div>
</aside>
