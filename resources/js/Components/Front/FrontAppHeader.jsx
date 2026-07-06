import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faBars, faTicket, faTrophy, faCircleUser } from '@fortawesome/free-solid-svg-icons';
import { router } from '@inertiajs/react';

export default function FrontAppHeader({
    site,
    title,
    onBack,
    onOpenMenu,
    hideBack = false,
    userName = null,
    userSubtitle = null,
    userAvatarImage = null,
}) {
    const showUserBlock = hideBack && Boolean(userName);
    const navClass = !showUserBlock ? 'my-3' : '';

    return (
        <nav className="navbar bg-body-tertiary front-app-header py-0">
            <div className={`container-fluid d-flex align-items-center justify-content-between w-100 gap-2 ${navClass}`}>
                    <div className="d-flex align-items-center flex-shrink-0" style={{ minWidth: '72px' }}>
                        {showUserBlock ? (
                            <div
                                className="d-flex flex-wrap gap-2 align-items-center"
                                aria-label="Usuario actual"
                            >
                                <div>
                                    {site?.logo ? (
                                        <img
                                            src={site.logo}
                                            alt={site.name ?? userName}
                                            className="rounded-circle"
                                            style={{ width: 'auto', height: '4rem', objectFit: 'cover' }}
                                        />
                                    ) : (
                                        <div
                                            className="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white"
                                            style={{ width: '40px', height: '40px' }}
                                        >
                                            {userName?.slice(0, 1).toUpperCase()}
                                        </div>
                                    )}
                                </div>

                                <div className="d-none d-md-flex flex-column lh-sm">
                                    <strong>{userName}</strong>
                                    <small className="sub-title">{userSubtitle ?? 'Cuenta activa'}</small>
                                </div>
                            </div>
                        ) : hideBack ? (
                            <span aria-hidden="true" />
                        ) : (
                            <button
                                type="button"
                                className="btn btn-outline-secondary btn-sm"
                                onClick={onBack}
                            >
                                <FontAwesomeIcon icon={faArrowLeft} />
                            </button>
                        )}
                    </div>

                    <div className="flex-grow-1 text-center px-2">
                        {title ? <h1 className="h5 mb-0">{title}</h1> : <span aria-hidden="true" />}
                    </div>

                    <div className="d-flex justify-content-end flex-shrink-0" style={{ minWidth: '72px' }}>
                        <button
                            type="button"
                            className="btn btn-outline-secondary btn-sm d-md-none"
                            onClick={onOpenMenu}
                        >
                            <FontAwesomeIcon icon={faBars} />
                        </button>
                    <nav className="d-none d-md-flex gap-2 align-items-center">
                            <button
                                type="button"
                                className="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1"
                                title="Principal"
                                onClick={() => router.visit('/')}
                            >
                                <FontAwesomeIcon icon={faTrophy} />
                                <span className="d-none d-lg-inline">Principal</span>
                            </button>
                            <button
                                type="button"
                                className="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1"
                                title="Mis Cupones"
                                onClick={() => router.visit('/usuario/cupones')}
                            >
                                <FontAwesomeIcon icon={faTicket} />
                                <span className="d-none d-lg-inline">Cupones</span>
                            </button>
                            <button
                                type="button"
                                className="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1"
                                title="Mi Perfil"
                                onClick={() => router.visit('/usuario')}
                            >
                                <FontAwesomeIcon icon={faCircleUser} />
                                <span className="d-none d-lg-inline">Perfil</span>
                            </button>
                        </nav>
                    </div>
            </div>
        </nav>
    );
}
