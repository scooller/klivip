import { Head, router, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faGift, faPenToSquare, faRightFromBracket, faTicket, faTrophy } from '@fortawesome/free-solid-svg-icons';
import { useEffect, useMemo, useState } from 'react';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import FrontAppHeader from '../Components/Front/FrontAppHeader';

export default function Schedule({ site, calendarDays = [] }) {
    const page = usePage();
    const customer = page.props.auth?.customer ?? null;
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [selectedDateIso, setSelectedDateIso] = useState(null);

    const weekdayHeaders = ['lu', 'ma', 'mi', 'ju', 'vi', 'sa', 'do'];

    const eventsByDate = useMemo(() => {
        return new Map(calendarDays.map((day) => [day.date_iso, day.events ?? []]));
    }, [calendarDays]);

    const referenceDate = useMemo(() => {
        const firstDay = calendarDays[0]?.date_iso;

        if (firstDay) {
            return new Date(`${firstDay}T00:00:00`);
        }

        return new Date();
    }, [calendarDays]);

    const monthLabel = useMemo(() => {
        return new Intl.DateTimeFormat('es-CL', {
            month: 'long',
            year: 'numeric',
        }).format(referenceDate);
    }, [referenceDate]);

    const formatDateIso = (date) => {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    };

    const calendarWeeks = useMemo(() => {
        const monthStart = new Date(referenceDate.getFullYear(), referenceDate.getMonth(), 1);
        const monthEnd = new Date(referenceDate.getFullYear(), referenceDate.getMonth() + 1, 0);

        const startOffset = (monthStart.getDay() + 6) % 7;
        const endOffset = 6 - ((monthEnd.getDay() + 6) % 7);

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
                hasEvents: events.length > 0,
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

    const selectedEvents = selectedDateIso ? (eventsByDate.get(selectedDateIso) ?? []) : [];

    useEffect(() => {
        const todayIso = formatDateIso(new Date());
        const dates = calendarWeeks.flat().map((day) => day.dateIso);

        if (dates.includes(todayIso)) {
            setSelectedDateIso(todayIso);

            return;
        }

        const firstCurrentMonthDay = calendarWeeks
            .flat()
            .find((day) => day.isCurrentMonth)?.dateIso;

        setSelectedDateIso(firstCurrentMonthDay ?? null);
    }, [calendarWeeks]);

    const currentTime = useMemo(() => {
        return new Intl.DateTimeFormat('es-CL', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        }).format(new Date());
    }, []);

    const handleLogout = () => {
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
            <Head title={`Programacion | ${site.name}`} />

            <div className="home-main-page">
                <FrontAppHeader
                    title="Toda la Programacion"
                    currentTime={currentTime}
                    onBack={() => router.visit('/principal')}
                    onOpenMenu={() => setIsMenuOpen(true)}
                />

                <main className="home-main-content d-flex flex-column gap-3">
                    <section className="home-panel d-flex flex-column gap-3" aria-label="Calendario de programacion">
                        <div className="home-panel-heading d-flex flex-wrap gap-2 align-items-center">
                            <h2>Calendario</h2>
                            <p>{monthLabel}</p>
                        </div>

                        <div className="schedule-table-wrap">
                            <table className="schedule-month-table" role="grid" aria-label="Calendario mensual">
                                <thead>
                                    <tr>
                                        {weekdayHeaders.map((label) => (
                                            <th key={label} scope="col">{label}</th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody>
                                    {calendarWeeks.map((week, weekIndex) => (
                                        <tr key={`week-${weekIndex}`}>
                                            {week.map((day) => (
                                                <td
                                                    key={day.dateIso}
                                                    className={[
                                                        day.isCurrentMonth ? 'is-current-month' : 'is-outside-month',
                                                        day.dateIso === selectedDateIso ? 'is-selected' : '',
                                                    ].join(' ').trim()}
                                                >
                                                    <button
                                                        type="button"
                                                        className="schedule-day-button"
                                                        onClick={() => setSelectedDateIso(day.dateIso)}
                                                        aria-label={`Ver eventos del ${day.dateIso}`}
                                                    >
                                                        <span>{day.dayNumber}</span>
                                                        {day.hasEvents ? <i aria-hidden="true" /> : null}
                                                    </button>
                                                </td>
                                            ))}
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <div className="schedule-events-panel d-flex flex-column gap-3" aria-live="polite">
                            <h3>
                                Eventos del dia{' '}
                                {selectedDateIso ? (
                                    new Intl.DateTimeFormat('es-CL', {
                                        weekday: 'long',
                                        day: '2-digit',
                                        month: 'long',
                                    }).format(new Date(`${selectedDateIso}T00:00:00`))
                                ) : null}
                            </h3>

                            {selectedEvents.length > 0 ? (
                                <ul className="schedule-events-list d-flex flex-column gap-3">
                                    {selectedEvents.map((event, index) => (
                                        <li key={`${selectedDateIso}-${event.title}-${index}`}>
                                            <div className="schedule-event-head d-flex flex-wrap gap-2 align-items-center">
                                                <strong>{event.title}</strong>
                                                {event.offer_label ? <span className="badge bg-secondary">{event.offer_label}</span> : null}
                                            </div>
                                            <p>{event.description ?? 'Evento especial'}</p>
                                            <span className="badge bg-success">{event.schedule_label ?? 'Programado'}</span>
                                        </li>
                                    ))}
                                </ul>
                            ) : (
                                <p className="schedule-day-empty">Sin eventos programados</p>
                            )}
                        </div>
                    </section>
                </main>

                <ActionDrawer
                    className="home-profile-drawer"
                    placement="start"
                    label={customer ? customer.name : 'Menu principal'}
                    open={isMenuOpen}
                    onClose={() => setIsMenuOpen(false)}
                >
                    <div className="home-drawer-profile d-flex flex-column gap-3">
                        <strong>{customer?.name ?? 'Invitado'}</strong>
                        <p className="mb-0">{customer?.email ?? 'Conecta tu cuenta para participar en sorteos.'}</p>
                    </div>

                    <nav className="home-drawer-nav d-flex flex-column gap-2" aria-label="Menu de usuario">
                        <button className="btn btn-link text-start" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/principal');
                        }}>
                            <FontAwesomeIcon icon={faTrophy} className="me-2" />
                            Principal
                        </button>
                        <button className="btn btn-link text-start" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/programacion');
                        }}>
                            <FontAwesomeIcon icon={faGift} className="me-2" />
                            Sorteos
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
                            router.visit('/usuario');
                        }}>
                            <FontAwesomeIcon icon={faPenToSquare} className="me-2" />
                            Editar perfil
                        </button>
                        <button className="btn btn-link text-start" onClick={handleLogout}>
                            <FontAwesomeIcon icon={faRightFromBracket} className="me-2" />
                            Cerrar sesion
                        </button>
                    </nav>
                </ActionDrawer>
            </div>
        </>
    );
}
