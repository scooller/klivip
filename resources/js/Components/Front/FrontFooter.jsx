import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faFacebookF,
    faInstagram,
    faLinkedin,
    faTwitter,
    faYoutube,
    faTiktok,
    faPinterestP,
    faSnapchatGhost,
    faWhatsapp,
    faTelegram,
    faDiscord,
    faSpotify,
    faGithub,
} from '@fortawesome/free-brands-svg-icons';
import {
    faEnvelope,
    faPhone,
    faLink,
    faMapMarkerAlt,
    faClock,
    faGlobe,
} from '@fortawesome/free-solid-svg-icons';
import { usePage } from '@inertiajs/react';

const iconMap = {
    faFacebookF,
    faInstagram,
    faLinkedin,
    faTwitter,
    faYoutube,
    faTiktok,
    faPinterestP,
    faSnapchatGhost,
    faWhatsapp,
    faTelegram,
    faDiscord,
    faSpotify,
    faGithub,
    faEnvelope,
    faPhone,
    faLink,
    faMapMarkerAlt,
    faClock,
    faGlobe,
};

export default function FrontFooter({ site, id = 'section-soporte' }) {
    const { siteSetting } = usePage().props;
    const social = siteSetting?.social ?? {};
    const contact = siteSetting?.contact ?? {};

    return (
        <footer id={id} className="site-footer mt-auto">
            <div className="container-fluid flex-column">
                <div className="w-100 d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center py-2 gap-3">
                    <ul className="list-group list-group-horizontal list-group-flush border-0">
                        <li className="list-group-item border-0" dangerouslySetInnerHTML={{ __html: site?.opening_hours ?? 'Juego Responsable' }} />
                        <li className="list-group-item border-0" dangerouslySetInnerHTML={{ __html: site?.address ?? contact.address ?? 'Seguridad Garantizada' }} />
                        {contact.phone && (
                            <li className="list-group-item border-0">
                                <a href={`tel:${contact.phone}`} className="nav-link p-0">
                                    {contact.phone}
                                </a>
                            </li>
                        )}
                    </ul>
                    <div className="social-links">
                        {social.facebook && social.instagram && social.twitter && social.linkedin && social.youtube && (
                            <span className="me-2">Siguenos</span>
                        )}
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
                {site?.links?.length > 0 && (
                    <ul className="nav justify-content-center py-2">
                        {site.links.map((link) => (
                            <li key={link.url} className="nav-item">
                                <a className="nav-link" href={link.url} target="_blank" rel="noopener noreferrer">
                                    {link.icon && iconMap[link.icon] && (
                                        <FontAwesomeIcon icon={iconMap[link.icon]} className="me-2" />
                                    )}
                                    {link.label}
                                </a>
                            </li>
                        ))}
                        {/* <li><a className="nav-link" disabled tabindex="-1">{siteSetting?.site_name ?? 'Klivip'} &copy; {new Date().getFullYear()}</a></li> */}
                    </ul>
                )}
            </div>
        </footer>
    );
}
