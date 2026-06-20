function normalizeCouponCode(code) {
    const digitsOnly = String(code ?? '').replace(/\D/g, '');

    if (digitsOnly.length > 0 && digitsOnly.length <= 6) {
        return digitsOnly.padStart(6, '0');
    }

    return String(code ?? '-');
}

export default function CouponCard({
    coupon,
    onClick = null,
}) {
    const isClickable = onClick != null;

    return (
        <div
            className={`active-coupon-card mx-auto card ${isClickable ? 'is-clickable' : ''}`.trim()}
            onClick={() => onClick?.(coupon)}
            onKeyDown={(event) => {
                if (!onClick) {
                    return;
                }

                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    onClick(coupon);
                }
            }}
            role={isClickable ? 'button' : undefined}
            tabIndex={isClickable ? 0 : undefined}
        >
            <div className="active-coupon-card__header card-header d-flex flex-wrap gap-2 align-items-center">
                <div>
                    <p className="active-coupon-card__brand card-title">{coupon.site_name ?? 'Sala'}</p>
                    <p className="active-coupon-card__meta card-text">Cupon activo</p>
                </div>
                <span className="badge bg-primary">{coupon.draw_label ?? coupon.type_label ?? 'TOMBOLA'}</span>
            </div>

            <div className="active-coupon-card__body card-body d-flex flex-column gap-3">
                {coupon.message ? (
                    <p className="active-coupon-card__message">{coupon.message}</p>
                ) : (
                    <p className="active-coupon-card__code">{normalizeCouponCode(coupon.code)}</p>
                )}
                <div className="active-coupon-card__tags d-flex flex-wrap gap-2 align-items-center">
                    <span className="badge bg-secondary">{coupon.type_label ?? 'Tipo de sorteo'}</span>
                    <span className="badge bg-success">{coupon.valid_to ?? 'Vigente'}</span>
                </div>
            </div>

            {isClickable ? (
                <div className="active-coupon-card__footer card-footer d-flex flex-column gap-3">
                    <button className="btn btn-outline-secondary btn-sm w-100" type="button">
                        Ver detalle
                    </button>
                </div>
            ) : null}
        </div>
    );
}
