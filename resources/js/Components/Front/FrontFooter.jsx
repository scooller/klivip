import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faFacebookF,
    faInstagram,
    faYoutube,
} from '@fortawesome/free-brands-svg-icons';

export default function FrontFooter({ site, id = 'section-soporte' }) {
    return (
        <footer id={id} className="site-footer navbar navbar-expand-lg">
            <div class="container-fluid flex-column">
                <div className="w-100 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center py-4 gap-3">
                    <p>{site?.opening_hours ?? 'Juego Responsable'}</p>
                    <p>{site?.address ?? 'Seguridad Garantizada'}</p>
                    <p>Atencion 24/7</p>
                    <div className="social-links">
                        <span>Siguenos</span>
                        {site?.facebook_url ? (
                            <a href={site.facebook_url} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faFacebookF} />
                            </a>
                        ) : null}
                        {site?.instagram_url ? (
                            <a href={site.instagram_url} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faInstagram} />
                            </a>
                        ) : null}
                        {site?.twitter_url ? (
                            <a href={site.twitter_url} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faTwitter} />
                            </a>
                        ) : null}
                        {site?.linkedin_url ? (
                            <a href={site.linkedin_url} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faLinkedin} />
                            </a>
                        ) : null}
                        {site?.youtube_url ? (
                            <a href={site.youtube_url} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faYoutube} />
                            </a>
                        ) : null}
                    </div>
                </div>
                <hr className="w-100" />
                <ul class="nav justify-content-center py-2">
                    <li class="nav-item">
                        <a class="nav-link disabled" aria-disabled="true">Klivip &copy; 2023</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Terminos y condiciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Politica de Privacidad</a>
                    </li>
                </ul>
            </div>
        </footer>
    );
}
