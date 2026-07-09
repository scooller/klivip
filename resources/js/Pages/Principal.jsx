import { Head, router, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faCalendarDays,
    faCoins,
    faGift,
    faTrophy,
    faUsers,
    faBarcode,
} from '@fortawesome/free-solid-svg-icons';
import { useEffect, useMemo, useRef, useState } from 'react';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import FrontFooter from '../Components/Front/FrontFooter';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import CouponMini from '../Components/Front/CouponMini';

const POPUP_SESSION_KEY = 'klivip_home_popup_shown';

export default function Principal({ site, promotions = [], games = [], banners = [], activeCoupons = [] }) {
    const page = usePage();
    const sharedSite = page.props.site ?? site;
    const pagePromotions = page.props.promotions ?? promotions;
    const pageGames = page.props.games ?? games;
    const pageBanners = page.props.banners ?? banners;
    const pageActiveCoupons = page.props.activeCoupons ?? activeCoupons;
    const customer = page.props.auth?.customer ?? null;
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [gameSlidesPerPage, setGameSlidesPerPage] = useState(3);
    const [couponSlidesPerPage, setCouponSlidesPerPage] = useState(4);
    const carouselRefs = useRef([]);
    const popupRef = useRef(null);
    const siteSetting = page.props.siteSetting ?? {};
    const popupData = siteSetting.popup ?? { enabled: false, image: null, link: null };

    useEffect(() => {
        const resolveSlidesPerPage = () => {
            if (window.innerWidth < 768) {
                setGameSlidesPerPage(1);
                setCouponSlidesPerPage(1);

                return;
            }

            if (window.innerWidth < 1024) {
                setGameSlidesPerPage(2);
                setCouponSlidesPerPage(2);

                return;
            }

            setGameSlidesPerPage(3);
            setCouponSlidesPerPage(4);
        };

        resolveSlidesPerPage();
        window.addEventListener('resize', resolveSlidesPerPage);

        return () => {
            window.removeEventListener('resize', resolveSlidesPerPage);
        };
    }, []);

    useEffect(() => {
        import('bootstrap/dist/js/bootstrap.bundle.js').then(({ Carousel, Modal }) => {
            carouselRefs.current.forEach((el) => {
                if (el) {
                    new Carousel(el, { ride: 'carousel', interval: 5000 });
                }
            });

            if (popupData.enabled && popupData.image && popupRef.current) {
                if (!sessionStorage.getItem(POPUP_SESSION_KEY)) {
                    const modal = new Modal(popupRef.current);
                    modal.show();
                    sessionStorage.setItem(POPUP_SESSION_KEY, 'true');
                }
            }
        });
    }, [popupData.enabled, popupData.image]);

    const [drawDate, setDrawDate] = useState('');

    useEffect(() => {
        const updateTimes = () => {
            setDrawDate(new Intl.DateTimeFormat('es-CL', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
            }).format(new Date()));
        };

        updateTimes();
        const interval = setInterval(updateTimes, 60 * 1000);

        return () => clearInterval(interval);
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

    // banner Home
    const bannerHome = useMemo(() => {
        // solo banner section == home
        if (pageBanners.length > 0 && pageBanners.some(banner => banner.section === 'home')) {
            return pageBanners.filter(banner => banner.section === 'home').map((banner, index) => ({
                id: banner.id ?? `banner-${index + 1}`,
                src: banner.image_url,
                alt: banner.title ?? `Banner ${index + 1}`,
                href: banner.target_url,
            }));
        }

        return [];
    }, [pageBanners]);

    // banner Events
    const bannersEvents = useMemo(() => {
        // solo banner section == events
        if (pageBanners.length > 0 && pageBanners.some(banner => banner.section === 'events')) {
            return pageBanners.filter(banner => banner.section === 'events').map((banner, index) => ({
                id: banner.id ?? `banner-${index + 1}`,
                src: banner.image_url,
                alt: banner.title ?? `Banner ${index + 1}`,
                href: banner.target_url,
            }));
        }

        return [];
    }, [pageBanners]);

    // banner Games
    const bannersGames = useMemo(() => {
        // solo banner section == games
        if (pageBanners.length > 0 && pageBanners.some(banner => banner.section === 'games')) {
            return pageBanners.filter(banner => banner.section === 'games').map((banner, index) => ({
                id: banner.id ?? `banner-${index + 1}`,
                src: banner.image_url,
                alt: banner.title ?? `Banner ${index + 1}`,
                href: banner.target_url,
            }));
        }

        return [];
    }, [pageBanners]);

    const nextDrawCards = useMemo(() => {
        return featuredSlides.slice(0, 3).map((slide, index) => ({
            id: slide.id,
            title: `Cupon ${String(index + 125).padStart(5, '0')}`,
            subtitle: slide.badge,
            note: slide.title,
        }));
    }, [featuredSlides]);

    const couponChunks = useMemo(() => {
        if (!pageActiveCoupons || pageActiveCoupons.length === 0) {
            return [];
        }

        const chunks = [];
        for (let i = 0; i < pageActiveCoupons.length; i += couponSlidesPerPage) {
            chunks.push(pageActiveCoupons.slice(i, i + couponSlidesPerPage));
        }
        return chunks;
    }, [pageActiveCoupons, couponSlidesPerPage]);

    const weeklyHighlights = useMemo(() => {
        const labels = ['Miercoles', 'Jueves', 'Sabado'];

        return labels.map((label, index) => ({
            id: `day-${index}`,
            label,
            title: featuredSlides[index]?.title ?? 'Gran premio',
            description: featuredSlides[index]?.badge ?? 'Evento especial',
        }));
    }, [featuredSlides]);

    const handleCustomerLogout = () => {
        router.post('/usuario/logout', {}, {
            preserveScroll: true,
            onFinish: () => {
                setIsMenuOpen(false);
                router.visit('/cuenta');
            },
        });
    };

    return (
        <>
            <Head title={`${sharedSite.name} | Principal`} />

            <div className="home-main-page d-flex flex-column min-vh-100">
                <FrontAppHeader
                    site={sharedSite}
                    title={null}
                    hideBack
                    userName={customer?.name ?? 'Invitado'}
                    userSubtitle={site?.name ?? 'Sala'}
                    onOpenMenu={() => setIsMenuOpen(true)}
                />

                <main className="home-main-content container mx-auto g-0 p-0 flex-grow-1">
                    <section className="banner-slide home-banner" aria-label="Banner principal">
                        <div id="bannerHome" className="carousel slide" ref={(el) => { carouselRefs.current[0] = el; }}>
                            <div className="carousel-inner">
                                {bannerHome.map((banner, index) => (
                                    <div key={banner.id} className={`carousel-item ${index === 0 ? 'active' : ''} banner-slide-item`}>
                                        {banner.href ? (
                                            <a href={banner.href} target="_blank" rel="noreferrer" className="banner-slide-link">
                                                <img src={banner.src} alt={banner.alt} className="banner-slide-image" />
                                            </a>
                                        ) : (
                                            <img src={banner.src} alt={banner.alt} className="banner-slide-image" />
                                        )}
                                    </div>
                                ))}
                            </div>
                            <button className="carousel-control-prev" type="button" data-bs-target="#bannerHome" data-bs-slide="prev">
                                <span className="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span className="visually-hidden">Anterior</span>
                            </button>
                            <button className="carousel-control-next" type="button" data-bs-target="#bannerHome" data-bs-slide="next">
                                <span className="carousel-control-next-icon" aria-hidden="true"></span>
                                <span className="visually-hidden">Siguiente</span>
                            </button>
                        </div>

                    </section>

                    {/* Banner Evento */}
                    <section className="banner-slide event-banner" aria-label="Banner evento">
                        <div id="bannerEvents" className="carousel slide">
                            <div className="carousel-inner">
                                {bannersEvents.map((banner, index) => (
                                    <div key={banner.id} className={`carousel-item ${index === 0 ? 'active' : ''} banner-slide-item`}>
                                        {banner.href ? (
                                            <a href={banner.href} target="_blank" rel="noreferrer" className="banner-slide-link">
                                                <img src={banner.src} alt={banner.alt} className="banner-slide-image" />
                                            </a>
                                        ) : (
                                            <img src={banner.src} alt={banner.alt} className="banner-slide-image" />
                                        )}
                                    </div>
                                ))}
                            </div>
                        </div>
                    </section>

                    {/* Banner games */}
                    <section className="banner-slide games-banner" aria-label="Banner juegos">
                        <div id="bannerGames" className="carousel slide" ref={(el) => { carouselRefs.current[1] = el; }}>
                            <div className="carousel-inner">
                                {bannersGames.map((banner, index) => (
                                    <div key={banner.id} className={`carousel-item ${index === 0 ? 'active' : ''} banner-slide-item`}>
                                        {banner.href ? (
                                            <a href={banner.href} target="_blank" rel="noreferrer" className="banner-slide-link">
                                                <img src={banner.src} alt={banner.alt} className="banner-slide-image" />
                                            </a>
                                        ) : (
                                            <img src={banner.src} alt={banner.alt} className="banner-slide-image" />
                                        )}
                                    </div>
                                ))}
                            </div>
                            <button className="carousel-control-prev" type="button" data-bs-target="#bannerGames" data-bs-slide="prev">
                                <span className="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span className="visually-hidden">Anterior</span>
                            </button>
                            <button className="carousel-control-next" type="button" data-bs-target="#bannerGames" data-bs-slide="next">
                                <span className="carousel-control-next-icon" aria-hidden="true"></span>
                                <span className="visually-hidden">Siguiente</span>
                            </button>
                        </div>
                    </section>

                    {couponChunks.length > 0 && (
                        <section className="home-panel user-coupons-section d-flex flex-column gap-3" aria-label="Mis Cupones">
                            <div className="home-panel-heading d-flex flex-wrap align-items-center justify-content-between gap-2">
                                <h2 className="mb-0 fw-bold">
                                    <FontAwesomeIcon icon={faBarcode} className="me-2" />
                                    Mis Cupones
                                </h2>
                                <button
                                    className="btn btn-outline-warning btn-sm"
                                    onClick={() => router.visit('/usuario/cupones')}
                                >
                                    Ver todos los cupones
                                </button>
                            </div>

                            <div id="couponsCarousel" className="carousel slide" ref={(el) => { carouselRefs.current[2] = el; }}>
                                <div className="carousel-inner">
                                    {couponChunks.map((chunk, index) => (
                                        <div key={`coupon-slide-${index}`} className={`carousel-item ${index === 0 ? 'active' : ''}`}>
                                            <div className="row g-2 justify-content-center px-4">
                                                {chunk.map((coupon) => (
                                                    <div key={coupon.id} className="col">
                                                        <CouponMini
                                                            number={coupon.number}
                                                            isUsed={coupon.is_used}
                                                            sweepstakeName={coupon.sweepstake_name}
                                                            date={coupon.obtained_at ? `Cobrado: ${coupon.obtained_at}` : (coupon.draw_at ? `Sorteo: ${coupon.draw_at}` : null)}
                                                            onClick={null}
                                                        />
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                                {couponChunks.length > 1 && (
                                    <>
                                        <button className="carousel-control-prev w-auto" type="button" data-bs-target="#couponsCarousel" data-bs-slide="prev">
                                            <span className="carousel-control-prev-icon" aria-hidden="true" style={{ filter: 'invert(1)' }}></span>
                                            <span className="visually-hidden">Anterior</span>
                                        </button>
                                        <button className="carousel-control-next w-auto" type="button" data-bs-target="#couponsCarousel" data-bs-slide="next">
                                            <span className="carousel-control-next-icon" aria-hidden="true" style={{ filter: 'invert(1)' }}></span>
                                            <span className="visually-hidden">Siguiente</span>
                                        </button>
                                    </>
                                )}
                            </div>
                        </section>
                    )}

                    {/* <section className="home-panel d-flex flex-column gap-3">
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
                    </section> */}
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
                    onLogout={handleCustomerLogout}
                />

                {popupData.enabled && popupData.image && (
                    <div className="modal fade" id="homePopupModal" tabIndex="-1" aria-hidden="true" ref={popupRef}>
                        <div className="modal-dialog modal-dialog-centered">
                            <div className="modal-content bg-transparent border-0">
                                <div className="modal-header border-0 pb-0 justify-content-end">
                                    <button type="button" className="btn-close btn-close-white shadow-none bg-dark rounded-circle p-2" data-bs-dismiss="modal" aria-label="Close" style={{ zIndex: 10 }}></button>
                                </div>
                                <div className="modal-body p-0 position-relative text-center">
                                    {popupData.link ? (
                                        <a href={popupData.link} target="_blank" rel="noreferrer">
                                            <img src={popupData.image} alt="Pop-up" className="img-fluid rounded shadow" />
                                        </a>
                                    ) : (
                                        <img src={popupData.image} alt="Pop-up" className="img-fluid rounded shadow" />
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
