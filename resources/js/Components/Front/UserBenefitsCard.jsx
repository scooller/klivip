import { Card, Empty, List, Typography } from 'antd';

const { Text } = Typography;

export default function UserBenefitsCard({ benefits = [] }) {
    return (
        <Card title="Beneficios" bordered={false}>
            {benefits.length > 0 ? (
                <List
                    dataSource={benefits}
                    renderItem={(item) => (
                        <List.Item>
                            <Text>{item}</Text>
                        </List.Item>
                    )}
                />
            ) : (
                <Empty
                    image={Empty.PRESENTED_IMAGE_SIMPLE}
                    description="Sin beneficios disponibles"
                />
            )}
        </Card>
    );
}
