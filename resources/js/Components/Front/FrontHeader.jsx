import { Layout, Typography } from 'antd';

const { Header } = Layout;
const { Title, Text } = Typography;

export default function FrontHeader({
    site,
    showBrand = true,
    leftContent = null,
    centerContent = null,
    rightContent = null,
    subtitle = null,
}) {
    const computedSubtitle = subtitle ?? (site?.slug ? `${site.slug}.klivip.test` : '');

    return (
        <Header className="casino-header">
            {showBrand ? (
                <div className="brand-lockup">
                    <span className="brand-mark">GI</span>
                    <div>
                        <Title level={3}>{site?.name ?? 'Klivip'}</Title>
                        <Text>{computedSubtitle}</Text>
                    </div>
                </div>
            ) : (
                <div>{leftContent}</div>
            )}

            <div>{centerContent}</div>
            <div>{rightContent}</div>
        </Header>
    );
}
