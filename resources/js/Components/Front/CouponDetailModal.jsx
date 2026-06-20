import CouponCard from './CouponCard';

export default function CouponDetailModal({
    coupon,
    open,
    onClose,
}) {
    if (!open || !coupon) {
        return null;
    }

    const handleBackdropClick = (event) => {
        if (event.target === event.currentTarget) {
            onClose();
        }
    };

    return (
        <>
            <div className="coupon-modal-backdrop" onClick={handleBackdropClick}></div>
            <div className="coupon-modal d-flex flex-column align-items-center justify-content-center" onClick={handleBackdropClick}>
                <div className="coupon-modal-content d-flex flex-column align-items-center gap-3">
                    <CouponCard coupon={coupon} />

                    <button className="btn btn-primary btn-sm" type="button" onClick={onClose}>
                        Cerrar
                    </button>
                </div>
            </div>
        </>
    );
}
