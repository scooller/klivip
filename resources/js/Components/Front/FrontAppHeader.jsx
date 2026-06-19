import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faBars } from '@fortawesome/free-solid-svg-icons';

export default function FrontAppHeader({
    site,
    title,
    onBack,
    onOpenMenu,
    currentTime = '0:00 a.m',
    hideBack = false,
    userName = null,
    userSubtitle = null,
    userAvatarImage = null,
}) {
    const showUserBlock = hideBack && Boolean(userName);

    return (
        <nav className="navbar bg-body-tertiary front-app-header py-2">
            <div className="container-fluid d-flex flex-column">
                <div className="small text-muted w-100 mb-2">
                    {currentTime}
                </div>

                <div className="d-flex align-items-center justify-content-between w-100 gap-2">
                    <div className="d-flex align-items-center flex-shrink-0" style={{ minWidth: '72px' }}>
                        {showUserBlock ? (
                            <div
                                className="d-flex flex-wrap gap-2 align-items-center"
                                aria-label="Usuario actual"
                            >
                                <div>
                                    {userAvatarImage ? (
                                        <img
                                            src={userAvatarImage}
                                            alt={userName}
                                            className="rounded-circle"
                                            style={{ width: '40px', height: '40px', objectFit: 'cover' }}
                                        />
                                    ) : site?.logo ? (
                                        <img
                                            src={site.logo}
                                            alt={site.name ?? userName}
                                            className="rounded-circle"
                                            style={{ width: '40px', height: '40px', objectFit: 'cover' }}
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
                                    <small className="text-muted">{userSubtitle ?? 'Cuenta activa'}</small>
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
                            className="btn btn-outline-secondary btn-sm"
                            onClick={onOpenMenu}
                        >
                            <FontAwesomeIcon icon={faBars} />
                        </button>
                    </div>
                </div>
            </div>
        </nav>
    );
}
