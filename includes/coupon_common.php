<?php
/** Available fixed-value checkout coupons keyed by their public code. */
function cocoon_coupon_catalog(): array
{
    return [
        'CC_15KDON149K' => ['discount' => 15000, 'minimum' => 149000],
        'CC_30KDON259K' => ['discount' => 30000, 'minimum' => 259000],
        'CC_50KDON359K' => ['discount' => 50000, 'minimum' => 359000],
    ];
}

function cocoon_coupon_discount(string $code, int $subtotal): int
{
    $coupons = cocoon_coupon_catalog();
    if (!isset($coupons[$code])) {
        throw new DomainException('Mã giảm giá không hợp lệ. Vui lòng kiểm tra lại.');
    }
    if ($subtotal < $coupons[$code]['minimum']) {
        throw new DomainException('Đơn hàng chưa đạt giá trị tối thiểu để sử dụng mã giảm giá này.');
    }
    return min($coupons[$code]['discount'], $subtotal);
}
