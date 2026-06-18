import { n as ActionDrawer, t as FrontAppHeader } from "./FrontAppHeader-DRdhF7Zp.js";
import { useEffect, useMemo, useState } from "react";
import { Head, router, usePage } from "@inertiajs/react";
import { Fragment, jsx, jsxs } from "react/jsx-runtime";
//#region resources/js/Pages/Schedule.jsx
function Schedule({ site, calendarDays = [] }) {
	const customer = usePage().props.auth?.customer ?? null;
	const [isMenuOpen, setIsMenuOpen] = useState(false);
	const [selectedDateIso, setSelectedDateIso] = useState(null);
	const weekdayHeaders = [
		"lu",
		"ma",
		"mi",
		"ju",
		"vi",
		"sa",
		"do"
	];
	const eventsByDate = useMemo(() => {
		return new Map(calendarDays.map((day) => [day.date_iso, day.events ?? []]));
	}, [calendarDays]);
	const referenceDate = useMemo(() => {
		const firstDay = calendarDays[0]?.date_iso;
		if (firstDay) return /* @__PURE__ */ new Date(`${firstDay}T00:00:00`);
		return /* @__PURE__ */ new Date();
	}, [calendarDays]);
	const monthLabel = useMemo(() => {
		return new Intl.DateTimeFormat("es-CL", {
			month: "long",
			year: "numeric"
		}).format(referenceDate);
	}, [referenceDate]);
	const formatDateIso = (date) => {
		return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`;
	};
	const calendarWeeks = useMemo(() => {
		const monthStart = new Date(referenceDate.getFullYear(), referenceDate.getMonth(), 1);
		const monthEnd = new Date(referenceDate.getFullYear(), referenceDate.getMonth() + 1, 0);
		const startOffset = (monthStart.getDay() + 6) % 7;
		const endOffset = 6 - (monthEnd.getDay() + 6) % 7;
		const calendarStart = new Date(monthStart);
		calendarStart.setDate(monthStart.getDate() - startOffset);
		const calendarEnd = new Date(monthEnd);
		calendarEnd.setDate(monthEnd.getDate() + endOffset);
		const weeks = [];
		let week = [];
		let cursor = new Date(calendarStart);
		while (cursor <= calendarEnd) {
			const iso = formatDateIso(cursor);
			const events = eventsByDate.get(iso) ?? [];
			week.push({
				dateIso: iso,
				dayNumber: cursor.getDate(),
				isCurrentMonth: cursor.getMonth() === referenceDate.getMonth(),
				hasEvents: events.length > 0
			});
			if (week.length === 7) {
				weeks.push(week);
				week = [];
			}
			cursor = new Date(cursor);
			cursor.setDate(cursor.getDate() + 1);
		}
		return weeks;
	}, [eventsByDate, referenceDate]);
	const selectedEvents = selectedDateIso ? eventsByDate.get(selectedDateIso) ?? [] : [];
	useEffect(() => {
		const todayIso = formatDateIso(/* @__PURE__ */ new Date());
		if (calendarWeeks.flat().map((day) => day.dateIso).includes(todayIso)) {
			setSelectedDateIso(todayIso);
			return;
		}
		const firstCurrentMonthDay = calendarWeeks.flat().find((day) => day.isCurrentMonth)?.dateIso;
		setSelectedDateIso(firstCurrentMonthDay ?? null);
	}, [calendarWeeks]);
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
	return /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx(Head, { title: `Programacion | ${site.name}` }), /* @__PURE__ */ jsxs("div", {
		className: "home-main-page",
		children: [
			/* @__PURE__ */ jsx(FrontAppHeader, {
				title: "Toda la Programacion",
				currentTime,
				onBack: () => router.visit("/principal"),
				onOpenMenu: () => setIsMenuOpen(true)
			}),
			/* @__PURE__ */ jsx("main", {
				className: "home-main-content container-fluid",
				children: /* @__PURE__ */ jsxs("section", {
					className: "home-panel d-flex flex-column gap-3",
					"aria-label": "Calendario de programacion",
					children: [
						/* @__PURE__ */ jsxs("div", {
							className: "home-panel-heading d-flex flex-wrap gap-2 align-items-center",
							children: [/* @__PURE__ */ jsx("h2", { children: "Calendario" }), /* @__PURE__ */ jsx("p", { children: monthLabel })]
						}),
						/* @__PURE__ */ jsx("div", {
							className: "schedule-table-wrap",
							children: /* @__PURE__ */ jsxs("table", {
								className: "schedule-month-table",
								role: "grid",
								"aria-label": "Calendario mensual",
								children: [/* @__PURE__ */ jsx("thead", { children: /* @__PURE__ */ jsx("tr", { children: weekdayHeaders.map((label) => /* @__PURE__ */ jsx("th", {
									scope: "col",
									children: label
								}, label)) }) }), /* @__PURE__ */ jsx("tbody", { children: calendarWeeks.map((week, weekIndex) => /* @__PURE__ */ jsx("tr", { children: week.map((day) => /* @__PURE__ */ jsx("td", {
									className: [day.isCurrentMonth ? "is-current-month" : "is-outside-month", day.dateIso === selectedDateIso ? "is-selected" : ""].join(" ").trim(),
									children: /* @__PURE__ */ jsxs("button", {
										type: "button",
										className: "schedule-day-button",
										onClick: () => setSelectedDateIso(day.dateIso),
										"aria-label": `Ver eventos del ${day.dateIso}`,
										children: [/* @__PURE__ */ jsx("span", { children: day.dayNumber }), day.hasEvents ? /* @__PURE__ */ jsx("i", { "aria-hidden": "true" }) : null]
									})
								}, day.dateIso)) }, `week-${weekIndex}`)) })]
							})
						}),
						/* @__PURE__ */ jsxs("div", {
							className: "schedule-events-panel d-flex flex-column gap-3",
							"aria-live": "polite",
							children: [/* @__PURE__ */ jsxs("h3", { children: [
								"Eventos del dia",
								" ",
								selectedDateIso ? new Intl.DateTimeFormat("es-CL", {
									weekday: "long",
									day: "2-digit",
									month: "long"
								}).format(/* @__PURE__ */ new Date(`${selectedDateIso}T00:00:00`)) : null
							] }), selectedEvents.length > 0 ? /* @__PURE__ */ jsx("ul", {
								className: "schedule-events-list d-flex flex-column gap-3",
								children: selectedEvents.map((event, index) => /* @__PURE__ */ jsxs("li", { children: [
									/* @__PURE__ */ jsxs("div", {
										className: "schedule-event-head d-flex flex-wrap gap-2 align-items-center",
										children: [/* @__PURE__ */ jsx("strong", { children: event.title }), event.offer_label ? /* @__PURE__ */ jsx("span", {
											className: "badge bg-secondary",
											children: event.offer_label
										}) : null]
									}),
									/* @__PURE__ */ jsx("p", { children: event.description ?? "Evento especial" }),
									/* @__PURE__ */ jsx("span", {
										className: "badge bg-success",
										children: event.schedule_label ?? "Programado"
									})
								] }, `${selectedDateIso}-${event.title}-${index}`))
							}) : /* @__PURE__ */ jsx("p", {
								className: "schedule-day-empty",
								children: "Sin eventos programados"
							})]
						})
					]
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
export { Schedule as default };

//# sourceMappingURL=Schedule-u1ZOqjLX.js.map