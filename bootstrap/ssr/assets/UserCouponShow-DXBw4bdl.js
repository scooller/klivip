import { n as ActionDrawer, t as FrontAppHeader } from "./FrontAppHeader-DRdhF7Zp.js";
import { t as UserBenefitsCard } from "./UserBenefitsCard-DOvtvw-h.js";
import { useEffect, useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { Fragment, jsx, jsxs } from "react/jsx-runtime";
//#region resources/js/Pages/UserCouponShow.jsx
function UserCouponShow({ site, coupon }) {
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
	return /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx(Head, { title: `Cupon ${coupon?.code ?? ""} | ${site.name}` }), /* @__PURE__ */ jsxs("div", {
		className: "home-main-page user-coupons-page",
		children: [
			/* @__PURE__ */ jsx(FrontAppHeader, {
				title: "Mis Cupones",
				currentTime,
				onBack: () => router.visit("/usuario/cupones"),
				onOpenMenu: () => setIsMenuOpen(true)
			}),
			/* @__PURE__ */ jsx("main", {
				className: "home-main-content container-fluid user-coupons-content",
				children: /* @__PURE__ */ jsx(UserBenefitsCard, {
					mode: "detail",
					activeCoupons: coupon ? [coupon] : [],
					actionLabel: "Ver todos los cupones",
					onAction: () => router.visit("/usuario/cupones")
				})
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
export { UserCouponShow as default };

//# sourceMappingURL=UserCouponShow-DXBw4bdl.js.map