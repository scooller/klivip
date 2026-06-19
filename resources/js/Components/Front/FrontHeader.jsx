import { router, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUser } from '@fortawesome/free-solid-svg-icons';

export default function FrontHeader({
    site,
    showBrand = true,
    leftContent = null,
    centerContent = null,
    rightContent = null,
    subtitle = null,
}) {
    const page = usePage();
    const customer = page.props.auth?.customer ?? null;
    const computedSubtitle = subtitle ?? (site?.slug ? `${site.slug}.klivip.test` : '');

    return (
        <header className="casino-header">
            {showBrand ? (
                <div className="brand-lockup">
                    {site?.logo ? (
                        <img src={site.logo} alt={site.name ?? 'Klivip'} className="brand-logo" />
                    ) : (
                        <span className="brand-mark">GI</span>
                    )}
                    <div>
                        <h3>{site?.name ?? 'Klivip'}</h3>
                        <p>{computedSubtitle}</p>
                    </div>
                </div>
            ) : (
                <div>{leftContent}</div>
            )}

            <div>{centerContent}</div>
            <div className="header-actions">
                <button className="btn btn-primary" onClick={() => router.visit('/usuario')}>
                    <FontAwesomeIcon icon={faUser} className="me-2" />
                    Registrarse
                </button>
                {customer ? (
                    <button className="btn btn-outline-secondary" onClick={() => router.visit('/usuario')}>
                        <FontAwesomeIcon icon={faUser} className="me-2" />
                        Ver perfil
                    </button>
                ) : (
                    <button className="btn btn-outline-secondary" onClick={() => router.visit('/usuario')}>
                        <FontAwesomeIcon icon={faUser} className="me-2" />
                        Conectarse
                    </button>
                )}
                {rightContent}
            </div>
        </header>
    );
}
