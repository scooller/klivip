function normalizeCouponCode(code) {
    const digitsOnly = String(code ?? '').replace(/\D/g, '');

    if (digitsOnly.length > 0 && digitsOnly.length <= 6) {
        return digitsOnly.padStart(6, '0');
    }

    return String(code ?? '-');
}

export default function UserBenefitsCard({
    activeCoupons = [],
    mode = 'list',
    onCouponSelect = null,
    actionLabel = null,
    onAction = null,
}) {
    const isDetailMode = mode === 'detail';

    return (
        <section
            className={`active-coupons-panel d-flex flex-column gap-3 ${isDetailMode ? 'active-coupons-panel--detail' : ''}`.trim()}
            aria-label="Cupones activos"
        >
            <h3>CUPONES ACTIVOS</h3>

            {activeCoupons.length > 0 ? (
                <div className={`active-coupons-list d-flex flex-column gap-3 ${isDetailMode ? 'is-detail' : ''}`.trim()}>
                    {activeCoupons.map((coupon) => (
                        <div
                            key={coupon.id}
                            className={`active-coupon-card card ${onCouponSelect ? 'is-clickable' : ''}`.trim()}
                            onClick={() => onCouponSelect?.(coupon)}
                            onKeyDown={(event) => {
                                if (!onCouponSelect) {
                                    return;
                                }

                                if (event.key === 'Enter' || event.key === ' ') {
                                    event.preventDefault();
                                    onCouponSelect(coupon);
                                }
                            }}
                            role={onCouponSelect ? 'button' : undefined}
                            tabIndex={onCouponSelect ? 0 : undefined}
                        >
                            <div className="active-coupon-card__header d-flex flex-wrap gap-2 align-items-center card-body">
                                <div>
                                    <p className="active-coupon-card__brand">{coupon.site_name ?? 'Sala'}</p>
                                    <p className="active-coupon-card__meta">Cupon activo</p>
                                </div>
                                <span className="badge bg-primary">{coupon.draw_label ?? coupon.type_label ?? 'TOMBOLA'}</span>
                            </div>

                            <div className="active-coupon-card__body d-flex flex-column gap-3 card-body">
                                <p className="active-coupon-card__code">{normalizeCouponCode(coupon.code)}</p>
                                <div className="active-coupon-card__tags d-flex flex-wrap gap-2 align-items-center">
                                    <span className="badge bg-secondary">{coupon.type_label ?? 'Tipo de sorteo'}</span>
                                    <span className="badge bg-success">{coupon.valid_to ?? 'Vigente'}</span>
                                </div>
                            </div>

                            {onCouponSelect ? (
                                <div className="active-coupon-card__footer d-flex flex-column gap-3 card-body">
                                    <button className="btn btn-outline-secondary btn-sm w-100" type="button">
                                        Ver detalle
                                    </button>
                                </div>
                            ) : null}
                        </div>
                    ))}
                </div>
            ) : (
                <div className="active-coupons-empty card">
                    <div className="card-body">
                        <p className="mb-0">No tienes cupones activos por ahora.</p>
                    </div>
                </div>
            )}

            {actionLabel && onAction ? (
                <button type="button" className="active-coupons-action btn btn-primary w-100" onClick={onAction}>
                    {actionLabel}
                </button>
            ) : null}
        </section>
    );
}
