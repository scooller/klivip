import { Button, Card, Space, Typography } from 'antd';

const { Title, Text } = Typography;

export default function UserWelcomeCard({ site, adminPortal = null }) {
    return (
        <Card bordered={false}>
            <Space direction="vertical" size={6}>
                <Title level={2} style={{ marginBottom: 0 }}>
                    Bienvenido a {site.name}
                </Title>
                <Text>Accede o crea tu cuenta para guardar favoritos y recibir promociones.</Text>
                <Text type="secondary">
                    {site.address ?? 'Sitio oficial'} · {site.opening_hours ?? 'Atencion 24/7'}
                </Text>
                {adminPortal?.url && (
                    <Button
                        type="default"
                        onClick={() => {
                            window.location.href = adminPortal.url;
                        }}
                    >
                        Conectarse como administrador
                    </Button>
                )}
            </Space>
        </Card>
    );
}
