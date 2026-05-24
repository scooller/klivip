import { Head, router, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faGift, faPenToSquare, faRightFromBracket, faTicket, faTrophy } from '@fortawesome/free-solid-svg-icons';
import { useMemo, useState } from 'react';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import UserBenefitsCard from '../Components/Front/UserBenefitsCard';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import { WaButton } from '../Components/Front/primitives/wa';

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

                <main className="home-main-content user-coupons-content">
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
                >
                    <div className="home-drawer-profile">
                        <strong>{customer?.name ?? 'Invitado'}</strong>
                        <p>{customer?.email ?? 'Conecta tu cuenta para participar en sorteos.'}</p>
                    </div>

                    <nav className="home-drawer-nav wa-stack" aria-label="Menu de usuario">
                        <WaButton variant="text" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/principal');
                        }}>
                            <FontAwesomeIcon icon={faTrophy} slot="start" />
                            Principal
                        </WaButton>
                        <WaButton variant="text" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/usuario');
                        }}>
                            <FontAwesomeIcon icon={faPenToSquare} slot="start" />
                            Editar perfil
                        </WaButton>
                        <WaButton variant="text" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/usuario/cupones');
                        }}>
                            <FontAwesomeIcon icon={faTicket} slot="start" />
                            Mis cupones
                        </WaButton>
                        <WaButton variant="text" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/programacion');
                        }}>
                            <FontAwesomeIcon icon={faGift} slot="start" />
                            Sorteos
                        </WaButton>
                        <WaButton variant="text" onClick={handleLogout}>
                            <FontAwesomeIcon icon={faRightFromBracket} slot="start" />
                            Cerrar sesion
                        </WaButton>
                    </nav>
                </ActionDrawer>
            </div>
        </>
    );
}
