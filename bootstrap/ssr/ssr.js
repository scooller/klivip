import "react";
import { createInertiaApp } from "@inertiajs/react";
import createServer from "@inertiajs/react/server";
import { renderToString } from "react-dom/server";
import { jsx } from "react/jsx-runtime";
//#region node_modules/laravel-vite-plugin/inertia-helpers/index.js
async function resolvePageComponent(path, pages) {
	for (const p of Array.isArray(path) ? path : [path]) {
		const page = pages[p];
		if (typeof page === "undefined") continue;
		return typeof page === "function" ? page() : page;
	}
	throw new Error(`Page not found: ${path}`);
}
//#endregion
//#region resources/js/ssr.jsx
var renderPage = (page) => createInertiaApp({
	page,
	render: renderToString,
	resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, /* #__PURE__ */ Object.assign({
		"./Pages/Principal.jsx": () => import("./assets/Principal-DM9vrAKq.js"),
		"./Pages/Schedule.jsx": () => import("./assets/Schedule-u1ZOqjLX.js"),
		"./Pages/User.jsx": () => import("./assets/User-C0YmJg-D.js"),
		"./Pages/UserCouponShow.jsx": () => import("./assets/UserCouponShow-DXBw4bdl.js"),
		"./Pages/UserCouponsIndex.jsx": () => import("./assets/UserCouponsIndex-DBRgmPJs.js")
	})),
	setup: ({ App, props }) => /* @__PURE__ */ jsx(App, { ...props })
});
createServer(renderPage);
//#endregion
export { renderPage as default };

//# sourceMappingURL=ssr.js.map