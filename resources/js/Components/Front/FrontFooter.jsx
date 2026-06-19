import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faFacebookF,
    faInstagram,
    faYoutube,
} from '@fortawesome/free-brands-svg-icons';
import { usePage } from '@inertiajs/react';

export default function FrontFooter({ site, id = 'section-soporte' }) {
    const { siteSetting } = usePage().props;
    const social = siteSetting?.social ?? {};
    const contact = siteSetting?.contact ?? {};

    return (
        <footer id={id} className="site-footer navbar navbar-expand-lg">
            <div class="container-fluid flex-column">
                <div className="w-100 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center py-4 gap-3">
                    <p>{site?.opening_hours ?? 'Juego Responsable'}</p>
                    <p>{site?.address ?? contact.address ?? 'Seguridad Garantizada'}</p>
                    <p>{contact.phone ?? 'Atencion 24/7'}</p>
                    <div className="social-links">
                        <span>Siguenos</span>
                        {social.facebook ? (
                            <a href={social.facebook} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faFacebookF} />
                            </a>
                        ) : null}
                        {social.instagram ? (
                            <a href={social.instagram} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faInstagram} />
                            </a>
                        ) : null}
                        {social.twitter ? (
                            <a href={social.twitter} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faTwitter} />
                            </a>
                        ) : null}
                        {social.linkedin ? (
                            <a href={social.linkedin} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faLinkedin} />
                            </a>
                        ) : null}
                        {social.youtube ? (
                            <a href={social.youtube} className="nav-link" target="_blank" rel="noopener noreferrer">
                                <FontAwesomeIcon icon={faYoutube} />
                            </a>
                        ) : null}
                    </div>
                </div>
                <hr className="w-100" />
                <p className="text-center mb-0">{siteSetting?.site_name ?? 'Klivip'} &copy; {new Date().getFullYear()}</p>
                {/* <ul class="nav justify-content-center py-2">
                    <li class="nav-item">
                        <a class="nav-link disabled" aria-disabled="true">{siteSetting?.site_name ?? 'Klivip'} &copy; {new Date().getFullYear()}</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Terminos y condiciones</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Politica de Privacidad</a>
                    </li>
                </ul> */}
            </div>
        </footer>
    );
}
