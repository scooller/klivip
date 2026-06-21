import { Head, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import UserBenefitsCard from '../Components/Front/UserBenefitsCard';
import CouponDetailModal from '../Components/Front/CouponDetailModal';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import FrontFooter from '../Components/Front/FrontFooter';

export default function UserCouponsIndex({ site, activeCoupons = [], pagination = null }) {
    const page = usePage();
    const sharedSite = page.props.site ?? site;
    const customer = page.props.auth?.customer ?? null;
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [selectedCoupon, setSelectedCoupon] = useState(null);

    const [currentTime, setCurrentTime] = useState('');

    useEffect(() => {
        const updateTime = () => {
            setCurrentTime(new Intl.DateTimeFormat('es-CL', {
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
            }).format(new Date()));
        };

        updateTime();
        const interval = setInterval(updateTime, 60 * 1000);

        return () => clearInterval(interval);
    }, []);

    const handleLogout = () => {
        router.post('/usuario/logout', {}, {
            preserveScroll: true,
            onFinish: () => {
                setIsMenuOpen(false);
                router.visit('/');
            },
        });
    };

    return (
        <>
            <Head title={`Mis cupones | ${site.name}`} />

            <div className="home-main-page user-coupons-page">
                <FrontAppHeader
                    site={sharedSite}
                    title="Mis Cupones"
                    currentTime={currentTime}
                    onBack={() => router.visit('/principal')}
                    onOpenMenu={() => setIsMenuOpen(true)}
                />

                <main className="home-main-content container-fluid user-coupons-content">
                    <UserBenefitsCard
                        activeCoupons={activeCoupons}
                        onCouponSelect={(coupon) => {
                            if (!coupon?.id) {
                                return;
                            }

                            setSelectedCoupon(coupon);
                        }}
                    />

                    {pagination ? (
                        <nav aria-label="Paginación de cupones activos">
                            <ul class="pagination justify-content-center mt-4">
                                <li class="page-item"><button
                                    className="page-link"
                                    disabled={!pagination.prev_page_url}
                                    onClick={() => {
                                        if (!pagination.prev_page_url) {
                                            return;
                                        }

                                        router.visit(pagination.prev_page_url, {
                                            preserveScroll: true,
                                        });
                                    }}
                                >
                                    Anterior
                                </button></li>

                                <li class="page-item disabled">
                                    <button className="page-link" disabled>
                                        Pagina {pagination.current_page} de {pagination.last_page} · {pagination.total} cupones
                                    </button>
                                </li>

                                <li class="page-item"><button
                                    className="page-link"
                                    disabled={!pagination.next_page_url}
                                    onClick={() => {
                                        if (!pagination.next_page_url) {
                                            return;
                                        }

                                        router.visit(pagination.next_page_url, {
                                            preserveScroll: true,
                                        });
                                    }}
                                    >
                                    Siguiente
                                </button></li>
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

                <CouponDetailModal
                    coupon={selectedCoupon}
                    open={selectedCoupon !== null}
                    onClose={() => setSelectedCoupon(null)}
                />
            </div>
        </>
    );
}
