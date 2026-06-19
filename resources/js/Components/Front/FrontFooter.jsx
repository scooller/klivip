import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faFacebookF,
    faInstagram,
    faYoutube,
} from '@fortawesome/free-brands-svg-icons';

export default function FrontFooter({ site, id = 'section-soporte' }) {
    return (
        <footer id={id} className="site-footer navbar navbar-expand-lg">
            <div class="container-fluid">
                <div className="w-100 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3">
                    <p>{site?.opening_hours ?? 'Juego Responsable'}</p>
                    <p>{site?.address ?? 'Seguridad Garantizada'}</p>
                    <p>Atencion 24/7</p>
                    <div className="social-links">
                        <span>Siguenos</span>
                        <FontAwesomeIcon icon={faFacebookF} />
                        <FontAwesomeIcon icon={faInstagram} />
                        <FontAwesomeIcon icon={faYoutube} />
                    </div>
                </div>
                <ul class="nav justify-content-center">
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
