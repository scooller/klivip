export default function CouponMini({
    number,
    isUsed = false,
    onClick = null,
    showLabel = true,
    as: Tag = 'div',
}) {
    const clickable = onClick != null;

    const paddedNumber = String(number).padStart(4, '0');

    return (
        <Tag
            className={`coupon-mini d-flex flex-column align-items-center justify-content-center ${isUsed ? 'is-used' : ''}`}
            role={clickable ? 'button' : undefined}
            tabIndex={clickable ? 0 : undefined}
            onClick={() => onClick?.()}
            onKeyDown={(e) => {
                if (!clickable) {
                    return;
                }

                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    onClick();
                }
            }}
        >
            <span className="coupon-mini__number text-center">Cupon<h2>{paddedNumber}</h2></span>
            {isUsed && showLabel && (
                <small className="coupon-mini__label">Usado</small>
            )}
        </Tag>
    );
}
