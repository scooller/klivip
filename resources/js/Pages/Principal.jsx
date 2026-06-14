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
import { useEffect, useMemo, useState } from 'react';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import FrontFooter from '../Components/Front/FrontFooter';
import FrontAppHeader from '../Components/Front/FrontAppHeader';

export default function Principal({ site, promotions = [], games = [], banners = [] }) {
    const page = usePage();
    const sharedSite = page.props.site ?? site;
    const pagePromotions = page.props.promotions ?? promotions;
    const pageGames = page.props.games ?? games;
    const pageBanners = page.props.banners ?? banners;
    const customer = page.props.auth?.customer ?? null;
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [gameSlidesPerPage, setGameSlidesPerPage] = useState(3);

    useEffect(() => {
        const resolveSlidesPerPage = () => {
            if (window.innerWidth < 768) {
                setGameSlidesPerPage(1);

                return;
            }

            if (window.innerWidth < 1024) {
                setGameSlidesPerPage(2);

                return;
            }

            setGameSlidesPerPage(3);
        };

        resolveSlidesPerPage();
        window.addEventListener('resize', resolveSlidesPerPage);

        return () => {
            window.removeEventListener('resize', resolveSlidesPerPage);
        };
    }, []);

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

                <main className="home-main-content d-flex flex-column gap-3">
                    <section className="home-banner d-flex flex-column gap-3" aria-label="Banner principal">
                        <div id="bannerCarousel" className="carousel slide home-banner-carousel" data-bs-ride="carousel">
                            <div className="carousel-inner">
                                {bannerSlides.map((banner, index) => (
                                    <div key={banner.id} className={`carousel-item ${index === 0 ? 'active' : ''} home-banner-item`}>
                                        {banner.href ? (
                                            <a href={banner.href} target="_blank" rel="noreferrer" className="home-banner-link">
                                                <img src={banner.src} alt={banner.alt} className="home-banner-image" />
                                            </a>
                                        ) : (
                                            <img src={banner.src} alt={banner.alt} className="home-banner-image" />
                                        )}
                                    </div>
                                ))}
                            </div>
                            <button className="carousel-control-prev" type="button" data-bs-target="#bannerCarousel" data-bs-slide="prev">
                                <span className="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span className="visually-hidden">Anterior</span>
                            </button>
                            <button className="carousel-control-next" type="button" data-bs-target="#bannerCarousel" data-bs-slide="next">
                                <span className="carousel-control-next-icon" aria-hidden="true"></span>
                                <span className="visually-hidden">Siguiente</span>
                            </button>
                        </div>

                    </section>

                    <section className="home-panel d-flex flex-column gap-3">
                        <div className="home-panel-heading d-flex flex-wrap gap-2 align-items-center">
                            <h2>Proximo sorteo</h2>
                            <p>
                                <FontAwesomeIcon icon={faCalendarDays} /> {drawDate} - 20:00
                            </p>
                        </div>

                        <div className="home-draw-grid d-grid gap-3">
                            {nextDrawCards.map((card) => (
                                <article key={card.id} className="home-draw-card">
                                    <span>{card.subtitle}</span>
                                    <h3>{card.title}</h3>
                                    <p>{card.note}</p>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="home-panel d-flex flex-column gap-3">
                        <div className="home-panel-heading d-flex flex-wrap gap-2 align-items-center">
                            <h2>Programacion semanal</h2>
                            <button className="btn btn-outline-secondary btn-sm" onClick={() => router.visit('/programacion')}>
                                Ver toda la programacion
                            </button>
                        </div>

                        <div className="home-week-grid d-grid gap-3">
                            {weeklyHighlights.map((item) => (
                                <article key={item.id} className="home-week-card">
                                    <span>{item.label}</span>
                                    <h3>{item.title}</h3>
                                    <p>{item.description}</p>
                                </article>
                            ))}
                        </div>
                    </section>

                    <section className="home-panel home-games-section d-flex flex-column gap-3" aria-label="Juegos del sitio">
                        <div className="home-panel-heading d-flex flex-wrap gap-2 align-items-center">
                            <h2>Juegos del sitio</h2>
                            <p>Disponibles hoy en {sharedSite.name}</p>
                        </div>

                        {pageGames.length > 0 ? (
                            <div id="gamesCarousel" className="carousel slide home-games-carousel" data-bs-ride="carousel">
                                <div className="carousel-inner">
                                    {pageGames.map((game, index) => (
                                        <div key={game.id ?? `site-game-${index}`} className={`carousel-item ${index === 0 ? 'active' : ''} home-games-slide`}>
                                            <article className="home-game-card">
                                                <div className="home-game-image">
                                                    <img
                                                        src={game.image_url}
                                                        alt={game.title ?? `Juego ${index + 1}`}
                                                        loading="lazy"
                                                    />
                                                    <span className="home-game-badge">
                                                        {game.is_featured ? 'Destacado' : 'Juego'}
                                                    </span>
                                                </div>

                                                <div className="home-game-content">
                                                    <h3>{game.title}</h3>
                                                    <p>{game.description ?? 'Disponible para jugar en este sitio.'}</p>
                                                </div>

                                                <div className="home-game-actions">
                                                    <button
                                                        className="btn btn-primary btn-sm"
                                                        disabled={!game.url}
                                                        onClick={() => {
                                                            if (!game.url) {
                                                                return;
                                                            }

                                                            window.open(game.url, '_blank', 'noopener,noreferrer');
                                                        }}
                                                    >
                                                        Jugar ahora
                                                    </button>
                                                </div>
                                            </article>
                                        </div>
                                    ))}
                                </div>
                                <button className="carousel-control-prev" type="button" data-bs-target="#gamesCarousel" data-bs-slide="prev">
                                    <span className="carousel-control-prev-icon" aria-hidden="true"></span>
                                    <span className="visually-hidden">Anterior</span>
                                </button>
                                <button className="carousel-control-next" type="button" data-bs-target="#gamesCarousel" data-bs-slide="next">
                                    <span className="carousel-control-next-icon" aria-hidden="true"></span>
                                    <span className="visually-hidden">Siguiente</span>
                                </button>
                            </div>
                        ) : (
                            <div className="alert alert-warning empty-state-callout" role="alert">
                                <strong>Sin juegos disponibles</strong>
                                <p className="mb-0">Este sitio aun no tiene juegos activos.</p>
                            </div>
                        )}
                    </section>

                    <section className="home-panel d-flex flex-column gap-3">
                        <div className="home-panel-heading d-flex flex-wrap gap-2 align-items-center">
                            <h2>Como ganar cupones?</h2>
                            <p>Completa estos pasos para participar</p>
                        </div>

                        <div className="home-steps-grid d-grid gap-3">
                            <article className="home-step-card d-flex flex-column gap-3">
                                <FontAwesomeIcon icon={faCoins} />
                                <h3>Carga saldo</h3>
                                <p>1 credito</p>
                            </article>
                            <article className="home-step-card d-flex flex-column gap-3">
                                <FontAwesomeIcon icon={faTrophy} />
                                <h3>Gana desafios</h3>
                                <p>2 creditos</p>
                            </article>
                            <article className="home-step-card d-flex flex-column gap-3">
                                <FontAwesomeIcon icon={faGift} />
                                <h3>Canjea premios</h3>
                                <p>3 creditos</p>
                            </article>
                            <article className="home-step-card d-flex flex-column gap-3">
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
                    <div className="home-drawer-profile d-flex flex-column gap-3">
                        <div className="home-drawer-avatar">
                            <FontAwesomeIcon icon={faCircleUser} />
                        </div>
                        <strong>{customer?.name ?? 'Invitado'}</strong>
                        <p className="mb-0">{customer?.email ?? 'Conecta tu cuenta para participar en sorteos.'}</p>
                    </div>

                    <nav className="home-drawer-nav d-flex flex-column gap-2" aria-label="Menu de usuario">
                        <button className="btn btn-link text-start" onClick={openUserPage}>
                            <FontAwesomeIcon icon={faPenToSquare} className="me-2" />
                            Editar perfil
                        </button>
                        <button className="btn btn-link text-start" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/usuario/cupones');
                        }}>
                            <FontAwesomeIcon icon={faTicket} className="me-2" />
                            Mis cupones
                        </button>
                        <button className="btn btn-link text-start" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/programacion');
                        }}>
                            <FontAwesomeIcon icon={faGift} className="me-2" />
                            Sorteos
                        </button>

                        <button className="btn btn-link text-start" onClick={handleCustomerLogout}>
                            <FontAwesomeIcon icon={faRightFromBracket} className="me-2" />
                            Cerrar sesion
                        </button>
                    </nav>
                </ActionDrawer>
            </div>
        </>
    );
}
