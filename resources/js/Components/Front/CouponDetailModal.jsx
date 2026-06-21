import { Modal } from 'bootstrap';
import { useCallback, useEffect, useRef } from 'react';
import CouponCard from './CouponCard';

export default function CouponDetailModal({
    coupon,
    open,
    onClose,
}) {
    const modalRef = useRef(null);
    const instanceRef = useRef(null);
    const stableOnClose = useRef(onClose);
    stableOnClose.current = onClose;

    const handleHidden = useCallback(() => {
        stableOnClose.current();
    }, []);

    useEffect(() => {
        if (!modalRef.current) {
            return;
        }

        instanceRef.current = Modal.getOrCreateInstance(modalRef.current, { backdrop: true });
        modalRef.current.addEventListener('hidden.bs.modal', handleHidden);

        return () => {
            if (!modalRef.current) {
                return;
            }

            modalRef.current.removeEventListener('hidden.bs.modal', handleHidden);
            const instance = instanceRef.current;

            if (instance) {
                instance.hide();
                Modal.getInstance(modalRef.current)?.dispose();
            }

            instanceRef.current = null;
        };
    }, [handleHidden]);

    useEffect(() => {
        const instance = instanceRef.current;

        if (!instance) {
            return;
        }

        if (open) {
            instance.show();
        } else {
            instance.hide();
        }
    }, [open]);

    return (
        <div className="modal fade" ref={modalRef} tabIndex="-1" aria-hidden="true">
            <div className="modal-dialog modal-dialog-centered">
                <div className="modal-content d-flex flex-column align-items-center gap-3 p-3">
                    {coupon ? (
                        <>
                            <CouponCard coupon={coupon} />

                            <button className="btn btn-primary btn-sm w-100" type="button" data-bs-dismiss="modal">
                                Cerrar
                            </button>
                        </>
                    ) : null}
                </div>
            </div>
        </div>
    );
}
