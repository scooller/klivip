import { Head, router, useForm, usePage } from '@inertiajs/react';
import {
    Button,
    Col,
    ConfigProvider,
    Form,
    Layout,
    notification,
    Row,
    Space,
    Typography,
} from 'antd';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft } from '@fortawesome/free-solid-svg-icons';
import UserBenefitsCard from '../Components/Front/UserBenefitsCard';
import FrontHeader from '../Components/Front/FrontHeader';
import UserLoginCard from '../Components/Front/UserLoginCard';
import UserSessionCard from '../Components/Front/UserSessionCard';
import UserWelcomeCard from '../Components/Front/UserWelcomeCard';
import { useEffect } from 'react';

const { Content } = Layout;
const { Title } = Typography;

export default function User({ site, benefits = [] }) {
    const page = usePage();
    const customer = page.props.auth?.customer ?? null;
    const adminPortal = page.props.auth?.adminPortal ?? null;
    const [form] = Form.useForm();
    const [notificationApi, notificationContext] = notification.useNotification();

    const loginForm = useForm({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        const serverErrors = Object.entries(loginForm.errors).map(([fieldName, message]) => ({
            name: fieldName,
            errors: [message],
        }));

        form.setFields(serverErrors);
    }, [form, loginForm.errors]);

    const handleLogin = (values) => {
        loginForm.transform(() => ({
            email: values.email,
            password: values.password,
            remember: Boolean(values.remember),
        }));

        loginForm.post('/usuario/login', {
            preserveScroll: true,
            onSuccess: () => {
                notificationApi.success({
                    message: 'Sesion iniciada',
                    description: 'Has iniciado sesion correctamente.',
                });
            },
            onError: () => {
                notificationApi.error({
                    message: 'No se pudo iniciar sesion',
                    description: 'Revisa tu correo y contrasena e intenta nuevamente.',
                });
            },
            onFinish: () => {
                loginForm.reset('password');
                form.setFieldValue('password', '');
            },
        });
    };

    const handleLogout = () => {
        router.post('/usuario/logout', {}, {
            preserveScroll: true,
            onSuccess: () => {
                notificationApi.success({
                    message: 'Sesion cerrada',
                    description: 'Tu sesion fue cerrada correctamente.',
                });
            },
        });
    };

    const loginErrorMessage = loginForm.errors.email ?? loginForm.errors.password ?? null;

    return (
        <>
            <Head title={`Usuario | ${site.name}`} />

            <ConfigProvider
                theme={{
                    token: {
                        colorPrimary: '#e0b33b',
                        borderRadius: 12,
                        fontFamily: 'Rajdhani, sans-serif',
                    },
                }}
            >
                {notificationContext}
                <Layout className="casino-layout">
                    <FrontHeader
                        site={site}
                        showBrand={false}
                        leftContent={(
                            <Space>
                                <Button
                                    icon={<FontAwesomeIcon icon={faArrowLeft} />}
                                    onClick={() => router.visit('/')}
                                >
                                    Volver
                                </Button>
                                <Title level={4} style={{ margin: 0, color: '#fff' }}>
                                    Cuenta de Usuario
                                </Title>
                            </Space>
                        )}
                    />

                    <Content className="casino-content">
                        <UserWelcomeCard site={site} adminPortal={adminPortal} />

                        <Row gutter={[16, 16]}>
                            <Col xs={24} lg={14}>
                                {customer ? (
                                    <UserSessionCard customer={customer} onLogout={handleLogout} />
                                ) : (
                                    <UserLoginCard
                                        form={form}
                                        loginForm={loginForm}
                                        onLogin={handleLogin}
                                        errorMessage={loginErrorMessage}
                                    />
                                )}
                            </Col>

                            <Col xs={24} lg={10}>
                                <div id="mis-cupones">
                                    <UserBenefitsCard benefits={benefits} />
                                </div>
                            </Col>
                        </Row>
                    </Content>
                </Layout>
            </ConfigProvider>
        </>
    );
}
