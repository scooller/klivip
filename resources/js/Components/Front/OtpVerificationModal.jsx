import { Modal } from 'bootstrap';
import { useCallback, useEffect, useRef } from 'react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faPersonCircleCheck, faBarcode } from '@fortawesome/free-solid-svg-icons';

export default function OtpVerificationModal({
    open,
    mode = 'login',
    form,
    feedback,
    onClose,
    onSubmit,
    onResend,
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

        instanceRef.current = Modal.getOrCreateInstance(modalRef.current, { backdrop: 'static', keyboard: false });
        modalRef.current.addEventListener('hidden.bs.modal', handleHidden);

        return () => {
            if (!modalRef.current) {
                return;
            }

            modalRef.current.removeEventListener('hidden.bs.modal', handleHidden);
            const instance = instanceRef.current;

            if (instance) {
                instance.hide();
                instance.dispose();
            }

            // Forced cleanup to avoid stale backdrop when unmounting (e.g., Inertia navigation)
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';

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

    const title = mode === 'login' ? 'Verificación de Acceso' : 'Verificación de Registro';
    const buttonText = mode === 'login' ? 'Iniciar Sesión' : 'Verificar código';

    return (
        <div className="modal fade" ref={modalRef} tabIndex="-1" aria-hidden="true" data-bs-backdrop="static">
            <div className="modal-dialog modal-dialog-centered">
                <div className="modal-content user-login-shell p-2 border-0 shadow">
                    <div className="modal-header border-0 pb-0">
                        <h5 className="modal-title fw-bold text-white">{title}</h5>
                        <button type="button" className="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div className="modal-body text-white">
                        <form className="d-flex flex-column gap-3" onSubmit={onSubmit}>
                            <div>
                                <label htmlFor="otp-entry" className="form-label">Código de verificación:</label>
                                <input
                                    id="otp-entry"
                                    className="form-control user-phone-input"
                                    type="text"
                                    inputMode="text"
                                    autoComplete="one-time-code"
                                    placeholder="ABC123"
                                    value={form.data.otp_code}
                                    onInput={(event) => form.setData('otp_code', event.target.value)}
                                />
                            </div>

                            {feedback ? (
                                <div className={`alert alert-${feedback.variant || 'info'} feedback-callout mb-0`} role="alert">
                                    <strong>{feedback.title}</strong>
                                    <p className="mb-0">{feedback.description}</p>
                                </div>
                            ) : null}

                            {form.errors.otp_code ? (
                                <div className="alert alert-danger feedback-callout mb-0" role="alert">
                                    <strong>Error</strong>
                                    <p className="mb-0">{form.errors.otp_code}</p>
                                </div>
                            ) : null}

                            <div className="d-flex flex-column gap-2 mt-2">
                                <button
                                    className="btn btn-primary w-100"
                                    type="submit"
                                    disabled={form.processing}
                                >
                                    <FontAwesomeIcon icon={faPersonCircleCheck} className="me-2" />
                                    {buttonText}
                                </button>
                                {mode === 'login' && onResend && (
                                    <button
                                        className="btn btn-outline-light w-100"
                                        type="button"
                                        disabled={form.processing}
                                        onClick={onResend}
                                    >
                                        <FontAwesomeIcon icon={faBarcode} className="me-2" />
                                        Reenviar código
                                    </button>
                                )}
                                <button
                                    className="btn btn-outline-light w-100"
                                    type="button"
                                    data-bs-dismiss="modal"
                                >
                                    Volver
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    );
}
