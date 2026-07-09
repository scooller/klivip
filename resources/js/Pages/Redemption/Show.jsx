import { Head, router, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCircleCheck, faClock, faGift, faSpinner, faTicket, faTrophy } from '@fortawesome/free-solid-svg-icons';
import { useState } from 'react';

export default function Show({ link, sweepstake, customer = null }) {
    const [feedback, setFeedback] = useState(null);
    const [processing, setProcessing] = useState(false);

    const handleRedeem = () => {
        setFeedback(null);
        setProcessing(true);

        router.post(`/redimir/${link.code}/redeem`, {}, {
            preserveScroll: true,
            onError: (errors) => {
                setFeedback({
                    variant: 'danger',
                    message: errors.email || 'Ocurrió un error. Intenta nuevamente.',
                });
                setProcessing(false);
            },
        });
    };

    return (
        <>
            <Head title={sweepstake.name} />

            <div className="min-vh-100 d-flex flex-column" style={{ background: 'linear-gradient(135deg, #04072a 0%, #090f3c 100%)', color: '#e8eaf6' }}>
                <div className="container py-4 flex-grow-1 d-flex align-items-center justify-content-center">
                    <div className="row w-100 justify-content-center">
                        <div className="col-12 col-lg-7 col-xl-6">
                            {/* Hero del sorteo */}
                            <div className="text-center mb-4">
                                <div className="d-inline-flex align-items-center gap-2 mb-3" style={{ background: 'rgba(255,193,7,0.15)', borderRadius: '999px', padding: '6px 18px' }}>
                                    <FontAwesomeIcon icon={faTrophy} style={{ color: '#ffc107' }} />
                                    <span className="small fw-semibold text-warning">Sorteo Activo</span>
                                </div>
                                <h1 className="fw-bold mb-2" style={{ fontSize: '1.8rem' }}>
                                    {sweepstake.name}
                                </h1>
                                {sweepstake.prize && (
                                    <div className="d-flex align-items-center justify-content-center gap-2 mb-2">
                                        <FontAwesomeIcon icon={faGift} style={{ color: '#ffc107' }} />
                                        <span className="fs-5 fw-semibold text-warning">
                                            {sweepstake.prize}
                                        </span>
                                    </div>
                                )}
                                <div className="d-flex align-items-center justify-content-center gap-2 text-secondary">
                                    <FontAwesomeIcon icon={faClock} />
                                    <small>Cierra: {sweepstake.expires_at}</small>
                                </div>
                            </div>

                            {/* Descripción */}
                            {sweepstake.description && (
                                <p className="text-center text-secondary mb-4" style={{ fontSize: '0.95rem' }}>
                                    {sweepstake.description}
                                </p>
                            )}

                            {/* Card de participación */}
                            <div className="card border-0 shadow-lg" style={{ background: 'rgba(255,255,255,0.05)', borderRadius: '20px', backdropFilter: 'blur(10px)' }}>
                                <div className="card-body p-4">
                                    <div className="text-center mb-4">
                                        <div className="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                             style={{ width: '64px', height: '64px', background: 'rgba(255,193,7,0.15)' }}>
                                            <FontAwesomeIcon icon={faTicket} style={{ fontSize: '1.8rem', color: '#ffc107' }} />
                                        </div>
                                        <h2 className="h5 fw-bold mb-1">Obtén {link.coupon_count} {link.coupon_count === 1 ? 'cupón' : 'cupones'}</h2>
                                        {link.title && (
                                            <p className="text-secondary small mb-0">{link.title}</p>
                                        )}
                                    </div>

                                    {customer && (
                                        <div className="text-center mb-3 px-3 py-2 rounded-3"
                                             style={{ background: 'rgba(255,255,255,0.05)' }}>
                                            <p className="small text-secondary mb-0">
                                                Canjeando como: <strong className="text-warning">{customer.name}</strong> ({customer.email})
                                            </p>
                                        </div>
                                    )}

                                    {feedback && (
                                        <div className={`alert alert-${feedback.variant} py-2 small`} role="alert">
                                            {feedback.message}
                                        </div>
                                    )}

                                    <button
                                        type="button"
                                        className="btn btn-warning btn-lg w-100 fw-bold"
                                        onClick={handleRedeem}
                                        disabled={processing}
                                    >
                                        {processing ? (
                                            <>
                                                <FontAwesomeIcon icon={faSpinner} spin className="me-2" />
                                                Procesando...
                                            </>
                                        ) : (
                                            <>
                                                <FontAwesomeIcon icon={faCircleCheck} className="me-2" />
                                                Canjear {link.coupon_count === 1 ? 'cupón' : 'cupones'}
                                            </>
                                        )}
                                    </button>

                                    {link.description && (
                                        <p className="text-center text-secondary small mt-3 mb-0">
                                            {link.description}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
