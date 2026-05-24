import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faBars } from '@fortawesome/free-solid-svg-icons';
import { WaAvatar, WaButton } from './primitives/wa';

export default function FrontAppHeader({
    title,
    onBack,
    onOpenMenu,
    currentTime = '7:49 a.m',
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
                    <div className="front-app-header-user wa-cluster" aria-label="Usuario actual">
                        <WaAvatar
                            className="front-app-header-user-avatar"
                            image={userAvatarImage ?? undefined}
                            label={userName}
                            initials={userName.slice(0, 1).toUpperCase()}
                        />
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

                <WaButton className="front-app-header-menu" variant="neutral" size="small" onClick={onOpenMenu}>
                    <FontAwesomeIcon icon={faBars} />
                </WaButton>
            </div>
        </header>
    );
}
