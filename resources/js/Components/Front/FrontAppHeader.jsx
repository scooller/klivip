import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faBars } from '@fortawesome/free-solid-svg-icons';

export default function FrontAppHeader({
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
        <header className="front-app-header">
            <div className="front-app-header-time">{currentTime}</div>

            <div className="front-app-header-main">
                {showUserBlock ? (
                    <div className="front-app-header-user d-flex flex-wrap gap-2 align-items-center" aria-label="Usuario actual">
                        <div className="front-app-header-user-avatar">
                            {userAvatarImage ? (
                                <img src={userAvatarImage} alt={userName} className="rounded-circle" style={{width: '40px', height: '40px', objectFit: 'cover'}} />
                            ) : (
                                <div className="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style={{width: '40px', height: '40px'}}>
                                    {userName.slice(0, 1).toUpperCase()}
                                </div>
                            )}
                        </div>
                        <div className="front-app-header-user-copy">
                            <strong>{userName}</strong>
                            <small>{userSubtitle ?? 'Cuenta activa'}</small>
                        </div>
                    </div>
                ) : hideBack ? (
                    <span className="front-app-header-spacer" aria-hidden="true" />
                ) : (
                    <button type="button" className="front-app-header-back" onClick={onBack}>
                        <FontAwesomeIcon icon={faArrowLeft} />
                    </button>
                )}

                {title ? <h1>{title}</h1> : <span className="front-app-header-spacer" aria-hidden="true" />}

                <button type="button" className="btn btn-outline-secondary btn-sm front-app-header-menu" onClick={onOpenMenu}>
                    <FontAwesomeIcon icon={faBars} />
                </button>
            </div>
        </header>
    );
}
