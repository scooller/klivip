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
                <ul class="nav justify-content-center">
                    <li class="nav-item">
                        <p>{site?.opening_hours ?? 'Juego Responsable'}</p>
                    </li>
                    <li class="nav-item">
                        <p>{site?.address ?? 'Seguridad Garantizada'}</p>
                    </li>
                    <li class="nav-item">
                        <p>Atencion 24/7</p>
                    </li>
                    <li class="nav-item">
                        <div className="social-links">
                            <span>Siguenos</span>
                            <FontAwesomeIcon icon={faFacebookF} />
                            <FontAwesomeIcon icon={faInstagram} />
                            <FontAwesomeIcon icon={faYoutube} />
                        </div>
                    </li>
                </ul>
            </div>
        </footer>
    );
}
