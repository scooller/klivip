import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faCircleUser,
    faGift,
    faPenToSquare,
    faRightFromBracket,
    faTicket,
    faTrophy,
} from '@fortawesome/free-solid-svg-icons';
import { router } from '@inertiajs/react';

const NAV_ITEMS = [
    { route: '/principal', icon: faTrophy, label: 'Principal' },
    // { route: '/programacion', icon: faGift, label: 'Sorteos' },
    { route: '/usuario/cupones', icon: faTicket, label: 'Mis cupones' },
    { route: '/usuario', icon: faPenToSquare, label: 'Editar perfil' },
];

export default function ActionDrawer({
    label,
    open,
    onClose,
    children,
    placement = 'end',
    className = '',
    customer = null,
    onLogout,
    currentPath = '',
}) {
    const offcanvasClass = placement === 'start' ? 'offcanvas-start' : 'offcanvas-end';

    const navigateTo = (route) => {
        onClose();
        router.visit(route);
    };

    return (
        <>
            {open && <div className="offcanvas-backdrop fade show"></div>}
            <div
                className={`offcanvas ${offcanvasClass} ${open ? 'show' : ''} casino-drawer ${className}`.trim()}
                style={{ visibility: open ? 'visible' : 'hidden' }}
            >
                <div className="offcanvas-header">
                    <h5 className="offcanvas-title">{label}</h5>
                    <button
                        type="button"
                        className="btn-close"
                        onClick={onClose}
                        aria-label="Cerrar"
                    ></button>
                </div>
                <div className="offcanvas-body casino-drawer-content">
                    {customer && (
                        <div className="home-drawer-profile d-flex flex-column gap-3">
                            <div className="home-drawer-avatar">
                                <FontAwesomeIcon icon={faCircleUser} />
                            </div>
                            <strong>{customer?.name ?? 'Invitado'}</strong>
                            <p className="mb-0">{customer?.email ?? 'Conecta tu cuenta para participar en sorteos.'}</p>
                        </div>
                    )}

                    <nav className="home-drawer-nav d-flex flex-column gap-2" aria-label="Menu de usuario">
                        {NAV_ITEMS.map(({ route, icon, label: itemLabel }) => (
                            <button
                                key={route}
                                className="btn btn-primary text-start"
                                onClick={() => navigateTo(route)}
                            >
                                <FontAwesomeIcon icon={icon} className="me-2" />
                                {itemLabel}
                            </button>
                        ))}
                        {onLogout && (
                            <button className="btn btn-primary text-start" onClick={onLogout}>
                                <FontAwesomeIcon icon={faRightFromBracket} className="me-2" />
                                Cerrar sesion
                            </button>
                        )}
                    </nav>

                    {children}
                </div>
            </div>
        </>
    );
}
