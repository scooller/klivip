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
        <nav className="navbar navbar-expand-lg bg-body-tertiary casino-header">
            <div className="container-fluid">
                {showBrand ? (
                    <div className="navbar-brand d-flex align-items-center gap-3 mb-0">
                        {site?.logo ? (
                            <img
                                src={site.logo}
                                alt={site.name ?? 'Klivip'}
                                className="brand-logo"
                                style={{ maxHeight: '48px' }}
                            />
                        ) : (
                            <span className="brand-mark">GI</span>
                        )}

                        <div className="d-flex flex-column lh-sm">
                            <span className="fw-bold">{site?.name ?? 'Klivip'}</span>
                            {computedSubtitle && (
                                <small className="text-muted">{computedSubtitle}</small>
                            )}
                        </div>
                    </div>
                ) : (
                    <div className="me-3">{leftContent}</div>
                )}

                <button
                    className="navbar-toggler"
                    type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#frontHeaderNavbar"
                    aria-controls="frontHeaderNavbar"
                    aria-expanded="false"
                    aria-label="Toggle navigation"
                >
                    <span className="navbar-toggler-icon"></span>
                </button>

                <div className="collapse navbar-collapse" id="frontHeaderNavbar">
                    <div className="mx-auto">
                        {centerContent}
                    </div>

                    <div className="d-flex flex-column flex-lg-row align-items-start align-items-lg-center gap-2 ms-lg-auto mt-3 mt-lg-0">
                        <button
                            className="btn btn-primary"
                            onClick={() => router.visit('/usuario')}
                        >
                            <FontAwesomeIcon icon={faUser} className="me-2" />
                            Registrarse
                        </button>

                        {customer ? (
                            <button
                                className="btn btn-outline-secondary"
                                onClick={() => router.visit('/usuario')}
                            >
                                <FontAwesomeIcon icon={faUser} className="me-2" />
                                Ver perfil
                            </button>
                        ) : (
                            <button
                                className="btn btn-outline-secondary"
                                onClick={() => router.visit('/usuario')}
                            >
                                <FontAwesomeIcon icon={faUser} className="me-2" />
                                Conectarse
                            </button>
                        )}

                        {rightContent}
                    </div>
                </div>
            </div>
        </nav>
    );
}
