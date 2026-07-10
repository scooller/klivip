import { Head, router, useForm, usePage } from '@inertiajs/react';
import FrontAppHeader from '../Components/Front/FrontAppHeader';
import ActionDrawer from '../Components/Front/Sections/ActionDrawer';
import UserSessionCard from '../Components/Front/UserSessionCard';
import UserWelcomeCard from '../Components/Front/UserWelcomeCard';
import FrontFooter from '../Components/Front/FrontFooter';
import CouponMini from '../Components/Front/CouponMini';
import CouponDetailModal from '../Components/Front/CouponDetailModal';
import OtpVerificationModal from '../Components/Front/OtpVerificationModal';

import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faArrowLeft, faBarcode, faPersonCircleCheck, faUserPen, faUsersSlash } from '@fortawesome/free-solid-svg-icons';
import { useEffect, useState } from 'react';

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
    const [selectedCoupon, setSelectedCoupon] = useState(null);
    const page = usePage();
    const sharedSite = page.props.site ?? site;
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
    const [confirmValue, setConfirmValue] = useState('');
    const [confirmError, setConfirmError] = useState(null);

    const [isRegistrationOtpPending, setIsRegistrationOtpPending] = useState(false);
    const registerVerifyForm = useForm({
        otp_code: '',
    });

    const [adultMaxBirthDate, setAdultMaxBirthDate] = useState('');

    useEffect(() => {
        const date = new Date();
        date.setFullYear(date.getFullYear() - 18);

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        setAdultMaxBirthDate(`${year}-${month}-${day}`);
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
                        ? 'Revisa tus mensajes SMS o tu correo y escribe el codigo de acceso para continuar.'
                        : 'Ingresaste correctamente con tu cuenta.',
                });
            },
            onError: () => {
                setFeedback({
                    variant: 'danger',
                    title: loginRequiresOtp ? 'No se pudo enviar el codigo' : 'No se pudo iniciar sesion',
                    description: loginRequiresOtp
                        ? 'Verifica tus datos e intenta nuevamente.'
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
        setConfirmError(null);

        const isPhonePriority = Boolean(registerForm.data.phone);
        if (isPhonePriority) {
            const formattedConfirm = formatPhone(confirmValue);
            if (formattedConfirm !== registerForm.data.phone) {
                setConfirmError('El número de teléfono y la confirmación no coinciden.');
                return;
            }
        } else if (registerForm.data.email) {
            if (confirmValue !== registerForm.data.email) {
                setConfirmError('El correo y la confirmación no coinciden.');
                return;
            }
        }

        registerForm.transform((data) => ({
            ...data,
            email_confirmation: isPhonePriority ? '' : confirmValue
        }));

        registerForm.post('/usuario/register', {
            preserveScroll: true,
            onSuccess: () => {
                const isSms = Boolean(registerForm.data.phone);
                setIsRegistrationOtpPending(true);
                setFeedback({
                    variant: 'success',
                    title: 'Codigo enviado',
                    description: isSms
                        ? 'Revisa tus mensajes SMS y escribe el codigo para verificar tu cuenta.'
                        : 'Revisa tu correo y escribe el codigo para verificar tu cuenta.',
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

    const handleVerifyRegistration = (event) => {
        event?.preventDefault();
        setFeedback(null);

        registerVerifyForm.post('/usuario/register/verify', {
            preserveScroll: true,
            onSuccess: () => {
                setIsRegistering(false);
                setIsRegistrationOtpPending(false);
                setIsChangingUser(false);
                registerForm.reset();
                registerVerifyForm.reset();

                setFeedback({
                    variant: 'success',
                    title: 'Registro exitoso',
                    description: 'Tu cuenta ha sido verificada y has iniciado sesión.',
                });
            },
            onError: () => {
                setFeedback({
                    variant: 'danger',
                    title: 'Codigo invalido',
                    description: 'El codigo ingresado no es valido o expiro. Intenta nuevamente.',
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

                        <section className="user-login-card d-flex flex-column gap-3" aria-label="Acceso principal">
                            <div className="user-login-brand" aria-hidden="true">
                                {site?.logo ? (
                                    <img src={site?.logo} alt={`${site.name ?? userName}`} className="user-login-logo img-fluid" />
                                ) : (
                                    <span className="user-login-site-name">{site.name}</span>
                                )}
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

                                        {(registerForm.data.email || registerForm.data.phone) ? (() => {
                                            const isPhonePriority = Boolean(registerForm.data.phone);
                                            const confirmLabel = isPhonePriority ? 'Confirma tu Numero de Telefono' : 'Confirma tu E-mail';
                                            const confirmType = isPhonePriority ? 'text' : 'email';
                                            const confirmPlaceholder = isPhonePriority ? '+56 9 1548 2685' : 'correo@ejemplo.com';

                                            return (
                                                <>
                                                    <label htmlFor="register-confirmation" className="form-label">{confirmLabel}</label>
                                                    <input
                                                        id="register-confirmation"
                                                        className="form-control user-phone-input user-register-input"
                                                        type={confirmType}
                                                        autoComplete="off"
                                                        placeholder={confirmPlaceholder}
                                                        value={confirmValue}
                                                        onInput={(event) => setConfirmValue(isPhonePriority ? formatPhone(event.target.value) : event.target.value)}
                                                    />
                                                </>
                                            );
                                        })() : null}

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

                                        {confirmError ? (
                                            <div className="alert alert-danger feedback-callout" role="alert">
                                                <strong>Error de validación</strong>
                                                <p className="mb-0">{confirmError}</p>
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
                                            <FontAwesomeIcon icon={faUserPen} /> Registrarme
                                        </button>

                                        <button
                                            className="user-login-secondary btn btn-outline-light btn-lg w-100"
                                            type="button"
                                            disabled={registerForm.processing}
                                            onClick={() => {
                                                setIsRegistering(false);
                                                setFeedback(null);
                                            }}
                                        >
                                            <FontAwesomeIcon icon={faArrowLeft} /> Volver al acceso
                                        </button>
                                    </form>
                            ) : (
                                <form className="user-login-form-shell d-flex flex-column gap-3" onSubmit={handleRequestOtp}>
                                    <label htmlFor="customer-phone-entry" className="form-label">Numero de Telefono / Email:</label>
                                    <input
                                        id="customer-phone-entry"
                                        className="form-control user-phone-input"
                                        type="text"
                                        value={loginForm.data.identifier}
                                        autoComplete="username"
                                        placeholder="Numero de telefono o email"
                                        disabled={loginForm.processing}
                                        onInput={(event) => loginForm.setData('identifier', formatPhone(event.target.value))}
                                    />

                                    {feedback && !isOtpPending && !isRegistrationOtpPending ? (
                                        <div className="alert alert-info feedback-callout" role="alert">
                                            <strong>{feedback.title}</strong>
                                            <p className="mb-0">{feedback.description}</p>
                                        </div>
                                    ) : null}

                                    {loginErrorMessage && !isOtpPending ? (
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
                                        <FontAwesomeIcon icon={faPersonCircleCheck} /> {loginRequiresOtp ? 'Enviar codigo' : 'Acceder a mi cuenta'}
                                    </button>

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
                                            <FontAwesomeIcon icon={faUserPen} /> Registrarme
                                        </button>
                                    </>
                                </form>
                            )}
                        </section>
                    </main>
                </div>
                <OtpVerificationModal
                    open={isRegistrationOtpPending || isOtpPending}
                    mode={isRegistrationOtpPending ? 'register' : 'login'}
                    form={isRegistrationOtpPending ? registerVerifyForm : loginForm}
                    feedback={feedback}
                    onSubmit={isRegistrationOtpPending ? handleVerifyRegistration : handleVerifyOtp}
                    onResend={handleRequestOtp}
                    onClose={() => {
                        if (isRegistrationOtpPending) {
                            setIsRegistrationOtpPending(false);
                            setFeedback(null);
                        } else {
                            setIsChangingUser(true);
                            setFeedback(null);
                            loginForm.reset('otp_code');
                        }
                    }}
                />
            </>
        );
    }

    return (
        <>
            <Head title={`Usuario | ${site.name}`} />

            <div className="casino-layout d-flex flex-column min-vh-100">
                <FrontAppHeader
                    site={sharedSite}
                    title="Mi Cuenta"
                    onBack={() => router.visit('/')}
                    onOpenMenu={() => setIsMenuOpen(true)}
                />

                <main className="casino-content d-flex flex-column gap-3 flex-grow-1">
                    {feedback ? (
                        <div className="alert alert-info feedback-callout" role="alert">
                            <strong>{feedback.title}</strong>
                            <p className="mb-0">{feedback.description}</p>
                        </div>
                    ) : null}

                    <UserWelcomeCard site={site} adminPortal={adminPortal} />

                    <div className="user-grid d-flex flex-column gap-3">
                        <UserSessionCard customer={customer} profileUnlock={profileUnlock} onLogout={handleLogout} />
                    </div>

                    <section id="mis-cupones" className="user-coupons-section d-flex flex-column gap-3">
                        <div className="d-flex align-items-center justify-content-between">
                            <h3 className="mb-0 fw-bold">
                                <FontAwesomeIcon icon={faBarcode} className="me-2" />
                                Mis Cupones
                            </h3>
                            <button
                                className="btn btn-outline-warning btn-sm"
                                onClick={() => router.visit('/usuario/cupones')}
                            >
                                Ver todos
                            </button>
                        </div>

                        {activeCoupons.length > 0 ? (
                            <div className="d-flex flex-column gap-4">
                                {activeCoupons.map((group) => (
                                    <div key={group.sweepstake_slug} className="sweepstake-group">
                                        <div className="d-flex align-items-center justify-content-between mb-2">
                                            <h5 className="mb-0 fw-bold text-secondary">{group.sweepstake_name}</h5>
                                            {group.draw_at && <span className="badge bg-warning text-dark">Sorteo: {group.draw_at}</span>}
                                        </div>
                                        <div className="row row-cols-2 row-cols-md-3 g-2">
                                            {group.coupons.map((coupon) => (
                                                <div key={coupon.id} className="col">
                                                    <CouponMini
                                                        number={coupon.number}
                                                        isUsed={coupon.is_used}
                                                        sweepstakeName={group.sweepstake_name}
                                                        date={group.draw_at ? `Sorteo: ${group.draw_at}` : null}
                                                        onClick={() => setSelectedCoupon({
                                                            ...coupon,
                                                            sweepstake_name: group.sweepstake_name,
                                                            prize: group.prize,
                                                            draw_at: group.draw_at,
                                                        })}
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <div className="alert alert-info text-center m-0" role="alert">
                                <p className="mb-0">No tienes cupones activos todavía.</p>
                            </div>
                        )}
                    </section>
                </main>

                <FrontFooter site={sharedSite} />

                <ActionDrawer
                    site={sharedSite}
                    className="home-profile-drawer"
                    placement="start"
                    label={customer ? customer.name : 'Menu principal'}
                    open={isMenuOpen}
                    onClose={() => setIsMenuOpen(false)}
                    customer={customer}
                    onLogout={handleLogout}
                />

                <CouponDetailModal
                    coupon={selectedCoupon}
                    open={selectedCoupon !== null}
                    onClose={() => setSelectedCoupon(null)}
                />
            </div>
        </>
    );
}
