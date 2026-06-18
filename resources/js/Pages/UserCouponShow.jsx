import { Head, router, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import UserBenefitsCard from '../Components/Front/UserBenefitsCard';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';

export default function UserCouponShow({ site, coupon }) {
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
            <Head title={`Cupon ${coupon?.code ?? ''} | ${site.name}`} />

            <div className="home-main-page user-coupons-page">
                <FrontAppHeader
                    title="Mis Cupones"
                    currentTime={currentTime}
                    onBack={() => router.visit('/usuario/cupones')}
                    onOpenMenu={() => setIsMenuOpen(true)}
                />

                <main className="home-main-content container-fluid user-coupons-content">
                    <UserBenefitsCard
                        mode="detail"
                        activeCoupons={coupon ? [coupon] : []}
                        actionLabel="Ver todos los cupones"
                        onAction={() => router.visit('/usuario/cupones')}
                    />
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
