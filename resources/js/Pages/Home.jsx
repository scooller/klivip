import { Head, router, usePage } from '@inertiajs/react';
import {
    Anchor,
    Button,
    Carousel,
    Card,
    Col,
    ConfigProvider,
    Drawer,
    Empty,
    Layout,
    List,
    Row,
    Space,
    Tag,
    Typography,
} from 'antd';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faBars,
    faCrown,
    faDice,
    faGift,
    faHeadset,
    faMusic,
    faPizzaSlice,
    faShieldHalved,
    faStar,
    faTrophy,
    faUser,
} from '@fortawesome/free-solid-svg-icons';
import FrontFooter from '../Components/Front/FrontFooter';
import FrontHeader from '../Components/Front/FrontHeader';
import { useState } from 'react';

const { Content } = Layout;
const { Title, Text } = Typography;

const promotionIcons = [
    faStar,
    faDice,
    faGift,
    faPizzaSlice,
    faCrown,
    faMusic,
    faTrophy,
];

const vegasFeatures = [
    { icon: faTrophy, label: '+500 Juegos' },
    { icon: faShieldHalved, label: 'Seguridad Garantizada' },
    { icon: faHeadset, label: 'Soporte 24/7' },
];

export default function Home({ site, promotions = [], games = [] }) {
    const page = usePage();
    const sharedSite = page.props.site ?? site;
    const pagePromotions = page.props.promotions ?? promotions;
    const pageGames = page.props.games ?? games;
    const customer = page.props.auth?.customer ?? null;
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isProfileDrawerOpen, setIsProfileDrawerOpen] = useState(false);

    const anchorItems = [
        { key: 'inicio', href: '#section-inicio', title: 'Inicio' },
        { key: 'juegos', href: '#section-juegos', title: 'Juegos' },
        { key: 'promociones', href: '#section-promociones', title: 'Promociones' },
        { key: 'eventos', href: '#section-eventos', title: 'Eventos' },
        { key: 'sala', href: '#section-sala', title: 'Sala de Juegos' },
        { key: 'soporte', href: '#section-soporte', title: 'Soporte' },
    ];

    const scrollToPromotions = () => {
        const promotionsSection = document.getElementById('section-promociones');

        if (!promotionsSection) {
            return;
        }

        promotionsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const handleCustomerLogout = () => {
        router.post('/usuario/logout', {}, {
            preserveScroll: true,
            onFinish: () => {
                setIsProfileDrawerOpen(false);
            },
        });
    };

    const gamesSliderConfig = {
        dots: true,
        arrows: false,
        infinite: pageGames.length > 4,
        speed: 450,
        slidesToShow: 4,
        slidesToScroll: 1,
        responsive: [
            {
                breakpoint: 1200,
                settings: {
                    slidesToShow: 3,
                },
            },
            {
                breakpoint: 900,
                settings: {
                    slidesToShow: 2,
                },
            },
            {
                breakpoint: 620,
                settings: {
                    slidesToShow: 1,
                },
            },
        ],
    };

    return (
        <>
            <Head title={`${sharedSite.name} | Klivip`} />

            <ConfigProvider
                theme={{
                    token: {
                        colorPrimary: '#e0b33b',
                        borderRadius: 12,
                        fontFamily: 'Rajdhani, sans-serif',
                    },
                }}
            >
                <Layout className="casino-layout">
                    <FrontHeader
                        site={sharedSite}
                        centerContent={(
                            <Anchor
                                className="desktop-nav"
                                affix={false}
                                direction="horizontal"
                                items={anchorItems}
                                targetOffset={104}
                            />
                        )}
                        rightContent={(
                            <Space>
                                {customer ? (
                                    <Button
                                        type="primary"
                                        icon={<FontAwesomeIcon icon={faUser} />}
                                        onClick={() => setIsProfileDrawerOpen(true)}
                                    >
                                        Ver perfil
                                    </Button>
                                ) : (
                                    <Button
                                        type="primary"
                                        icon={<FontAwesomeIcon icon={faUser} />}
                                        onClick={() => router.visit('/usuario')}
                                    >
                                        Registrate
                                    </Button>
                                )}
                                <Button className="mobile-menu-button" icon={<FontAwesomeIcon icon={faBars} />} onClick={() => setIsMenuOpen(true)} />
                            </Space>
                        )}
                    />

                    <Content className="casino-content">
                        <section id="section-inicio" className="hero-section">
                            <div className="hero-copy">
                                <Text className="hero-kicker">Tu Isla de Diversion</Text>
                                <Title>PREMIOS, JUEGOS Y EXPERIENCIAS VIP</Title>
                                <Text>
                                    {sharedSite.content
                                        ? sharedSite.content.replace(/<[^>]+>/g, '').slice(0, 170)
                                        : 'Disfruta cada dia una nueva programacion pensada para ti y participa por increibles premios.'}
                                </Text>

                                <Space>
                                    <Button
                                        size="large"
                                        type="primary"
                                        icon={<FontAwesomeIcon icon={faUser} />}
                                        onClick={() => router.visit('/usuario')}
                                    >
                                        Registrate Ahora
                                    </Button>
                                    <Button size="large" onClick={scrollToPromotions}>Ver Promociones</Button>
                                </Space>
                            </div>

                            <Card className="hero-art" bordered={false}>
                                <div className="coin coin-a">$</div>
                                <div className="coin coin-b">$</div>
                                <div className="hero-glow" />
                            </Card>
                        </section>

                        <section id="section-promociones" className="program-section">
                            <Title level={2}>Programacion Diaria</Title>
                            <Text>Siempre hay algo que ganar</Text>

                            <Row gutter={[14, 14]}>
                                {pagePromotions.length > 0 ? (
                                    pagePromotions.map((promotion, index) => (
                                        <Col xs={24} sm={12} md={8} lg={24 / 7} key={`${promotion.title}-${index}`}>
                                            <Card className="program-card" bordered={false}>
                                                <Text className="day-label">{promotion.schedule_label ?? 'Hoy'}</Text>
                                                <span className="day-icon">
                                                    <FontAwesomeIcon icon={promotionIcons[index % promotionIcons.length]} />
                                                </span>
                                                <Title level={4}>{promotion.offer_label ?? promotion.title}</Title>
                                                <Text>{promotion.description ?? promotion.title}</Text>
                                            </Card>
                                        </Col>
                                    ))
                                ) : (
                                    <Col xs={24}>
                                        <Card className="program-card" bordered={false}>
                                            <Empty
                                                image={Empty.PRESENTED_IMAGE_SIMPLE}
                                                description="Sin programacion configurada"
                                            />
                                        </Card>
                                    </Col>
                                )}
                            </Row>
                        </section>

                        <section id="section-eventos" className="vegas-section">
                            <div className="vegas-logo">Vegas Nights</div>

                            <div className="vegas-copy">
                                <Text className="hero-kicker">Juega desde donde estes con</Text>
                                <Title>VEGAS NIGHTS</Title>
                                <Text>
                                    Nuestra plataforma de juego online esta disponible 24/7 para que vivas la emocion sin limites.
                                </Text>

                                <List
                                    className="feature-list"
                                    dataSource={vegasFeatures}
                                    renderItem={(feature) => (
                                        <List.Item>
                                            <Space>
                                                <FontAwesomeIcon icon={feature.icon} />
                                                <Text>{feature.label}</Text>
                                            </Space>
                                        </List.Item>
                                    )}
                                />

                                <Button size="large" type="primary">Juega Ahora En Vegas Nights</Button>
                            </div>
                        </section>

                        <section id="section-juegos" className="games-section">
                            <Title level={2}>Juegos del Sitio</Title>
                            {pageGames.length > 0 ? (
                                <Carousel className="games-carousel" {...gamesSliderConfig}>
                                    {pageGames.map((game, index) => (
                                        <div className="game-slide" key={`${game.title}-${index}`}>
                                            <Card className={`game-card ${game.is_featured ? 'is-featured' : ''}`} bordered={false}>
                                                {game.is_featured && <Tag color="gold">Destacado</Tag>}
                                                <span>Slot</span>
                                                <Title level={5}>{game.title}</Title>
                                                {game.description && <Text>{game.description}</Text>}
                                            </Card>
                                        </div>
                                    ))}
                                </Carousel>
                            ) : (
                                <Card bordered={false}>
                                    <Empty
                                        image={Empty.PRESENTED_IMAGE_SIMPLE}
                                        description="Sin juegos vinculados"
                                    />
                                </Card>
                            )}
                        </section>

                        <section id="section-sala" className="bottom-cta">
                            <div>
                                <Title level={3}>Unete a nuestra comunidad</Title>
                                <Text>Registrate hoy y recibe beneficios exclusivos, promociones y mucho mas.</Text>
                            </div>
                            <Button
                                size="large"
                                type="primary"
                                icon={<FontAwesomeIcon icon={faUser} />}
                                onClick={() => router.visit('/usuario')}
                            >
                                Registrate Gratis
                            </Button>
                        </section>

                        <FrontFooter site={sharedSite} id="section-soporte" />
                    </Content>

                    <Drawer
                        title={customer ? customer.name : 'Perfil'}
                        placement="right"
                        open={isProfileDrawerOpen}
                        onClose={() => setIsProfileDrawerOpen(false)}
                        className="casino-drawer"
                    >
                        <Space direction="vertical" style={{ width: '100%' }}>
                            <Button type="primary" block onClick={() => {
                                setIsProfileDrawerOpen(false);
                                router.visit('/usuario');
                            }}>
                                Ver perfil
                            </Button>
                            <Button block onClick={() => {
                                setIsProfileDrawerOpen(false);
                                router.visit('/usuario#mis-cupones');
                            }}>
                                Ver mis cupones
                            </Button>
                            <Button danger block onClick={handleCustomerLogout}>
                                Cerrar sesion
                            </Button>
                        </Space>
                    </Drawer>

                    <Drawer
                        title="Menu"
                        placement="right"
                        open={isMenuOpen}
                        onClose={() => setIsMenuOpen(false)}
                        className="casino-drawer"
                    >
                        <Anchor
                            affix={false}
                            items={anchorItems}
                            targetOffset={104}
                            onClick={() => {
                                setIsMenuOpen(false);
                            }}
                        />
                    </Drawer>
                </Layout>
            </ConfigProvider>
        </>
    );
}
