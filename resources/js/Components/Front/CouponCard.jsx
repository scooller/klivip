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
                    <p className="card-title">{coupon.site_name ?? 'Sala'}</p>
                    <p className="card-subtitle">{coupon.type_label ?? 'TOMBOLA'}</p>
                </div>
            </div>

            <div className="card-body">
                {
                    //draw_label='percentage,fixed,mensaje'
                }
                <p className="code">{normalizeCouponCode(coupon.code)}</p>
                {coupon.draw_label === 'percentage' && (
                    <p className="message">Dcto de {Number(coupon.value).toFixed(1)}%</p>
                )}

                {coupon.draw_label === 'fixed' && (
                    <p className="message">{Math.round(coupon.value)} Uso{coupon.value > 1 ? 's' : ''}</p>
                )}

                {coupon.draw_label === 'mensaje' && (
                    <p className="message">{coupon.message}</p>
                )}
                <div className="d-flex justify-content-evenly align-items-center">
                    <span className="badge bg-secondary">{coupon.draw_label ?? 'Tipo de sorteo'}</span>
                    <span className="badge bg-success">{coupon.valid_to ?? 'Vigente'}</span>
                </div>
            </div>
            <div className="card-footer">
                <p className="mb-0">{coupon.valid_from ?? 'Fecha de inicio no disponible'}</p>
                <p className="mb-3">{coupon.valid_to ?? 'Fecha de fin no disponible'}</p>
            </div>
        </div>
    );
}
