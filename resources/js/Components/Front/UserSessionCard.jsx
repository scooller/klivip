import { Badge, Button, Card, Descriptions, Space, Typography } from 'antd';

const { Title, Text } = Typography;

export default function UserSessionCard({ customer, onLogout }) {
    return (
        <Card
            title={(
                <Space>
                    <Badge status="success" text="Sesion activa" />
                </Space>
            )}
            bordered={false}
        >
            <Descriptions column={1} bordered size="small">
                <Descriptions.Item label="Nombre">
                    <Title level={5} style={{ margin: 0 }}>
                        {customer.name}
                    </Title>
                </Descriptions.Item>
                <Descriptions.Item label="Correo">
                    <Text>{customer.email}</Text>
                </Descriptions.Item>
            </Descriptions>
            <Button danger block onClick={onLogout} style={{ marginTop: 16 }}>
                Cerrar sesion
            </Button>
        </Card>
    );
}
