export default function CouponCard({
    coupon,
    onClick = null,
}) {
    const isClickable = onClick != null;

    const paddedNumber = String(coupon.number).padStart(5, '0');

    return (
        <div
            className={`active-coupon-card mx-auto text-center card ${isClickable ? 'is-clickable' : ''}`.trim()}
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
            <div className="card-header">
                <div>
                    <p className="card-title">{coupon.sweepstake_name ?? 'Sorteo'}</p>
                    {/* <p className="card-subtitle">{coupon.is_used ? 'Canjeado' : 'Válido'}</p> */}
                </div>
            </div>

            <div className="card-body">
                <p className="code">{paddedNumber}</p>
                {coupon.prize && (
                    <p className="message mt-5">{coupon.prize}</p>
                )}
                {/* <div className="d-flex justify-content-evenly align-items-center">
                    <span className="badge bg-secondary">
                        {coupon.is_used ? 'Usado' : 'Activo'}
                    </span>
                    {coupon.draw_at && (
                        <span className="badge bg-warning text-dark">
                            Sorteo: {coupon.draw_at}
                        </span>
                    )}
                </div> */}
            </div>
            <div className="card-footer pb-5">
                <p className="mb-0">
                    {coupon.draw_at
                        ? `Sorteo: ${coupon.draw_at}`
                        : ''}
                </p>
            </div>
        </div>
    );
}
