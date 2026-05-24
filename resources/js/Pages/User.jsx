import { Head, router, useForm, usePage } from '@inertiajs/react';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
    faGift,
    faPenToSquare,
    faRightFromBracket,
    faTicket,
    faTrophy,
} from '@fortawesome/free-solid-svg-icons';
import UserBenefitsCard from '../Components/Front/UserBenefitsCard';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import UserSessionCard from '../Components/Front/UserSessionCard';
import UserWelcomeCard from '../Components/Front/UserWelcomeCard';
import { useMemo, useState } from 'react';
import { WaCallout, WaInput, WaButton } from '../Components/Front/primitives/wa';

function formatPhone(rawValue) {
    if (rawValue.includes('@') || /[a-zA-Z]/.test(rawValue)) {
        return rawValue;
    }

    const digitsOnly = rawValue.replace(/\D/g, '').slice(0, 11);

    if (digitsOnly.length <= 2) {
        return digitsOnly ? `+${digitsOnly}` : '';
    }

    const country = digitsOnly.slice(0, 2);
    const remainder = digitsOnly.slice(2);

    if (remainder.length <= 1) {
        return `+${country} ${remainder}`;
    }

    if (remainder.length <= 5) {
        return `+${country} ${remainder[0]} ${remainder.slice(1)}`;
    }

    return `+${country} ${remainder[0]} ${remainder.slice(1, 5)} ${remainder.slice(5, 9)}`;
}

export default function User({ site, activeCoupons = [] }) {
    const page = usePage();
    const customer = page.props.auth?.customer ?? null;
    const adminPortal = page.props.auth?.adminPortal ?? null;
    const otpLogin = page.props.auth?.otpLogin ?? { pending: false, identifier: null, email: null };
    const [isChangingUser, setIsChangingUser] = useState(false);
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isRegistering, setIsRegistering] = useState(false);
    const isOtpPending = Boolean(otpLogin.pending) && !isChangingUser;
    const [feedback, setFeedback] = useState(null);

    const currentTime = useMemo(() => {
        return new Intl.DateTimeFormat('es-CL', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true,
        }).format(new Date());
    }, []);

    const loginForm = useForm({
        identifier: otpLogin.identifier ?? '',
        otp_code: '',
        remember: false,
    });

    const registerForm = useForm({
        name: '',
        email: '',
        email_confirmation: '',
        phone: '',
        birth_date: '',
    });

    const adultMaxBirthDate = useMemo(() => {
        const date = new Date();
        date.setFullYear(date.getFullYear() - 18);

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
    }, []);

    const handleRequestOtp = (event) => {
        event?.preventDefault();
        setFeedback(null);
        setIsChangingUser(false);
        loginForm.post('/usuario/login', {
            preserveScroll: true,
            onSuccess: () => {
                setFeedback({
                    variant: 'success',
                    title: 'Codigo enviado',
                    description: 'Revisa tu correo y escribe el codigo de acceso para continuar.',
                });
            },
            onError: () => {
                setFeedback({
                    variant: 'danger',
                    title: 'No se pudo enviar el codigo',
                    description: 'Revisa tu correo e intenta nuevamente.',
                });
            },
            onFinish: () => {
                loginForm.reset('otp_code');
            },
        });
    };

    const handleVerifyOtp = (event) => {
        event?.preventDefault();
        setFeedback(null);
        loginForm.post('/usuario/login/verify', {
            preserveScroll: true,
            onSuccess: () => {
                setFeedback({
                    variant: 'success',
                    title: 'Sesion iniciada',
                    description: 'Has iniciado sesion correctamente.',
                });
            },
            onError: () => {
                setFeedback({
                    variant: 'danger',
                    title: 'No se pudo verificar el codigo',
                    description: 'Revisa el codigo e intenta nuevamente.',
                });
            },
            onFinish: () => {
                loginForm.reset('otp_code');
            },
        });
    };

    const handleRegisterCustomer = (event) => {
        event?.preventDefault();
        setFeedback(null);

        registerForm.post('/usuario/register', {
            preserveScroll: true,
            onSuccess: () => {
                const registeredEmail = registerForm.data.email;

                setIsRegistering(false);
                setIsChangingUser(false);
                loginForm.setData('identifier', registeredEmail);
                loginForm.setData('otp_code', '');
                registerForm.reset();

                setFeedback({
                    variant: 'success',
                    title: 'Registro exitoso',
                    description: 'Tu cuenta fue creada. Ahora solicita tu codigo para acceder.',
                });
            },
            onError: () => {
                setFeedback({
                    variant: 'danger',
                    title: 'No se pudo completar el registro',
                    description: 'Revisa los datos del formulario e intenta nuevamente.',
                });
            },
        });
    };

    const handleLogout = () => {
        setFeedback(null);
        router.post('/usuario/logout', {}, {
            preserveScroll: true,
            onSuccess: () => {
                setIsMenuOpen(false);
                setFeedback({
                    variant: 'success',
                    title: 'Sesion cerrada',
                    description: 'Tu sesion fue cerrada correctamente.',
                });
            },
        });
    };

    const loginErrorMessage = loginForm.errors.identifier ?? loginForm.errors.otp_code ?? null;
    const registerErrorMessage = registerForm.errors.name
        ?? registerForm.errors.email
        ?? registerForm.errors.email_confirmation
        ?? registerForm.errors.phone
        ?? registerForm.errors.birth_date
        ?? null;

    if (!customer) {
        return (
            <>
                <Head title={`Acceso | ${site.name}`} />

                <div className="user-login-screen">
                    <div className="user-login-glow user-login-glow--top" aria-hidden="true" />
                    <div className="user-login-glow user-login-glow--bottom" aria-hidden="true" />

                    <main className="user-login-shell wa-stack">
                        <span className="user-login-hour">7:49 a.m</span>

                        <section className="user-login-card wa-stack" aria-label="Acceso principal">
                            <div className="user-login-brand" aria-hidden="true">
                                <h1>{site.name}</h1>
                            </div>

                            {isRegistering ? (
                                <form className="user-login-form-shell user-register-form-shell wa-stack" onSubmit={handleRegisterCustomer}>
                                    <label htmlFor="register-name">Nombre Completo</label>
                                    <WaInput
                                        id="register-name"
                                        className="user-phone-input user-register-input"
                                        type="text"
                                        autoComplete="name"
                                        placeholder="Carlos Silva"
                                        value={registerForm.data.name}
                                        onInput={(event) => registerForm.setData('name', event.target.value)}
                                    />

                                    <label htmlFor="register-email">E-mail</label>
                                    <WaInput
                                        id="register-email"
                                        className="user-phone-input user-register-input"
                                        type="email"
                                        autoComplete="email"
                                        placeholder="correo@ejemplo.com"
                                        value={registerForm.data.email}
                                        onInput={(event) => registerForm.setData('email', event.target.value)}
                                    />

                                    <label htmlFor="register-email-confirmation">Confirma su E-mail</label>
                                    <WaInput
                                        id="register-email-confirmation"
                                        className="user-phone-input user-register-input"
                                        type="email"
                                        autoComplete="email"
                                        placeholder="correo@ejemplo.com"
                                        value={registerForm.data.email_confirmation}
                                        onInput={(event) => registerForm.setData('email_confirmation', event.target.value)}
                                    />

                                    <label htmlFor="register-phone">Numero de Telefono</label>
                                    <WaInput
                                        id="register-phone"
                                        className="user-phone-input user-register-input"
                                        type="text"
                                        autoComplete="tel"
                                        placeholder="+56 9 1548 2685"
                                        value={registerForm.data.phone}
                                        onInput={(event) => registerForm.setData('phone', formatPhone(event.target.value))}
                                    />

                                    <label htmlFor="register-birth-date">Fecha de Nacimiento</label>
                                    <WaInput
                                        id="register-birth-date"
                                        className="user-phone-input user-register-input"
                                        type="date"
                                        max={adultMaxBirthDate}
                                        value={registerForm.data.birth_date}
                                        onInput={(event) => registerForm.setData('birth_date', event.target.value)}
                                    />

                                    {feedback ? (
                                        <WaCallout className="feedback-callout" variant={feedback.variant}>
                                            <strong>{feedback.title}</strong>
                                            <p>{feedback.description}</p>
                                        </WaCallout>
                                    ) : null}

                                    {registerErrorMessage ? (
                                        <WaCallout className="feedback-callout" variant="danger">
                                            <strong>Error de registro</strong>
                                            <p>{registerErrorMessage}</p>
                                        </WaCallout>
                                    ) : null}

                                    <WaButton
                                        className="user-login-primary"
                                        variant="brand"
                                        size="large"
                                        type="submit"
                                        disabled={registerForm.processing}
                                    >
                                        Registrarme
                                    </WaButton>

                                    <WaButton
                                        className="user-login-secondary"
                                        variant="neutral"
                                        size="large"
                                        type="button"
                                        disabled={registerForm.processing}
                                        onClick={() => {
                                            setIsRegistering(false);
                                            setFeedback(null);
                                        }}
                                    >
                                        Volver al acceso
                                    </WaButton>
                                </form>
                            ) : (
                                <form className="user-login-form-shell wa-stack" onSubmit={isOtpPending ? handleVerifyOtp : handleRequestOtp}>
                                    <label htmlFor="customer-phone-entry">Numero de Telefono / Email:</label>
                                    <WaInput
                                        id="customer-phone-entry"
                                        className="user-phone-input"
                                        type="text"
                                        value={loginForm.data.identifier}
                                        autoComplete="username"
                                        placeholder="Numero de telefono o email"
                                        disabled={isOtpPending || loginForm.processing}
                                        onInput={(event) => loginForm.setData('identifier', formatPhone(event.target.value))}
                                    />

                                    {isOtpPending ? (
                                        <WaButton
                                            className="user-login-secondary"
                                            variant="neutral"
                                            size="large"
                                            type="button"
                                            onClick={() => {
                                                setIsChangingUser(true);
                                                setFeedback(null);
                                                loginForm.reset('otp_code');
                                            }}
                                        >
                                            Cambiar usuario
                                        </WaButton>
                                    ) : null}

                                    {isOtpPending ? (
                                        <>
                                            <label htmlFor="customer-otp-entry">Codigo de acceso:</label>
                                            <WaInput
                                                id="customer-otp-entry"
                                                className="user-phone-input"
                                                type="text"
                                                inputMode="text"
                                                autoComplete="one-time-code"
                                                placeholder="ABC123"
                                                value={loginForm.data.otp_code}
                                                onInput={(event) => loginForm.setData('otp_code', event.target.value)}
                                            />
                                        </>
                                    ) : null}

                                    {feedback ? (
                                        <WaCallout className="feedback-callout" variant={feedback.variant}>
                                            <strong>{feedback.title}</strong>
                                            <p>{feedback.description}</p>
                                        </WaCallout>
                                    ) : null}

                                    {loginErrorMessage ? (
                                        <WaCallout className="feedback-callout" variant="danger">
                                            <strong>Error de autenticacion</strong>
                                            <p>{loginErrorMessage}</p>
                                        </WaCallout>
                                    ) : null}

                                    <WaButton
                                        className="user-login-primary"
                                        variant="brand"
                                        size="large"
                                        type="submit"
                                        disabled={loginForm.processing}
                                    >
                                        {isOtpPending ? 'Verificar codigo' : 'Acceder'}
                                    </WaButton>

                                    {isOtpPending ? (
                                        <WaButton
                                            className="user-login-secondary"
                                            variant="neutral"
                                            size="large"
                                            type="button"
                                            disabled={loginForm.processing}
                                            onClick={() => handleRequestOtp()}
                                        >
                                            Reenviar codigo
                                        </WaButton>
                                    ) : (
                                        <>
                                            <p className="user-login-copy">Aun no estas registrado?</p>

                                            <WaButton
                                                className="user-login-secondary"
                                                variant="brand"
                                                size="large"
                                                type="button"
                                                onClick={() => {
                                                    setIsRegistering(true);
                                                    setFeedback(null);
                                                }}
                                            >
                                                Registrarme
                                            </WaButton>
                                        </>
                                    )}
                                </form>
                            )}
                        </section>
                    </main>
                </div>
            </>
        );
    }

    return (
        <>
            <Head title={`Usuario | ${site.name}`} />

            <div className="casino-layout">
                <FrontAppHeader
                    title="Mi Cuenta"
                    currentTime={currentTime}
                    onBack={() => router.visit('/principal')}
                    onOpenMenu={() => setIsMenuOpen(true)}
                />

                <main className="casino-content wa-stack">
                    {feedback ? (
                        <WaCallout className="feedback-callout" variant={feedback.variant}>
                            <strong>{feedback.title}</strong>
                            <p>{feedback.description}</p>
                        </WaCallout>
                    ) : null}

                    <UserWelcomeCard site={site} adminPortal={adminPortal} />

                    <div className="user-grid wa-grid">
                        <div>
                            <UserSessionCard customer={customer} onLogout={handleLogout} />
                        </div>
                    </div>

                    <section id="mis-cupones" className="user-coupons-section wa-stack">
                        <UserBenefitsCard
                            activeCoupons={activeCoupons.slice(0, 2)}
                            onCouponSelect={(coupon) => {
                                if (!coupon?.id) {
                                    return;
                                }

                                router.visit(`/usuario/cupones/${coupon.id}`);
                            }}
                            actionLabel="Ver todos los cupones"
                            onAction={() => router.visit('/usuario/cupones')}
                        />
                    </section>
                </main>

                <ActionDrawer
                    className="home-profile-drawer"
                    placement="start"
                    label={customer ? customer.name : 'Menu principal'}
                    open={isMenuOpen}
                    onClose={() => setIsMenuOpen(false)}
                >
                    <div className="home-drawer-profile wa-stack">
                        <strong>{customer?.name ?? 'Invitado'}</strong>
                        <p>{customer?.email ?? 'Conecta tu cuenta para participar en sorteos.'}</p>
                    </div>

                    <nav className="home-drawer-nav wa-stack" aria-label="Menu de usuario">
                        <WaButton variant="text" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/principal');
                        }}>
                            <FontAwesomeIcon icon={faTrophy} slot="start" />
                            Principal
                        </WaButton>
                        <WaButton variant="text" onClick={() => setIsMenuOpen(false)}>
                            <FontAwesomeIcon icon={faPenToSquare} slot="start" />
                            Editar perfil
                        </WaButton>
                        <WaButton variant="text" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/usuario/cupones');
                        }}>
                            <FontAwesomeIcon icon={faTicket} slot="start" />
                            Mis cupones
                        </WaButton>
                        <WaButton variant="text" onClick={() => {
                            setIsMenuOpen(false);
                            router.visit('/programacion');
                        }}>
                            <FontAwesomeIcon icon={faGift} slot="start" />
                            Sorteos
                        </WaButton>
                        <WaButton variant="text" onClick={handleLogout}>
                            <FontAwesomeIcon icon={faRightFromBracket} slot="start" />
                            Cerrar sesion
                        </WaButton>
                    </nav>
                </ActionDrawer>
            </div>
        </>
    );
}
