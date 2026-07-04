import { Head } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCircleCheck, faTicket, faTrophy } from '@fortawesome/free-solid-svg-icons';

export default function Success({ redemption }) {
    const { coupon_count, coupon_numbers, sweepstake_name, sweepstake_slug } = redemption;

    return (
        <>
            <Head title="¡Cupones obtenidos!" />

            <div className="min-vh-100 d-flex flex-column align-items-center justify-content-center"
                 style={{ background: 'linear-gradient(135deg, #04072a 0%, #090f3c 100%)', color: '#e8eaf6' }}>
                <div className="container py-4">
                    <div className="row justify-content-center">
                        <div className="col-12 col-lg-6 text-center">
                            {/* Check animado */}
                            <div className="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                                 style={{ width: '96px', height: '96px', background: 'rgba(25,135,84,0.2)', border: '3px solid rgba(25,135,84,0.5)' }}>
                                <FontAwesomeIcon icon={faCircleCheck} style={{ fontSize: '3rem', color: '#28a745' }} />
                            </div>

                            <h1 className="fw-bold mb-2" style={{ fontSize: '2rem' }}>
                                ¡Felicidades!
                            </h1>
                            <p className="text-secondary mb-4">
                                Has obtenido <strong className="text-warning">{coupon_count}</strong> {coupon_count === 1 ? 'cupón' : 'cupones'} para:
                            </p>

                            {/* Nombre del sorteo */}
                            <div className="d-inline-flex align-items-center gap-2 mb-4 px-4 py-2 rounded-pill"
                                 style={{ background: 'rgba(255,193,7,0.15)' }}>
                                <FontAwesomeIcon icon={faTrophy} style={{ color: '#ffc107' }} />
                                <span className="fw-semibold text-warning">{sweepstake_name}</span>
                            </div>

                            {/* Números de cupones */}
                            <div className="card border-0 shadow-lg mb-4"
                                 style={{ background: 'rgba(255,255,255,0.05)', borderRadius: '20px', backdropFilter: 'blur(10px)' }}>
                                <div className="card-body p-4">
                                    <div className="d-flex align-items-center justify-content-center gap-2 mb-3">
                                        <FontAwesomeIcon icon={faTicket} style={{ color: '#ffc107' }} />
                                        <span className="fw-semibold">Tus números</span>
                                    </div>
                                    <div className="d-flex flex-wrap justify-content-center gap-2">
                                        {coupon_numbers.map((number) => (
                                            <span key={number}
                                                  className="badge d-inline-flex align-items-center justify-content-center"
                                                  style={{
                                                      fontSize: '1.2rem',
                                                      fontWeight: 700,
                                                      padding: '10px 18px',
                                                      borderRadius: '12px',
                                                      background: 'linear-gradient(135deg, #ffc107 0%, #ff9800 100%)',
                                                      color: '#04072a',
                                                      minWidth: '60px',
                                                  }}>
                                                {number}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            <p className="text-secondary small mb-4">
                                Guarda tus números. El sorteo se realizará en la fecha indicada. ¡Mucha suerte!
                            </p>

                            <div className="d-flex gap-2 justify-content-center">
                                {sweepstake_slug && (
                                    <a href={`/?sweepstake=${sweepstake_slug}`}
                                       className="btn btn-outline-light px-4">
                                        Ver sorteo
                                    </a>
                                )}
                                <a href="/"
                                   className="btn btn-warning fw-bold px-4">
                                    Volver al inicio
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
