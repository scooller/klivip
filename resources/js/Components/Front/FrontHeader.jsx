import { router, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUser } from '@fortawesome/free-solid-svg-icons';
import { WaButton } from './primitives/wa';

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
                    <span className="brand-mark">GI</span>
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
                <WaButton variant="brand" onClick={() => router.visit('/usuario')}>
                    <FontAwesomeIcon icon={faUser} slot="start" />
                    Registrarse
                </WaButton>
                {customer ? (
                    <WaButton variant="neutral" onClick={() => router.visit('/usuario')}>
                        <FontAwesomeIcon icon={faUser} slot="start" />
                        Ver perfil
                    </WaButton>
                ) : (
                    <WaButton variant="neutral" onClick={() => router.visit('/usuario')}>
                        <FontAwesomeIcon icon={faUser} slot="start" />
                        Conectarse
                    </WaButton>
                )}
                {rightContent}
            </div>
        </header>
    );
}
