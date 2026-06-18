import { router } from "@inertiajs/react";
import { Fragment, jsx, jsxs } from "react/jsx-runtime";
import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import { faArrowLeft, faBars, faCircleUser, faPenToSquare, faRightFromBracket, faTicket, faTrophy } from "@fortawesome/free-solid-svg-icons";
//#region resources/js/Components/Front/Sections/ActionDrawer.jsx
var NAV_ITEMS = [
	{
		route: "/principal",
		icon: faTrophy,
		label: "Principal"
	},
	{
		route: "/usuario/cupones",
		icon: faTicket,
		label: "Mis cupones"
	},
	{
		route: "/usuario",
		icon: faPenToSquare,
		label: "Editar perfil"
	}
];
function ActionDrawer({ label, open, onClose, children, placement = "end", className = "", customer = null, onLogout, currentPath = "" }) {
	const offcanvasClass = placement === "start" ? "offcanvas-start" : "offcanvas-end";
	const navigateTo = (route) => {
		onClose();
		router.visit(route);
	};
	return /* @__PURE__ */ jsxs(Fragment, { children: [open && /* @__PURE__ */ jsx("div", { className: "offcanvas-backdrop fade show" }), /* @__PURE__ */ jsxs("div", {
		className: `offcanvas ${offcanvasClass} ${open ? "show" : ""} casino-drawer ${className}`.trim(),
		style: { visibility: open ? "visible" : "hidden" },
		children: [/* @__PURE__ */ jsxs("div", {
			className: "offcanvas-header",
			children: [/* @__PURE__ */ jsx("h5", {
				className: "offcanvas-title",
				children: label
			}), /* @__PURE__ */ jsx("button", {
				type: "button",
				className: "btn-close",
				onClick: onClose,
				"aria-label": "Cerrar"
			})]
		}), /* @__PURE__ */ jsxs("div", {
			className: "offcanvas-body casino-drawer-content",
			children: [
				customer && /* @__PURE__ */ jsxs("div", {
					className: "home-drawer-profile d-flex flex-column gap-3",
					children: [
						/* @__PURE__ */ jsx("div", {
							className: "home-drawer-avatar",
							children: /* @__PURE__ */ jsx(FontAwesomeIcon, { icon: faCircleUser })
						}),
						/* @__PURE__ */ jsx("strong", { children: customer?.name ?? "Invitado" }),
						/* @__PURE__ */ jsx("p", {
							className: "mb-0",
							children: customer?.email ?? "Conecta tu cuenta para participar en sorteos."
						})
					]
				}),
				/* @__PURE__ */ jsxs("nav", {
					className: "home-drawer-nav d-flex flex-column gap-2",
					"aria-label": "Menu de usuario",
					children: [NAV_ITEMS.map(({ route, icon, label: itemLabel }) => /* @__PURE__ */ jsxs("button", {
						className: "btn btn-primary text-start",
						onClick: () => navigateTo(route),
						children: [/* @__PURE__ */ jsx(FontAwesomeIcon, {
							icon,
							className: "me-2"
						}), itemLabel]
					}, route)), onLogout && /* @__PURE__ */ jsxs("button", {
						className: "btn btn-primary text-start",
						onClick: onLogout,
						children: [/* @__PURE__ */ jsx(FontAwesomeIcon, {
							icon: faRightFromBracket,
							className: "me-2"
						}), "Cerrar sesion"]
					})]
				}),
				children
			]
		})]
	})] });
}
//#endregion
//#region resources/js/Components/Front/FrontAppHeader.jsx
function FrontAppHeader({ title, onBack, onOpenMenu, currentTime = "0:00 a.m", hideBack = false, userName = null, userSubtitle = null, userAvatarImage = null }) {
	return /* @__PURE__ */ jsxs("header", {
		className: "front-app-header",
		children: [/* @__PURE__ */ jsx("div", {
			className: "front-app-header-time",
			children: currentTime
		}), /* @__PURE__ */ jsxs("div", {
			className: "front-app-header-main",
			children: [
				hideBack && Boolean(userName) ? /* @__PURE__ */ jsxs("div", {
					className: "front-app-header-user d-flex flex-wrap gap-2 align-items-center",
					"aria-label": "Usuario actual",
					children: [/* @__PURE__ */ jsx("div", {
						className: "front-app-header-user-avatar",
						children: userAvatarImage ? /* @__PURE__ */ jsx("img", {
							src: userAvatarImage,
							alt: userName,
							className: "rounded-circle",
							style: {
								width: "40px",
								height: "40px",
								objectFit: "cover"
							}
						}) : /* @__PURE__ */ jsx("div", {
							className: "rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white",
							style: {
								width: "40px",
								height: "40px"
							},
							children: userName.slice(0, 1).toUpperCase()
						})
					}), /* @__PURE__ */ jsxs("div", {
						className: "front-app-header-user-copy",
						children: [/* @__PURE__ */ jsx("strong", { children: userName }), /* @__PURE__ */ jsx("small", { children: userSubtitle ?? "Cuenta activa" })]
					})]
				}) : hideBack ? /* @__PURE__ */ jsx("span", {
					className: "front-app-header-spacer",
					"aria-hidden": "true"
				}) : /* @__PURE__ */ jsx("button", {
					type: "button",
					className: "front-app-header-back",
					onClick: onBack,
					children: /* @__PURE__ */ jsx(FontAwesomeIcon, { icon: faArrowLeft })
				}),
				title ? /* @__PURE__ */ jsx("h1", { children: title }) : /* @__PURE__ */ jsx("span", {
					className: "front-app-header-spacer",
					"aria-hidden": "true"
				}),
				/* @__PURE__ */ jsx("button", {
					type: "button",
					className: "btn btn-outline-secondary btn-sm front-app-header-menu",
					onClick: onOpenMenu,
					children: /* @__PURE__ */ jsx(FontAwesomeIcon, { icon: faBars })
				})
			]
		})]
	});
}
//#endregion
export { ActionDrawer as n, FrontAppHeader as t };

//# sourceMappingURL=FrontAppHeader-DRdhF7Zp.js.map