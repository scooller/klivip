import { Space, Typography } from 'antd';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faFacebookF,
    faInstagram,
    faYoutube,
} from '@fortawesome/free-brands-svg-icons';

const { Text } = Typography;

export default function FrontFooter({ site, id = 'section-soporte' }) {
    return (
        <footer id={id} className="casino-footer">
            <Text>{site?.opening_hours ?? 'Juego Responsable'}</Text>
            <Text>{site?.address ?? 'Seguridad Garantizada'}</Text>
            <Text>Atencion 24/7</Text>
            <Space>
                <Text>Siguenos</Text>
                <FontAwesomeIcon icon={faFacebookF} />
                <FontAwesomeIcon icon={faInstagram} />
                <FontAwesomeIcon icon={faYoutube} />
            </Space>
        </footer>
    );
}
