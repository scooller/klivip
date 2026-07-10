export default function CouponMini({
    number,
    isUsed = false,
    onClick = null,
    showLabel = true,
    as: Tag = 'div',
    sweepstakeName = null,
    date = null,
}) {
    const clickable = onClick != null;

    const paddedNumber = String(number).padStart(5, '0');

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
            {sweepstakeName && <div className="coupon-mini__header fw-bold mb-1">{sweepstakeName}</div>}
            <span className="coupon-mini__number text-center">Cupon<h2>{paddedNumber}</h2></span>
            {isUsed && showLabel && (
                <small className="coupon-mini__label">Usado</small>
            )}
            {date && <small className="coupon-mini__footer mt-1 text-muted" style={{ fontSize: '0.75rem' }}>{date}</small>}
        </Tag>
    );
}
