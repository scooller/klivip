import BaseCard from './primitives/BaseCard';

export default function UserWelcomeCard({ site, adminPortal = null }) {
    return (
        <BaseCard>
            <div className="welcome-copy d-flex flex-column gap-3">
                <h2>Bienvenido a {site.name}</h2>
                {site.logo && (
                    <img src={site.logo} alt={site.name ?? ''} className="w-75 img-fluid mx-auto" />
                )}
                <div dangerouslySetInnerHTML={{ __html: site.content && site.content !== '<p></p>' ? site.content : '<p>Accede o crea tu cuenta para guardar favoritos y recibir promociones.</p>' }} />
                <p className="text-dark">
                    {site.address ?? 'Sitio oficial'} · {site.opening_hours ?? 'Atencion 24/7'}
                </p>
                {adminPortal?.url && (
                    <button
                        className="btn btn-outline-primary"
                        onClick={() => {
                            window.location.href = adminPortal.url;
                        }}
                    >
                        Conectarse como administrador
                    </button>
                )}
            </div>
        </BaseCard>
    );
}
