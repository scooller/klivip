import { n as ActionDrawer, t as FrontAppHeader } from "./FrontAppHeader-DRdhF7Zp.js";
import { t as UserBenefitsCard } from "./UserBenefitsCard-DOvtvw-h.js";
import { useEffect, useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { Fragment, jsx, jsxs } from "react/jsx-runtime";
//#region resources/js/Pages/UserCouponsIndex.jsx
function UserCouponsIndex({ site, activeCoupons = [], pagination = null }) {
	const customer = usePage().props.auth?.customer ?? null;
	const [isMenuOpen, setIsMenuOpen] = useState(false);
	const [currentTime, setCurrentTime] = useState("");
	useEffect(() => {
		const updateTime = () => {
			setCurrentTime(new Intl.DateTimeFormat("es-CL", {
				hour: "numeric",
				minute: "2-digit",
				hour12: true
			}).format(/* @__PURE__ */ new Date()));
		};
		updateTime();
		const interval = setInterval(updateTime, 60 * 1e3);
		return () => clearInterval(interval);
	}, []);
	const handleLogout = () => {
		router.post("/usuario/logout", {}, {
			preserveScroll: true,
			onFinish: () => {
				setIsMenuOpen(false);
				router.visit("/");
			}
		});
	};
	return /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx(Head, { title: `Mis cupones | ${site.name}` }), /* @__PURE__ */ jsxs("div", {
		className: "home-main-page user-coupons-page",
		children: [
			/* @__PURE__ */ jsx(FrontAppHeader, {
				title: "Mis Cupones",
				currentTime,
				onBack: () => router.visit("/principal"),
				onOpenMenu: () => setIsMenuOpen(true)
			}),
			/* @__PURE__ */ jsxs("main", {
				className: "home-main-content container-fluid user-coupons-content",
				children: [/* @__PURE__ */ jsx(UserBenefitsCard, {
					activeCoupons,
					onCouponSelect: (coupon) => {
						if (!coupon?.id) return;
						router.visit(`/usuario/cupones/${coupon.id}`);
					}
				}), pagination ? /* @__PURE__ */ jsxs("div", {
					className: "coupons-pagination d-flex flex-wrap gap-2 align-items-center",
					role: "navigation",
					"aria-label": "Paginacion de cupones",
					children: [
						/* @__PURE__ */ jsx("button", {
							className: "btn btn-outline-secondary btn-sm",
							disabled: !pagination.prev_page_url,
							onClick: () => {
								if (!pagination.prev_page_url) return;
								router.visit(pagination.prev_page_url, { preserveScroll: true });
							},
							children: "Anterior"
						}),
						/* @__PURE__ */ jsxs("p", {
							className: "mb-0",
							children: [
								"Pagina ",
								pagination.current_page,
								" de ",
								pagination.last_page,
								" · ",
								pagination.total,
								" cupones"
							]
						}),
						/* @__PURE__ */ jsx("button", {
							className: "btn btn-primary btn-sm",
							disabled: !pagination.next_page_url,
							onClick: () => {
								if (!pagination.next_page_url) return;
								router.visit(pagination.next_page_url, { preserveScroll: true });
							},
							children: "Siguiente"
						})
					]
				}) : null]
			}),
			/* @__PURE__ */ jsx(ActionDrawer, {
				className: "home-profile-drawer",
				placement: "start",
				label: customer ? customer.name : "Menu principal",
				open: isMenuOpen,
				onClose: () => setIsMenuOpen(false),
				customer,
				onLogout: handleLogout
			})
		]
	})] });
}
//#endregion
export { UserCouponsIndex as default };

//# sourceMappingURL=UserCouponsIndex-DBRgmPJs.js.map