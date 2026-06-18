import { jsx, jsxs } from "react/jsx-runtime";
//#region resources/js/Components/Front/UserBenefitsCard.jsx
function normalizeCouponCode(code) {
	const digitsOnly = String(code ?? "").replace(/\D/g, "");
	if (digitsOnly.length > 0 && digitsOnly.length <= 6) return digitsOnly.padStart(6, "0");
	return String(code ?? "-");
}
function UserBenefitsCard({ activeCoupons = [], mode = "list", onCouponSelect = null, actionLabel = null, onAction = null }) {
	const isDetailMode = mode === "detail";
	return /* @__PURE__ */ jsxs("section", {
		className: `active-coupons-panel d-flex flex-column gap-3 ${isDetailMode ? "active-coupons-panel--detail" : ""}`.trim(),
		"aria-label": "Cupones activos",
		children: [
			/* @__PURE__ */ jsx("h3", { children: "CUPONES ACTIVOS" }),
			activeCoupons.length > 0 ? /* @__PURE__ */ jsx("div", {
				className: `active-coupons-list d-flex flex-column gap-3 ${isDetailMode ? "is-detail" : ""}`.trim(),
				children: activeCoupons.map((coupon) => /* @__PURE__ */ jsxs("div", {
					className: `active-coupon-card mx-auto card ${onCouponSelect ? "is-clickable" : ""}`.trim(),
					onClick: () => onCouponSelect?.(coupon),
					onKeyDown: (event) => {
						if (!onCouponSelect) return;
						if (event.key === "Enter" || event.key === " ") {
							event.preventDefault();
							onCouponSelect(coupon);
						}
					},
					role: onCouponSelect ? "button" : void 0,
					tabIndex: onCouponSelect ? 0 : void 0,
					children: [
						/* @__PURE__ */ jsxs("div", {
							className: "active-coupon-card__header card-header d-flex flex-wrap gap-2 align-items-center",
							children: [/* @__PURE__ */ jsxs("div", { children: [/* @__PURE__ */ jsx("p", {
								className: "active-coupon-card__brand card-title",
								children: coupon.site_name ?? "Sala"
							}), /* @__PURE__ */ jsx("p", {
								className: "active-coupon-card__meta card-text",
								children: "Cupon activo"
							})] }), /* @__PURE__ */ jsx("span", {
								className: "badge bg-primary",
								children: coupon.draw_label ?? coupon.type_label ?? "TOMBOLA"
							})]
						}),
						/* @__PURE__ */ jsxs("div", {
							className: "active-coupon-card__body card-body d-flex flex-column gap-3",
							children: [/* @__PURE__ */ jsx("p", {
								className: "active-coupon-card__code",
								children: normalizeCouponCode(coupon.code)
							}), /* @__PURE__ */ jsxs("div", {
								className: "active-coupon-card__tags d-flex flex-wrap gap-2 align-items-center",
								children: [/* @__PURE__ */ jsx("span", {
									className: "badge bg-secondary",
									children: coupon.type_label ?? "Tipo de sorteo"
								}), /* @__PURE__ */ jsx("span", {
									className: "badge bg-success",
									children: coupon.valid_to ?? "Vigente"
								})]
							})]
						}),
						onCouponSelect ? /* @__PURE__ */ jsx("div", {
							className: "active-coupon-card__footer card-footer d-flex flex-column gap-3",
							children: /* @__PURE__ */ jsx("button", {
								className: "btn btn-outline-secondary btn-sm w-100",
								type: "button",
								children: "Ver detalle"
							})
						}) : null
					]
				}, coupon.id))
			}) : /* @__PURE__ */ jsx("div", {
				className: "active-coupons-empty card",
				children: /* @__PURE__ */ jsx("div", {
					className: "card-body",
					children: /* @__PURE__ */ jsx("p", {
						className: "mb-0",
						children: "No tienes cupones activos por ahora."
					})
				})
			}),
			actionLabel && onAction ? /* @__PURE__ */ jsx("button", {
				type: "button",
				className: "active-coupons-action btn btn-primary w-100",
				onClick: onAction,
				children: actionLabel
			}) : null
		]
	});
}
//#endregion
export { UserBenefitsCard as t };

//# sourceMappingURL=UserBenefitsCard-DOvtvw-h.js.map