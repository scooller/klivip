import { WaButton, WaCard, WaTag } from './primitives/wa';

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
            className={`active-coupons-panel wa-stack ${isDetailMode ? 'active-coupons-panel--detail' : ''}`.trim()}
            aria-label="Cupones activos"
        >
            <h3>CUPONES ACTIVOS</h3>

            {activeCoupons.length > 0 ? (
                <div className={`active-coupons-list wa-stack ${isDetailMode ? 'is-detail' : ''}`.trim()}>
                    {activeCoupons.map((coupon) => (
                        <WaCard
                            key={coupon.id}
                            className={`active-coupon-card ${onCouponSelect ? 'is-clickable' : ''}`.trim()}
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
                            <div className="active-coupon-card__header wa-cluster">
                                <div>
                                    <p className="active-coupon-card__brand">{coupon.site_name ?? 'Sala'}</p>
                                    <p className="active-coupon-card__meta">Cupon activo</p>
                                </div>
                                <WaTag variant="brand">{coupon.draw_label ?? coupon.type_label ?? 'TOMBOLA'}</WaTag>
                            </div>

                            <div className="active-coupon-card__body wa-stack">
                                <p className="active-coupon-card__code">{normalizeCouponCode(coupon.code)}</p>
                                <div className="active-coupon-card__tags wa-cluster">
                                    <WaTag>{coupon.type_label ?? 'Tipo de sorteo'}</WaTag>
                                    <WaTag variant="success">{coupon.valid_to ?? 'Vigente'}</WaTag>
                                </div>
                            </div>

                            {onCouponSelect ? (
                                <div className="active-coupon-card__footer wa-stack">
                                    <WaButton variant="neutral" size="small">
                                        Ver detalle
                                    </WaButton>
                                </div>
                            ) : null}
                        </WaCard>
                    ))}
                </div>
            ) : (
                <WaCard className="active-coupons-empty">
                    <p>No tienes cupones activos por ahora.</p>
                </WaCard>
            )}

            {actionLabel && onAction ? (
                <WaButton type="button" className="active-coupons-action" variant="brand" onClick={onAction}>
                    {actionLabel}
                </WaButton>
            ) : null}
        </section>
    );
}
