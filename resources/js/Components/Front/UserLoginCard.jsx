import { Alert, Button, Card, Checkbox, Form, Input } from 'antd';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faUser } from '@fortawesome/free-solid-svg-icons';

export default function UserLoginCard({ form, loginForm, onLogin, errorMessage = null }) {
    return (
        <Card title="Iniciar sesion de cliente" bordered={false}>
            <Alert
                type="info"
                showIcon
                message="Acceso de cliente"
                description="Ingresa con tu cuenta de cliente para acceder a tu perfil, beneficios y funcionalidades personalizadas de la plataforma."
                style={{ marginBottom: 16 }}
            />

            {errorMessage && (
                <Alert
                    type="error"
                    showIcon
                    message="Error de autenticacion"
                    description={errorMessage}
                    style={{ marginBottom: 16 }}
                />
            )}

            <Form
                form={form}
                layout="vertical"
                onFinish={onLogin}
                initialValues={loginForm.data}
            >
                <Form.Item
                    name="email"
                    label="Correo"
                    rules={[
                        { required: true, message: 'Ingresa tu correo.' },
                        { type: 'email', message: 'Ingresa un correo valido.' },
                    ]}
                >
                    <Input
                        type="email"
                        placeholder="tu@email.com"
                        prefix={<FontAwesomeIcon icon={faUser} />}
                        disabled={loginForm.processing}
                    />
                </Form.Item>

                <Form.Item
                    name="password"
                    label="Contrasena"
                    rules={[{ required: true, message: 'Ingresa tu contrasena.' }]}
                >
                    <Input.Password
                        placeholder="Tu contrasena"
                        disabled={loginForm.processing}
                    />
                </Form.Item>

                <Form.Item name="remember" valuePropName="checked">
                    <Checkbox disabled={loginForm.processing}>
                        Recordarme
                    </Checkbox>
                </Form.Item>

                <Form.Item>
                    <Button
                        type="primary"
                        size="large"
                        htmlType="submit"
                        block
                        loading={loginForm.processing}
                    >
                        Iniciar sesion
                    </Button>
                </Form.Item>
            </Form>
        </Card>
    );
}
