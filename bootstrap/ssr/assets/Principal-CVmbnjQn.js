import { n as ActionDrawer, t as FrontAppHeader } from "./FrontAppHeader-DRdhF7Zp.js";
import { useEffect, useMemo, useRef, useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import "@fortawesome/free-solid-svg-icons";
import { faFacebookF, faInstagram, faYoutube } from "@fortawesome/free-brands-svg-icons";
//#region resources/js/Components/Front/FrontFooter.jsx
function FrontFooter({ site, id = "section-soporte" }) {
	return /* @__PURE__ */ jsxs("footer", {
		id,
		className: "casino-footer",
		children: [
			/* @__PURE__ */ jsx("p", { children: site?.opening_hours ?? "Juego Responsable" }),
			/* @__PURE__ */ jsx("p", { children: site?.address ?? "Seguridad Garantizada" }),
			/* @__PURE__ */ jsx("p", { children: "Atencion 24/7" }),
			/* @__PURE__ */ jsxs("div", {
				className: "social-links",
				children: [
					/* @__PURE__ */ jsx("span", { children: "Siguenos" }),
					/* @__PURE__ */ jsx(FontAwesomeIcon, { icon: faFacebookF }),
					/* @__PURE__ */ jsx(FontAwesomeIcon, { icon: faInstagram }),
					/* @__PURE__ */ jsx(FontAwesomeIcon, { icon: faYoutube })
				]
			})
		]
	});
}
//#endregion
//#region resources/js/Pages/Principal.jsx
function Principal({ site, promotions = [], games = [], banners = [] }) {
	const page = usePage();
	const sharedSite = page.props.site ?? site;
	const pagePromotions = page.props.promotions ?? promotions;
	const pageGames = page.props.games ?? games;
	const pageBanners = page.props.banners ?? banners;
	const customer = page.props.auth?.customer ?? null;
	const [isMenuOpen, setIsMenuOpen] = useState(false);
	const [gameSlidesPerPage, setGameSlidesPerPage] = useState(3);
	const carouselRefs = useRef([]);
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
		window.addEventListener("resize", resolveSlidesPerPage);
		return () => {
			window.removeEventListener("resize", resolveSlidesPerPage);
		};
	}, []);
	useEffect(() => {
		import("bootstrap/dist/js/bootstrap.bundle.js").then(({ Carousel }) => {
			carouselRefs.current.forEach((el) => {
				if (el) new Carousel(el, {
					ride: "carousel",
					interval: 5e3
				});
			});
		});
	}, []);
	const [currentTime, setCurrentTime] = useState("");
	const [drawDate, setDrawDate] = useState("");
	useEffect(() => {
		const updateTimes = () => {
			setCurrentTime(new Intl.DateTimeFormat("es-CL", {
				hour: "numeric",
				minute: "2-digit",
				hour12: true
			}).format(/* @__PURE__ */ new Date()));
			setDrawDate(new Intl.DateTimeFormat("es-CL", {
				day: "2-digit",
				month: "2-digit",
				year: "numeric"
			}).format(/* @__PURE__ */ new Date()));
		};
		updateTimes();
		const interval = setInterval(updateTimes, 60 * 1e3);
		return () => clearInterval(interval);
	}, []);
	const featuredSlides = useMemo(() => {
		if (pagePromotions.length > 0) return pagePromotions.slice(0, 5).map((promotion, index) => ({
			id: `promo-${index}`,
			badge: promotion.schedule_label ?? "En vivo",
			title: promotion.offer_label ?? promotion.title,
			description: promotion.description ?? "Premios y entretenimiento para hoy."
		}));
		if (pageGames.length > 0) return pageGames.slice(0, 5).map((game, index) => ({
			id: `game-${index}`,
			badge: game.is_featured ? "Destacado" : "Juego",
			title: game.title,
			description: game.description ?? "Disponible para jugar hoy."
		}));
		return [{
			id: "fallback-slide",
			badge: "Bienvenido",
			title: "2 Plataformas de Doble Diversion",
			description: "Conecta con promociones y sorteos exclusivos desde tu cuenta."
		}];
	}, [pageGames, pagePromotions]);
	const bannerHome = useMemo(() => {
		if (pageBanners.length > 0 && pageBanners.some((banner) => banner.section === "home")) return pageBanners.filter((banner) => banner.section === "home").map((banner, index) => ({
			id: banner.id ?? `banner-${index + 1}`,
			src: banner.image_url,
			alt: banner.title ?? `Banner ${index + 1}`,
			href: banner.target_url
		}));
		return [];
	}, [pageBanners]);
	const bannersEvents = useMemo(() => {
		if (pageBanners.length > 0 && pageBanners.some((banner) => banner.section === "events")) return pageBanners.filter((banner) => banner.section === "events").map((banner, index) => ({
			id: banner.id ?? `banner-${index + 1}`,
			src: banner.image_url,
			alt: banner.title ?? `Banner ${index + 1}`,
			href: banner.target_url
		}));
		return [];
	}, [pageBanners]);
	const bannersGames = useMemo(() => {
		if (pageBanners.length > 0 && pageBanners.some((banner) => banner.section === "games")) return pageBanners.filter((banner) => banner.section === "games").map((banner, index) => ({
			id: banner.id ?? `banner-${index + 1}`,
			src: banner.image_url,
			alt: banner.title ?? `Banner ${index + 1}`,
			href: banner.target_url
		}));
		return [];
	}, [pageBanners]);
	useMemo(() => {
		return featuredSlides.slice(0, 3).map((slide, index) => ({
			id: slide.id,
			title: `Cupon ${String(index + 125).padStart(6, "0")}`,
			subtitle: slide.badge,
			note: slide.title
		}));
	}, [featuredSlides]);
	useMemo(() => {
		return [
			"Miercoles",
			"Jueves",
			"Sabado"
		].map((label, index) => ({
			id: `day-${index}`,
			label,
			title: featuredSlides[index]?.title ?? "Gran premio",
			description: featuredSlides[index]?.badge ?? "Evento especial"
		}));
	}, [featuredSlides]);
	const handleCustomerLogout = () => {
		router.post("/usuario/logout", {}, {
			preserveScroll: true,
			onFinish: () => {
				setIsMenuOpen(false);
				router.visit("/");
			}
		});
	};
	return /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx(Head, { title: `${sharedSite.name} | Principal` }), /* @__PURE__ */ jsxs("div", {
		className: "home-main-page",
		children: [
			/* @__PURE__ */ jsx(FrontAppHeader, {
				title: null,
				currentTime,
				hideBack: true,
				userName: customer?.name ?? "Invitado",
				userSubtitle: site?.name ?? "Sala",
				onOpenMenu: () => setIsMenuOpen(true)
			}),
			/* @__PURE__ */ jsxs("main", {
				className: "home-main-content container-fluid",
				children: [
					/* @__PURE__ */ jsx("section", {
						className: "banner-slide home-banner",
						"aria-label": "Banner principal",
						children: /* @__PURE__ */ jsxs("div", {
							id: "bannerHome",
							className: "carousel slide",
							ref: (el) => {
								carouselRefs.current[0] = el;
							},
							children: [
								/* @__PURE__ */ jsx("div", {
									className: "carousel-inner",
									children: bannerHome.map((banner, index) => /* @__PURE__ */ jsx("div", {
										className: `carousel-item ${index === 0 ? "active" : ""} home-banner-item`,
										children: banner.href ? /* @__PURE__ */ jsx("a", {
											href: banner.href,
											target: "_blank",
											rel: "noreferrer",
											className: "home-banner-link",
											children: /* @__PURE__ */ jsx("img", {
												src: banner.src,
												alt: banner.alt,
												className: "home-banner-image"
											})
										}) : /* @__PURE__ */ jsx("img", {
											src: banner.src,
											alt: banner.alt,
											className: "home-banner-image"
										})
									}, banner.id))
								}),
								/* @__PURE__ */ jsxs("button", {
									className: "carousel-control-prev",
									type: "button",
									"data-bs-target": "#bannerHome",
									"data-bs-slide": "prev",
									children: [/* @__PURE__ */ jsx("span", {
										className: "carousel-control-prev-icon",
										"aria-hidden": "true"
									}), /* @__PURE__ */ jsx("span", {
										className: "visually-hidden",
										children: "Anterior"
									})]
								}),
								/* @__PURE__ */ jsxs("button", {
									className: "carousel-control-next",
									type: "button",
									"data-bs-target": "#bannerHome",
									"data-bs-slide": "next",
									children: [/* @__PURE__ */ jsx("span", {
										className: "carousel-control-next-icon",
										"aria-hidden": "true"
									}), /* @__PURE__ */ jsx("span", {
										className: "visually-hidden",
										children: "Siguiente"
									})]
								})
							]
						})
					}),
					/* @__PURE__ */ jsx("section", {
						className: "banner-slide event-banner",
						"aria-label": "Banner evento",
						children: /* @__PURE__ */ jsx("div", {
							id: "bannerEvents",
							className: "carousel slide",
							children: /* @__PURE__ */ jsx("div", {
								className: "carousel-inner",
								children: bannersEvents.map((banner, index) => /* @__PURE__ */ jsx("div", {
									className: `carousel-item ${index === 0 ? "active" : ""} home-banner-item`,
									children: banner.href ? /* @__PURE__ */ jsx("a", {
										href: banner.href,
										target: "_blank",
										rel: "noreferrer",
										className: "home-banner-link",
										children: /* @__PURE__ */ jsx("img", {
											src: banner.src,
											alt: banner.alt,
											className: "home-banner-image"
										})
									}) : /* @__PURE__ */ jsx("img", {
										src: banner.src,
										alt: banner.alt,
										className: "home-banner-image"
									})
								}, banner.id))
							})
						})
					}),
					/* @__PURE__ */ jsx("section", {
						className: "banner-slide games-banner",
						"aria-label": "Banner juegos",
						children: /* @__PURE__ */ jsxs("div", {
							id: "bannerGames",
							className: "carousel slide",
							ref: (el) => {
								carouselRefs.current[1] = el;
							},
							children: [
								/* @__PURE__ */ jsx("div", {
									className: "carousel-inner",
									children: bannersGames.map((banner, index) => /* @__PURE__ */ jsx("div", {
										className: `carousel-item ${index === 0 ? "active" : ""} home-banner-item`,
										children: banner.href ? /* @__PURE__ */ jsx("a", {
											href: banner.href,
											target: "_blank",
											rel: "noreferrer",
											className: "home-banner-link",
											children: /* @__PURE__ */ jsx("img", {
												src: banner.src,
												alt: banner.alt,
												className: "home-banner-image"
											})
										}) : /* @__PURE__ */ jsx("img", {
											src: banner.src,
											alt: banner.alt,
											className: "home-banner-image"
										})
									}, banner.id))
								}),
								/* @__PURE__ */ jsxs("button", {
									className: "carousel-control-prev",
									type: "button",
									"data-bs-target": "#bannerGames",
									"data-bs-slide": "prev",
									children: [/* @__PURE__ */ jsx("span", {
										className: "carousel-control-prev-icon",
										"aria-hidden": "true"
									}), /* @__PURE__ */ jsx("span", {
										className: "visually-hidden",
										children: "Anterior"
									})]
								}),
								/* @__PURE__ */ jsxs("button", {
									className: "carousel-control-next",
									type: "button",
									"data-bs-target": "#bannerGames",
									"data-bs-slide": "next",
									children: [/* @__PURE__ */ jsx("span", {
										className: "carousel-control-next-icon",
										"aria-hidden": "true"
									}), /* @__PURE__ */ jsx("span", {
										className: "visually-hidden",
										children: "Siguiente"
									})]
								})
							]
						})
					}),
					/* @__PURE__ */ jsx(FrontFooter, { site: sharedSite })
				]
			}),
			/* @__PURE__ */ jsx(ActionDrawer, {
				className: "home-profile-drawer",
				placement: "start",
				label: customer ? customer.name : "Menu principal",
				open: isMenuOpen,
				onClose: () => setIsMenuOpen(false),
				customer,
				onLogout: handleCustomerLogout
			})
		]
	})] });
}
//#endregion
export { Principal as default };

//# sourceMappingURL=Principal-CVmbnjQn.js.map