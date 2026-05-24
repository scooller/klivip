import BaseCard from './primitives/BaseCard';
import { WaButton } from './primitives/wa';

export default function UserWelcomeCard({ site, adminPortal = null }) {
    return (
        <BaseCard>
            <div className="welcome-copy wa-stack">
                <h2>Bienvenido a {site.name}</h2>
                <p>Accede o crea tu cuenta para guardar favoritos y recibir promociones.</p>
                <p className="muted-copy">
                    {site.address ?? 'Sitio oficial'} · {site.opening_hours ?? 'Atencion 24/7'}
                </p>
                {adminPortal?.url && (
                    <WaButton
                        variant="neutral"
                        onClick={() => {
                            window.location.href = adminPortal.url;
                        }}
                    >
                        Conectarse como administrador
                    </WaButton>
                )}
            </div>
        </BaseCard>
    );
}
