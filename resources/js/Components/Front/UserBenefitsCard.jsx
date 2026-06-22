import CouponCard from './CouponCard';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';

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
                <div className={`row row-cols-1 row-cols-md-3 g-4 justify-content-center ${isDetailMode ? 'is-detail' : ''}`.trim()}>
                    {activeCoupons.map((coupon) => (
                        <div key={coupon.id} className="col">
                            <CouponCard
                                key={coupon.id}
                                coupon={coupon}
                                onClick={onCouponSelect}
                            />
                        </div>
                    ))}
                </div>
            ) : (
                <div className="active-coupons-empty">
                    <div className="alert alert-info m-0" role="alert">
                        <p className="mb-0">No tienes cupones activos por ahora.</p>
                    </div>
                </div>
            )}

            {actionLabel && onAction ? (
                <button type="button" className="active-coupons-action btn btn-primary w-100" onClick={onAction}>
                    <FontAwesomeIcon icon={faTicket} /> {actionLabel}
                </button>
            ) : null}
        </section>
    );
}
