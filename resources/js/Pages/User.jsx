import { Head, router, useForm, usePage } from '@inertiajs/react';
import UserBenefitsCard from '../Components/Front/UserBenefitsCard';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import UserSessionCard from '../Components/Front/UserSessionCard';
import UserWelcomeCard from '../Components/Front/UserWelcomeCard';
import { useMemo, useState } from 'react';

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
    const security = page.props.auth?.security ?? {};
    const otpLogin = page.props.auth?.otpLogin ?? { pending: false, identifier: null, email: null };
    const loginRequiresOtp = Boolean(security.loginRequiresOtp ?? true);
    const profileUnlock = security.profileUnlock ?? {
        unlocked: false,
        otpEnabled: true,
        magicLinkEnabled: true,
        hideBirthDate: true,
    };
    const [isChangingUser, setIsChangingUser] = useState(false);
    const [isMenuOpen, setIsMenuOpen] = useState(false);
    const [isRegistering, setIsRegistering] = useState(false);
    const isOtpPending = loginRequiresOtp && Boolean(otpLogin.pending) && !isChangingUser;
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
                    title: loginRequiresOtp ? 'Codigo enviado' : 'Acceso concedido',
                    description: loginRequiresOtp
                        ? 'Revisa tu correo y escribe el codigo de acceso para continuar.'
                        : 'Ingresaste correctamente con tu cuenta.',
                });
            },
            onError: () => {
                setFeedback({
                    variant: 'danger',
                    title: loginRequiresOtp ? 'No se pudo enviar el codigo' : 'No se pudo iniciar sesion',
                    description: loginRequiresOtp
                        ? 'Revisa tu correo e intenta nuevamente.'
                        : 'Revisa tus datos e intenta nuevamente.',
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

                    <main className="user-login-shell d-flex flex-column gap-3">
                        <span className="user-login-hour">7:49 a.m</span>

                        <section className="user-login-card d-flex flex-column gap-3" aria-label="Acceso principal">
                            <div className="user-login-brand" aria-hidden="true">
                                <h1>{site.name}</h1>
                            </div>

                            {isRegistering ? (
                                <form className="user-login-form-shell user-register-form-shell d-flex flex-column gap-3" onSubmit={handleRegisterCustomer}>
                                    <label htmlFor="register-name" className="form-label">Nombre Completo</label>
                                    <input
                                        id="register-name"
                                        className="form-control user-phone-input user-register-input"
                                        type="text"
                                        autoComplete="name"
                                        placeholder="Carlos Silva"
                                        value={registerForm.data.name}
                                        onInput={(event) => registerForm.setData('name', event.target.value)}
                                    />

                                    <label htmlFor="register-email" className="form-label">E-mail</label>
                                    <input
                                        id="register-email"
                                        className="form-control user-phone-input user-register-input"
                                        type="email"
                                        autoComplete="email"
                                        placeholder="correo@ejemplo.com"
                                        value={registerForm.data.email}
                                        onInput={(event) => registerForm.setData('email', event.target.value)}
                                    />

                                    <label htmlFor="register-email-confirmation" className="form-label">Confirma su E-mail</label>
                                    <input
                                        id="register-email-confirmation"
                                        className="form-control user-phone-input user-register-input"
                                        type="email"
                                        autoComplete="email"
                                        placeholder="correo@ejemplo.com"
                                        value={registerForm.data.email_confirmation}
                                        onInput={(event) => registerForm.setData('email_confirmation', event.target.value)}
                                    />

                                    <label htmlFor="register-phone" className="form-label">Numero de Telefono</label>
                                    <input
                                        id="register-phone"
                                        className="form-control user-phone-input user-register-input"
                                        type="text"
                                        autoComplete="tel"
                                        placeholder="+56 9 1548 2685"
                                        value={registerForm.data.phone}
                                        onInput={(event) => registerForm.setData('phone', formatPhone(event.target.value))}
                                    />

                                    <label htmlFor="register-birth-date" className="form-label">Fecha de Nacimiento</label>
                                    <input
                                        id="register-birth-date"
                                        className="form-control user-phone-input user-register-input"
                                        type="date"
                                        max={adultMaxBirthDate}
                                        value={registerForm.data.birth_date}
                                        onInput={(event) => registerForm.setData('birth_date', event.target.value)}
                                    />

                                    {feedback ? (
                                        <div className="alert alert-info feedback-callout" role="alert">
                                            <strong>{feedback.title}</strong>
                                            <p className="mb-0">{feedback.description}</p>
                                        </div>
                                    ) : null}

                                    {registerErrorMessage ? (
                                        <div className="alert alert-danger feedback-callout" role="alert">
                                            <strong>Error de registro</strong>
                                            <p className="mb-0">{registerErrorMessage}</p>
                                        </div>
                                    ) : null}

                                    <button
                                        className="user-login-primary btn btn-primary btn-lg w-100"
                                        type="submit"
                                        disabled={registerForm.processing}
                                    >
                                        Registrarme
                                    </button>

                                    <button
                                        className="user-login-secondary btn btn-outline-secondary btn-lg w-100"
                                        type="button"
                                        disabled={registerForm.processing}
                                        onClick={() => {
                                            setIsRegistering(false);
                                            setFeedback(null);
                                        }}
                                    >
                                        Volver al acceso
                                    </button>
                                </form>
                            ) : (
                                <form className="user-login-form-shell d-flex flex-column gap-3" onSubmit={isOtpPending ? handleVerifyOtp : handleRequestOtp}>
                                    <label htmlFor="customer-phone-entry" className="form-label">Numero de Telefono / Email:</label>
                                    <input
                                        id="customer-phone-entry"
                                        className="form-control user-phone-input"
                                        type="text"
                                        value={loginForm.data.identifier}
                                        autoComplete="username"
                                        placeholder="Numero de telefono o email"
                                        disabled={isOtpPending || loginForm.processing}
                                        onInput={(event) => loginForm.setData('identifier', formatPhone(event.target.value))}
                                    />

                                    {isOtpPending ? (
                                        <button
                                            className="user-login-secondary btn btn-outline-secondary btn-lg w-100"
                                            type="button"
                                            onClick={() => {
                                                setIsChangingUser(true);
                                                setFeedback(null);
                                                loginForm.reset('otp_code');
                                            }}
                                        >
                                            Cambiar usuario
                                        </button>
                                    ) : null}

                                    {isOtpPending ? (
                                        <>
                                            <label htmlFor="customer-otp-entry" className="form-label">Codigo de acceso:</label>
                                            <input
                                                id="customer-otp-entry"
                                                className="form-control user-phone-input"
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
                                        <div className="alert alert-info feedback-callout" role="alert">
                                            <strong>{feedback.title}</strong>
                                            <p className="mb-0">{feedback.description}</p>
                                        </div>
                                    ) : null}

                                    {loginErrorMessage ? (
                                        <div className="alert alert-danger feedback-callout" role="alert">
                                            <strong>Error de autenticacion</strong>
                                            <p className="mb-0">{loginErrorMessage}</p>
                                        </div>
                                    ) : null}

                                    <button
                                        className="user-login-primary btn btn-primary btn-lg w-100"
                                        type="submit"
                                        disabled={loginForm.processing}
                                    >
                                        {isOtpPending ? 'Verificar codigo' : 'Acceder'}
                                    </button>

                                    {isOtpPending ? (
                                        <button
                                            className="user-login-secondary btn btn-outline-secondary btn-lg w-100"
                                            type="button"
                                            disabled={loginForm.processing}
                                            onClick={() => handleRequestOtp()}
                                        >
                                            Reenviar codigo
                                        </button>
                                    ) : (
                                        <>
                                            <p className="user-login-copy">Aun no estas registrado?</p>

                                            <button
                                                className="user-login-secondary btn btn-primary btn-lg w-100"
                                                type="button"
                                                onClick={() => {
                                                    setIsRegistering(true);
                                                    setFeedback(null);
                                                }}
                                            >
                                                Registrarme
                                            </button>
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

                <main className="casino-content d-flex flex-column gap-3">
                    {feedback ? (
                        <div className="alert alert-info feedback-callout" role="alert">
                            <strong>{feedback.title}</strong>
                            <p className="mb-0">{feedback.description}</p>
                        </div>
                    ) : null}

                    <UserWelcomeCard site={site} adminPortal={adminPortal} />

                    <div className="user-grid d-flex flex-column gap-3">
                        <div>
                            <UserSessionCard customer={customer} profileUnlock={profileUnlock} onLogout={handleLogout} />
                        </div>
                    </div>

                    <section id="mis-cupones" className="user-coupons-section d-flex flex-column gap-3">
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
                    customer={customer}
                    onLogout={handleLogout}
                />
            </div>
        </>
    );
}
