import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faFacebookF,
    faInstagram,
    faYoutube,
} from '@fortawesome/free-brands-svg-icons';

export default function FrontFooter({ site, id = 'section-soporte' }) {
    return (
        <footer id={id} className="casino-footer">
            <p>{site?.opening_hours ?? 'Juego Responsable'}</p>
            <p>{site?.address ?? 'Seguridad Garantizada'}</p>
            <p>Atencion 24/7</p>
            <div className="social-links">
                <span>Siguenos</span>
                <FontAwesomeIcon icon={faFacebookF} />
                <FontAwesomeIcon icon={faInstagram} />
                <FontAwesomeIcon icon={faYoutube} />
            </div>
        </footer>
    );
}
