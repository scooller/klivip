import { useEffect, useRef } from 'react';
import CouponCard from './CouponCard';

export default function CouponDetailModal({
    coupon,
    open,
    onClose,
}) {
    const modalRef = useRef(null);
    const bsModalRef = useRef(null);

    useEffect(() => {
        if (!modalRef.current) {
            return;
        }

        const Modal = window.bootstrap.Modal;
        bsModalRef.current = Modal.getOrCreateInstance(modalRef.current);

        if (open) {
            bsModalRef.current.show();
        } else {
            bsModalRef.current.hide();
        }
    }, [open]);

    useEffect(() => {
        if (!modalRef.current) {
            return;
        }

        const handleHidden = () => onClose();
        modalRef.current.addEventListener('hidden.bs.modal', handleHidden);

        return () => {
            modalRef.current?.removeEventListener('hidden.bs.modal', handleHidden);
            bsModalRef.current?.dispose();
        };
    }, [onClose]);

    return (
        <div className="modal fade" ref={modalRef} tabIndex="-1" aria-hidden="true">
            <div className="modal-dialog modal-dialog-centered">
                <div className="modal-content d-flex flex-column align-items-center gap-3 p-3">
                    <CouponCard coupon={coupon} />

                    <button className="btn btn-primary btn-sm w-100" type="button" data-bs-dismiss="modal">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    );
}
