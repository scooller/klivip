import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import UserBenefitsCard from '../Components/Front/UserBenefitsCard';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';

export default function UserCouponsIndex({ site, activeCoupons = [], pagination = null }) {
    const page = usePage();
    const customer = page.props.auth?.customer ?? null;
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    const currentTime = useMemo(() => {
        return new Intl.DateTimeFormat('es-CL', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        }).format(new Date());
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

                            router.visit(`/usuario/cupones/${coupon.id}`);
                        }}
                    />

                    {pagination ? (
                        <div className="coupons-pagination d-flex flex-wrap gap-2 align-items-center" role="navigation" aria-label="Paginacion de cupones">
                            <button
                                className="btn btn-outline-secondary btn-sm"
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
                            </button>

                            <p className="mb-0">
                                Pagina {pagination.current_page} de {pagination.last_page} · {pagination.total} cupones
                            </p>

                            <button
                                className="btn btn-primary btn-sm"
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
                            </button>
                        </div>
                    ) : null}
                </main>

                <ActionDrawer
                    className="home-profile-drawer"
                    placement="start"
                    label={customer ? customer.name : 'Menu principal'}
                    open={isMenuOpen}
                    onClose={() => setIsMenuOpen(false)}
                    customer={customer}
                    onLogout={handleLogout}
                />
            </div>
        </>
    );
}
