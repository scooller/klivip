import { Head, router, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faCalendarDays,
    faCircleUser,
    faCoins,
    faGift,
    faPenToSquare,
    faTicket,
    faTrophy,
    faRightFromBracket,
    faUsers,
} from '@fortawesome/free-solid-svg-icons';
import { useMemo, useState } from 'react';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import FrontFooter from '../Components/Front/FrontFooter';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import { WaButton, WaCarousel, WaCarouselItem } from '../Components/Front/primitives/wa';

export default function Principal({ site, promotions = [], games = [], banners = [] }) {
    const page = usePage();
    const sharedSite = page.props.site ?? site;
    const pagePromotions = page.props.promotions ?? promotions;
    const pageGames = page.props.games ?? games;
    const pageBanners = page.props.banners ?? banners;
    const customer = page.props.auth?.customer ?? null;
    const [isMenuOpen, setIsMenuOpen] = useState(false);

    const currentTime = useMemo(() => {
        return new Intl.DateTimeFormat('es-CL', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        }).format(new Date());
    }, []);

    const drawDate = useMemo(() => {
        return new Intl.DateTimeFormat('es-CL', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(new Date());
    }, []);

    const featuredSlides = useMemo(() => {
        if (pagePromotions.length > 0) {
            return pagePromotions.slice(0, 5).map((promotion, index) => ({
                id: `promo-${index}`,
                badge: promotion.schedule_label ?? 'En vivo',
                title: promotion.offer_label ?? promotion.title,
                description: promotion.description ?? 'Premios y entretenimiento para hoy.',
            }));
        }

        if (pageGames.length > 0) {
            return pageGames.slice(0, 5).map((game, index) => ({
                id: `game-${index}`,
                badge: game.is_featured ? 'Destacado' : 'Juego',
                title: game.title,
                description: game.description ?? 'Disponible para jugar hoy.',
            }));
        }

        return [{
            id: 'fallback-slide',
            badge: 'Bienvenido',
            title: '2 Plataformas de Doble Diversion',
            description: 'Conecta con promociones y sorteos exclusivos desde tu cuenta.',
        }];
    }, [pageGames, pagePromotions]);

    const bannerSlides = useMemo(() => {
        if (pageBanners.length > 0) {
            return pageBanners.map((banner, index) => ({
                id: banner.id ?? `banner-${index + 1}`,
                src: banner.image_url,
                alt: banner.title ?? `Banner ${index + 1}`,
                href: banner.target_url,
            }));
        }

        return [
            {
                id: 'banner-1',
                src: '/images/banners/banner-1-muestra.png',
                alt: 'Promociones en casino con doble diversion',
                href: null,
            },
            {
                id: 'banner-2',
                src: '/images/banners/banner-2-muestra.png',
                alt: 'Banner de juegos y premios de temporada',
                href: null,
            },
            {
                id: 'banner-3',
                src: '/images/banners/banner-1-boton-on.png',
                alt: 'Banner de registro para participar en sorteos',
                href: null,
            },
        ];
    }, [pageBanners]);

    const nextDrawCards = useMemo(() => {
        return featuredSlides.slice(0, 3).map((slide, index) => ({
            id: slide.id,
            title: `Cupon ${String(index + 125).padStart(6, '0')}`,
            subtitle: slide.badge,
            note: slide.title,
        }));
    }, [featuredSlides]);

    const weeklyHighlights = useMemo(() => {
        const labels = ['Miercoles', 'Jueves', 'Sabado'];

        return labels.map((label, index) => ({
            id: `day-${index}`,
            label,
            title: featuredSlides[index]?.title ?? 'Gran premio',
            description: featuredSlides[index]?.badge ?? 'Evento especial',
        }));
    }, [featuredSlides]);

    const openUserPage = () => {
        setIsMenuOpen(false);
        router.visit('/usuario');
    };

    const handleCustomerLogout = () => {
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
            <Head title={`${sharedSite.name} | Principal`} />

            <div className="home-main-page">
                <FrontAppHeader
                    title={null}
                    currentTime={currentTime}
                    hideBack
                    userName={customer?.name ?? 'Invitado'}
                    userSubtitle={site?.name ?? 'Sala'}
                    onOpenMenu={() => setIsMenuOpen(true)}
                />

                <main className="home-main-content wa-stack">
                    <section className="home-banner wa-stack" aria-label="Banner principal">
                        <WaCarousel
                            className="home-banner-carousel"
                            navigation
                            pagination
                            loop
                            autoplay
                            autoplayInterval={4500}
                            mouseDragging
                        >
                            {bannerSlides.map((banner) => (
                                <WaCarouselItem key={banner.id}>
                                    {banner.href ? (
                                        <a href={banner.href} target="_blank" rel="noreferrer" className="home-banner-link">
                                            <img src={banner.src} alt={banner.alt} className="home-banner-image" />
                                        </a>
                                    ) : (
                                        <img src={banner.src} alt={banner.alt} className="home-banner-image" />
                                    )}
                                </WaCarouselItem>
                            ))}
                        </WaCarousel>

                        <WaButton variant="brand" size="small" onClick={openUserPage}>
                            Quiero participar
                        </WaButton>
                    </section>

                    <section className="home-panel wa-stack">
                        <div className="home-panel-heading wa-cluster">
                            <h2>Proximo sorteo</h2>
                            <p>
                                <FontAwesomeIcon icon={faCalendarDays} /> {drawDate} - 20:00
                            </p>
                        </div>

                        <div className="home-draw-grid wa-grid">
                            {nextDrawCards.map((card) => (
                                <article key={card.id} className="home-draw-card">
                                    <span>{card.subtitle}</span>
                                    <h3>{card.title}</h3>
                                    <p>{card.note}</p>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="home-panel wa-stack">
                        <div className="home-panel-heading wa-cluster">
                            <h2>Programacion semanal</h2>
                            <WaButton variant="neutral" size="small" onClick={() => router.visit('/programacion')}>
                                Ver toda la programacion
                            </WaButton>
                        </div>

                        <div className="home-week-grid wa-grid">
                            {weeklyHighlights.map((item) => (
                                <article key={item.id} className="home-week-card">
                                    <span>{item.label}</span>
                                    <h3>{item.title}</h3>
                                    <p>{item.description}</p>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="home-panel wa-stack">
                        <div className="home-panel-heading wa-cluster">
                            <h2>Como ganar cupones?</h2>
                            <p>Completa estos pasos para participar</p>
                        </div>

                        <div className="home-steps-grid wa-grid">
                            <article className="home-step-card wa-stack">
                                <FontAwesomeIcon icon={faCoins} />
                                <h3>Carga saldo</h3>
                                <p>1 credito</p>
                            </article>
                            <article className="home-step-card wa-stack">
                                <FontAwesomeIcon icon={faTrophy} />
                                <h3>Gana desafios</h3>
                                <p>2 creditos</p>
                            </article>
                            <article className="home-step-card wa-stack">
                                <FontAwesomeIcon icon={faGift} />
                                <h3>Canjea premios</h3>
                                <p>3 creditos</p>
                            </article>
                            <article className="home-step-card wa-stack">
                                <FontAwesomeIcon icon={faUsers} />
                                <h3>Invita amigos</h3>
                                <p>4 creditos</p>
                            </article>
                        </div>
                    </section>

                    <FrontFooter site={sharedSite} />
                </main>

                <ActionDrawer
                    className="home-profile-drawer"
                    placement="start"
                    label={customer ? customer.name : 'Menu principal'}
                    open={isMenuOpen}
                    onClose={() => setIsMenuOpen(false)}
                >
                    <div className="home-drawer-profile wa-stack">
                        <div className="home-drawer-avatar">
                            <FontAwesomeIcon icon={faCircleUser} />
                        </div>
                        <strong>{customer?.name ?? 'Invitado'}</strong>
                        <p>{customer?.email ?? 'Conecta tu cuenta para participar en sorteos.'}</p>
                    </div>

                    <nav className="home-drawer-nav wa-stack" aria-label="Menu de usuario">
                        <WaButton variant="text" onClick={openUserPage}>
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

                        <WaButton variant="text" onClick={handleCustomerLogout}>
                            <FontAwesomeIcon icon={faRightFromBracket} slot="start" />
                            Cerrar sesion
                        </WaButton>
                    </nav>
                </ActionDrawer>
            </div>
        </>
    );
}
