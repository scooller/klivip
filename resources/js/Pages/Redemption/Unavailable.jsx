import { Head } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCircleExclamation } from '@fortawesome/free-solid-svg-icons';

export default function Unavailable({ reason }) {
    return (
        <>
            <Head title="No disponible" />

            <div className="min-vh-100 d-flex align-items-center justify-content-center"
                 style={{ background: 'linear-gradient(135deg, #04072a 0%, #090f3c 100%)', color: '#e8eaf6' }}>
                <div className="container py-5">
                    <div className="row justify-content-center">
                        <div className="col-12 col-lg-5 text-center">
                            <div className="d-inline-flex align-items-center justify-content-center rounded-circle mb-4"
                                 style={{
                                     width: '96px',
                                     height: '96px',
                                     background: 'rgba(220,53,69,0.15)',
                                     border: '3px solid rgba(220,53,69,0.4)',
                                 }}>
                                <FontAwesomeIcon icon={faCircleExclamation} style={{ fontSize: '3rem', color: '#dc3545' }} />
                            </div>

                            <h1 className="fw-bold mb-3" style={{ fontSize: '1.8rem' }}>
                                No disponible
                            </h1>

                            <p className="text-secondary mb-4" style={{ fontSize: '1.1rem' }}>
                                {reason || 'Este link no está disponible en este momento.'}
                            </p>

                            <a href="/"
                               className="btn btn-outline-light px-4">
                                Volver al inicio
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
