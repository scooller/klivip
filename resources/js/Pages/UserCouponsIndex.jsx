import { Head, router, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faCalendar, faCircleCheck, faQrcode, faTicket, faTrophy, faGifts } from '@fortawesome/free-solid-svg-icons';
import { useEffect, useRef, useState } from 'react';
import { Modal } from 'bootstrap';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import CouponDetailModal from '../Components/Front/CouponDetailModal';
import CouponMini from '../Components/Front/CouponMini';
import FrontFooter from '../Components/Front/FrontFooter';

export default function UserCouponsIndex({ site, groupedCoupons = [], pagination = null }) {
    const page = usePage();
    const sharedSite = page.props.site ?? site;
    const customer = page.props.auth?.customer ?? null;
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [selectedCoupon, setSelectedCoupon] = useState(null);
    const [redeemCode, setRedeemCode] = useState('');
    const [submitting, setSubmitting] = useState(false);
    const [feedback, setFeedback] = useState(null);
    const redeemInputRef = useRef(null);

    const redeemSuccess = page.props.redeemSuccess ?? null;
    const redeemErrors = page.props.errors ?? {};
    const successModalRef = useRef(null);
    const successModalInstance = useRef(null);

    useEffect(() => {
        if (!successModalRef.current) {
            return;
        }

        successModalInstance.current = Modal.getOrCreateInstance(successModalRef.current, { backdrop: true });

        if (redeemSuccess) {
            successModalInstance.current.show();
        }
    }, [redeemSuccess]);

    useEffect(() => {
        if (redeemErrors.code) {
            setFeedback({ variant: 'danger', message: redeemErrors.code });
        }
    }, [redeemErrors]);

    const handleRedeem = (e) => {
        e.preventDefault();
        setSubmitting(true);
        setFeedback(null);

        router.post('/usuario/cupones/redeem', { code: redeemCode }, {
            preserveScroll: true,
            onSuccess: () => {
                setRedeemCode('');
            },
            onError: (errors) => {
                setFeedback({
                    variant: 'danger',
                    message: errors.code || 'No se pudo procesar el código.',
                });
            },
            onFinish: () => {
                setSubmitting(false);
            },
        });
    };

    const handleLogout = () => {
        router.post('/usuario/logout', {}, {
            preserveScroll: true,
            onFinish: () => {
                setIsMenuOpen(false);
                router.visit('/cuenta');
            },
        });
    };

    const totalCoupons = pagination?.total ?? groupedCoupons.reduce(
        (sum, group) => sum + group.coupons.length,
        0,
    );

    return (
        <>
            <Head title={`Mis cupones | ${sharedSite.name}`} />

            <div className="home-main-page user-coupons-page d-flex flex-column min-vh-100">
                <FrontAppHeader
                    site={sharedSite}
                    title="Mis Cupones"
                    onBack={() => router.visit('/')}
                    onOpenMenu={() => setIsMenuOpen(true)}
                />

                <main className="home-main-content container-fluid user-coupons-content p-0 flex-grow-1">
                    {/* Formulario de canje por código / QR */}
                    <section className="redeem-form-card mb-4" aria-label="Canjear código">
                        <div className="card border-0 shadow-sm"
                             style={{ background: 'rgba(255,255,255,0.05)', borderRadius: '16px' }}>
                            <div className="card-body p-3">
                                <div className="d-flex align-items-center gap-2 mb-2">
                                    <div className="d-inline-flex align-items-center justify-content-center rounded-circle"
                                         style={{ width: '40px', height: '40px', background: 'rgba(255,193,7,0.15)' }}>
                                        <FontAwesomeIcon icon={faQrcode} style={{ color: '#ffc107' }} />
                                    </div>
                                    <div>
                                        <h2 className="h6 mb-0 fw-bold">Canjear cupones</h2>
                                        <p className="small text-secondary mb-0">Ingresa el código o escanea un QR</p>
                                    </div>
                                </div>

                                {feedback && (
                                    <div className={`alert alert-${feedback.variant} py-2 small mb-2`} role="alert">
                                        {feedback.message}
                                    </div>
                                )}

                                <form onSubmit={handleRedeem} className="d-flex gap-2">
                                    <input
                                        ref={redeemInputRef}
                                        type="text"
                                        className="form-control form-control-lg"
                                        style={{
                                            background: 'rgba(255,255,255,0.08)',
                                            borderColor: 'rgba(255,255,255,0.15)',
                                            color: '#e8eaf6',
                                        }}
                                        placeholder="Ej: ABC123-XK7"
                                        value={redeemCode}
                                        onChange={(e) => setRedeemCode(e.target.value)}
                                        disabled={submitting}
                                        required
                                    />
                                    <button
                                        type="submit"
                                        className="btn btn-warning fw-bold px-3 px-lg-4"
                                        disabled={submitting || !redeemCode.trim()}
                                    >
                                        {submitting ? (
                                            <span className="spinner-border spinner-border-sm" role="status" />
                                        ) : (
                                            <>
                                                <FontAwesomeIcon icon={faTicket} className="me-1" />
                                                Canjear
                                            </>
                                        )}
                                    </button>
                                </form>
                            </div>
                        </div>
                    </section>

                    <section className="user-coupons-summary text-center mb-4">
                        <div className="d-inline-flex align-items-center gap-2 px-4 py-2 rounded-pill"
                             style={{ background: 'rgba(255,193,7,0.15)' }}>
                            <FontAwesomeIcon icon={faTicket} style={{ color: '#ffc107' }} />
                            <span className="fw-semibold text-warning">
                                {totalCoupons} {totalCoupons === 1 ? 'cupón' : 'cupones'} activos
                            </span>
                        </div>
                    </section>

                    {groupedCoupons.length > 0 ? (
                        <div className="d-flex flex-column gap-4">
                            {groupedCoupons.map((group) => (
                                <section
                                    key={group.sweepstake_slug}
                                    className="sweepstake-coupon-group container-fluid"
                                    aria-label={`Cupones de ${group.sweepstake_name}`}
                                >
                                    <div className="sweepstake-coupon-group-header d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                                        <div className="d-flex align-items-center gap-2">
                                            <FontAwesomeIcon icon={faTrophy} style={{ color: '#ffc107' }} />
                                            <h3 className="mb-0 fw-bold">{group.sweepstake_name}</h3>
                                        </div>
                                        {group.draw_at && (
                                            <span className="badge bg-warning text-dark">
                                                <FontAwesomeIcon icon={faCalendar} className="me-1" />
                                                Sorteo: {group.draw_at}
                                            </span>
                                        )}
                                    </div>

                                    {group.prize && (
                                        <p className="text-secondary small mb-3">
                                            <FontAwesomeIcon icon={faGifts} className="me-1" />
                                            Premio: {group.prize}
                                        </p>
                                    )}

                                    <div className="row row-cols-2 row-cols-md-4 row-cols-lg-6 g-2">
                                        {group.coupons.map((coupon) => (
                                            <div key={coupon.id} className="col">
                                                <CouponMini
                                                    number={coupon.number}
                                                    isUsed={coupon.is_used}
                                                    sweepstakeName={group.sweepstake_name}
                                                    date={coupon.obtained_at ? `Cobrado: ${coupon.obtained_at}` : (group.draw_at ? `Sorteo: ${group.draw_at}` : null)}
                                                    onClick={() => setSelectedCoupon({
                                                        ...coupon,
                                                        sweepstake_name: group.sweepstake_name,
                                                        prize: group.prize,
                                                        draw_at: group.draw_at,
                                                    })}
                                                />
                                            </div>
                                        ))}
                                    </div>
                                </section>
                            ))}
                        </div>
                    ) : (
                        <div className="alert alert-info text-center" role="alert">
                            <strong>No tienes cupones activos</strong>
                            <p className="mb-0 mt-1">Participa en un sorteo para obtener tus primeros cupones.</p>
                        </div>
                    )}

                    {pagination && pagination.last_page > 1 ? (
                        <nav aria-label="Paginación de cupones" className="mt-4">
                            <ul className="pagination justify-content-center">
                                <li className={`page-item ${!pagination.prev_page_url ? 'disabled' : ''}`}>
                                    <button
                                        className="page-link"
                                        disabled={!pagination.prev_page_url}
                                        onClick={() => {
                                            if (!pagination.prev_page_url) {
                                                return;
                                            }

                                            router.visit(pagination.prev_page_url, { preserveScroll: true });
                                        }}
                                    >
                                        Anterior
                                    </button>
                                </li>
                                <li className="page-item disabled">
                                    <button className="page-link" disabled>
                                        Página {pagination.current_page} de {pagination.last_page}
                                        <small className="ms-1">({pagination.total} cupones)</small>
                                    </button>
                                </li>
                                <li className={`page-item ${!pagination.next_page_url ? 'disabled' : ''}`}>
                                    <button
                                        className="page-link"
                                        disabled={!pagination.next_page_url}
                                        onClick={() => {
                                            if (!pagination.next_page_url) {
                                                return;
                                            }

                                            router.visit(pagination.next_page_url, { preserveScroll: true });
                                        }}
                                    >
                                        Siguiente
                                    </button>
                                </li>
                            </ul>
                        </nav>
                    ) : null}
                </main>

                <FrontFooter site={sharedSite} />

                <ActionDrawer
                    site={sharedSite}
                    className="home-profile-drawer"
                    placement="start"
                    label={customer ? customer.name : 'Menu principal'}
                    open={isMenuOpen}
                    onClose={() => setIsMenuOpen(false)}
                    customer={customer}
                    onLogout={handleLogout}
                />

                {/* Modal de canje exitoso */}
                <div className="modal fade" ref={successModalRef} tabIndex="-1" aria-hidden="true">
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content"
                             style={{ background: 'linear-gradient(135deg, #04072a 0%, #090f3c 100%)', color: '#e8eaf6', borderRadius: '20px', border: '1px solid rgba(255,193,7,0.3)' }}>
                            <div className="modal-body p-4 text-center">
                                {redeemSuccess && (
                                    <>
                                        <div className="d-inline-flex align-items-center justify-content-center rounded-circle mb-3"
                                             style={{ width: '72px', height: '72px', background: 'rgba(25,135,84,0.2)', border: '3px solid rgba(25,135,84,0.5)' }}>
                                            <FontAwesomeIcon icon={faCircleCheck} style={{ fontSize: '2.2rem', color: '#28a745' }} />
                                        </div>
                                        <h2 className="fw-bold mb-1">¡Cupones obtenidos!</h2>
                                        <p className="text-secondary mb-3">
                                            Has obtenido <strong className="text-warning">{redeemSuccess.coupon_count}</strong> {redeemSuccess.coupon_count === 1 ? 'cupón' : 'cupones'} para:
                                        </p>
                                        <div className="d-inline-flex align-items-center gap-2 mb-3 px-3 py-1 rounded-pill"
                                             style={{ background: 'rgba(255,193,7,0.15)' }}>
                                            <FontAwesomeIcon icon={faTrophy} style={{ color: '#ffc107' }} />
                                            <span className="fw-semibold text-warning">{redeemSuccess.sweepstake_name}</span>
                                        </div>
                                        <div className="d-flex flex-wrap justify-content-center gap-2 mb-3">
                                            {redeemSuccess.coupon_numbers.map((number) => (
                                                <span key={number}
                                                      className="badge d-inline-flex align-items-center justify-content-center"
                                                      style={{
                                                          fontSize: '1.1rem',
                                                          fontWeight: 700,
                                                          padding: '8px 14px',
                                                          borderRadius: '10px',
                                                          background: 'linear-gradient(135deg, #ffc107 0%, #ff9800 100%)',
                                                          color: '#04072a',
                                                          minWidth: '50px',
                                                      }}>
                                                    {String(number).padStart(5, '0')}
                                                </span>
                                            ))}
                                        </div>
                                        <button type="button" className="btn btn-warning fw-bold w-100" data-bs-dismiss="modal">
                                            ¡Genial!
                                        </button>
                                    </>
                                )}
                            </div>
                        </div>
                    </div>
                </div>

                {/* Modal de detalle del cupón (ticket) */}
                <CouponDetailModal
                    coupon={selectedCoupon}
                    open={selectedCoupon !== null}
                    onClose={() => setSelectedCoupon(null)}
                />
            </div>
        </>
    );
}
