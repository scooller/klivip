import { useForm, router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import BaseCard from './primitives/BaseCard';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { faBarcode, faPersonCircleCheck, faPersonWalkingDashedLineArrowRight, faRightFromBracket, faUserSlash } from '@fortawesome/free-solid-svg-icons';
import PhoneInputDefault from 'react-phone-input-2';
import 'react-phone-input-2/lib/style.css';

const PhoneInput = PhoneInputDefault.default ?? PhoneInputDefault;

export default function UserSessionCard({ customer, profileUnlock, onLogout }) {
    const isUnlocked = Boolean(profileUnlock?.unlocked);
    const otpEnabled = Boolean(profileUnlock?.otpEnabled);
    const magicLinkEnabled = Boolean(profileUnlock?.magicLinkEnabled);
    const hideBirthDate = Boolean(profileUnlock?.hideBirthDate);
    const [avatarPreview, setAvatarPreview] = useState(null);
    const [unlockFeedback, setUnlockFeedback] = useState(null);
    const [confirmingDelete, setConfirmingDelete] = useState(false);
    const [deleteError, setDeleteError] = useState(null);
    const form = useForm({
        name: customer?.name ?? '',
        email: customer?.email ?? '',
        email_confirmation: customer?.email ?? '',
        phone: customer?.phone ?? '',
        birth_date: customer?.birth_date ?? '',
        avatar: null,
    });
    const unlockForm = useForm({
        otp_code: '',
    });
    const fileInputRef = useRef(null);

    const [maxBirthDate, setMaxBirthDate] = useState('');

    useEffect(() => {
        const date = new Date();
        date.setFullYear(date.getFullYear() - 18);

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        setMaxBirthDate(`${year}-${month}-${day}`);
    }, []);

    const avatarInitial = form.data.name.trim().charAt(0).toUpperCase() || 'U';

    const formErrorMessage = form.errors.name
        ?? form.errors.email
        ?? form.errors.email_confirmation
        ?? form.errors.phone
        ?? form.errors.birth_date
        ?? form.errors.avatar
        ?? form.errors.profile
        ?? null;

    const handleAvatarPick = () => {
        fileInputRef.current?.click();
    };

    const handleAvatarSelected = (event) => {
        const file = event.target.files?.[0];

        if (!file) {
            return;
        }

        setAvatarPreview(URL.createObjectURL(file));
        form.setData('avatar', file);
    };

    const handleFieldChange = (field, value) => {
        form.setData(field, value);
    };

    const handleRequestDelete = () => {
        setDeleteError(null);
        setConfirmingDelete(true);
    };

    const handleCancelDelete = () => {
        setConfirmingDelete(false);
        setDeleteError(null);
    };

    const handleConfirmDelete = () => {
        setDeleteError(null);

        router.delete('/usuario/perfil', {
            preserveScroll: true,
            onError: (errors) => {
                setConfirmingDelete(false);
                setDeleteError(errors.profile ?? 'No se pudo eliminar el perfil. Intenta nuevamente.');
            },
        });
    };

    const handleSubmit = (event) => {
        event.preventDefault();

        form.transform((data) => ({
            ...data,
            phone: data.phone ? `+${data.phone}` : '',
        }));

        form.post('/usuario/perfil', {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const handleRequestUnlockOtp = () => {
        setUnlockFeedback(null);
        unlockForm.post('/usuario/perfil/unlock/otp/request', {
            preserveScroll: true,
            onSuccess: () => {
                const message = customer?.phone
                    ? 'Revisa tus mensajes SMS e ingresa el codigo para desbloquear la edicion.'
                    : 'Revisa tu correo e ingresa el codigo para desbloquear la edicion.';
                setUnlockFeedback({
                    variant: 'success',
                    title: 'Codigo enviado',
                    description: message,
                });
            },
        });
    };

    const handleVerifyUnlockOtp = (event) => {
        event.preventDefault();
        setUnlockFeedback(null);
        unlockForm.post('/usuario/perfil/unlock/otp/verify', {
            preserveScroll: true,
            onSuccess: () => {
                unlockForm.reset('otp_code');
                setUnlockFeedback({
                    variant: 'success',
                    title: 'Perfil desbloqueado',
                    description: 'Ya puedes editar tus datos.',
                });
            },
        });
    };

    const handleRequestUnlockLink = () => {
        setUnlockFeedback(null);
        unlockForm.post('/usuario/perfil/unlock/link/request', {
            preserveScroll: true,
            onSuccess: () => {
                setUnlockFeedback({
                    variant: 'success',
                    title: 'Enlace enviado',
                    description: 'Revisa tu correo y usa el enlace de un solo uso para desbloquear.',
                });
            },
        });
    };

    const unlockErrorMessage = unlockForm.errors.profile_unlock
        ?? unlockForm.errors.profile_unlock_otp
        ?? null;

    if (!isUnlocked) {
        return (
            <BaseCard

            >
                <div className="profile-editor d-flex flex-column gap-3">
                    <div className="profile-editor-avatar d-flex flex-column gap-3">
                        <div className="profile-editor-avatar-image">
                            {customer?.avatar_url ? (
                                <img src={customer.avatar_url} alt={customer?.name || 'Avatar'} className="rounded-circle" style={{ width: '100px', height: '100px', objectFit: 'cover' }} />
                            ) : (
                                <div className="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style={{ width: '100px', height: '100px', fontSize: '48px' }}>
                                    {avatarInitial}
                                </div>
                            )}
                        </div>
                    </div>

                    <label>Nombre Completo</label>
                    <input className="profile-input form-control" type="text" value={customer?.name ?? ''} disabled />

                    <label>E-mail</label>
                    <input className="profile-input form-control" type="text" value={customer?.email ?? ''} disabled />

                    <label>Numero de Telefono</label>
                    <input className="profile-input form-control" type="text" value={customer?.phone ?? ''} disabled />

                    {!hideBirthDate && customer?.birth_date ? (
                        <>
                            <label>Fecha de Nacimiento</label>
                            <input className="profile-input form-control" type="text" value={customer.birth_date} disabled />
                        </>
                    ) : null}

                    {unlockFeedback ? (
                        <div className={`feedback-callout alert alert-${unlockFeedback.variant === 'success' ? 'success' : unlockFeedback.variant}`} role="alert">
                            <strong>{unlockFeedback.title}</strong>
                            <p className="mb-0">{unlockFeedback.description}</p>
                        </div>
                    ) : null}

                    {unlockErrorMessage ? (
                        <div className="feedback-callout alert alert-danger" role="alert">
                            <strong>No se pudo desbloquear</strong>
                            <p className="mb-0">{unlockErrorMessage}</p>
                        </div>
                    ) : null}

                    {otpEnabled ? (
                        <>
                            <button className="block-action btn btn-primary" type="button" onClick={handleRequestUnlockOtp}>
                                <FontAwesomeIcon icon={faBarcode} /> Solicitar codigo
                            </button>

                            <form className="d-flex flex-column gap-3" onSubmit={handleVerifyUnlockOtp}>
                                <label htmlFor="profile-unlock-otp">Codigo de desbloqueo</label>
                                <input
                                    id="profile-unlock-otp"
                                    className="profile-input form-control"
                                    type="text"
                                    inputMode="text"
                                    autoComplete="one-time-code"
                                    placeholder='* * * * *'
                                    value={unlockForm.data.otp_code}
                                    onInput={(event) => unlockForm.setData('otp_code', event.target.value)}
                                />
                                <button className="block-action btn btn-primary" type="submit" disabled={unlockForm.processing}>
                                    <FontAwesomeIcon icon={faPersonCircleCheck} /> Verificar codigo
                                </button>
                            </form>
                        </>
                    ) : null}

                    {magicLinkEnabled ? (
                        <button className="block-action btn btn-outline-secondary" type="button" onClick={handleRequestUnlockLink}>
                            <FontAwesomeIcon icon={faPersonWalkingDashedLineArrowRight} /> Enviar link de un solo uso
                        </button>
                    ) : null}

                    <button className="block-action btn btn-danger" type="button" onClick={onLogout}>
                        <FontAwesomeIcon icon={faRightFromBracket} /> Cerrar sesion
                    </button>
                </div>
            </BaseCard>
        );
    }

    return (
        <BaseCard
            title={(
                <span>
                    <span className="badge rounded-pill bg-success">Editar perfil (desbloqueado)</span>
                </span>
            )}
        >
            <form className="profile-editor d-flex flex-column gap-3" onSubmit={handleSubmit}>
                <div className="profile-editor-avatar d-flex flex-column gap-3">
                    <div className="profile-editor-avatar-image">
                        {avatarPreview || customer?.avatar_url ? (
                            <img src={avatarPreview ?? customer?.avatar_url} alt={form.data.name || 'Avatar'} className="rounded-circle" style={{ width: '100px', height: '100px', objectFit: 'cover' }} />
                        ) : (
                            <div className="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white" style={{ width: '100px', height: '100px', fontSize: '48px' }}>
                                {avatarInitial}
                            </div>
                        )}
                    </div>
                    <button type="button" className="btn btn-outline-secondary btn-sm" onClick={handleAvatarPick}>
                        Agregar avatar
                    </button>
                    <input
                        ref={fileInputRef}
                        className="profile-editor-file"
                        type="file"
                        accept="image/*"
                        onChange={handleAvatarSelected}
                    />
                </div>

                <label htmlFor="profile-name">Nombre Completo</label>
                <input
                    id="profile-name"
                    className="profile-input form-control"
                    type="text"
                    autoComplete="name"
                    value={form.data.name}
                    onInput={(event) => handleFieldChange('name', event.target.value)}
                />

                <label htmlFor="profile-email">E-mail</label>
                <input
                    id="profile-email"
                    className="profile-input form-control"
                    type="email"
                    autoComplete="email"
                    value={form.data.email}
                    onInput={(event) => handleFieldChange('email', event.target.value)}
                />

                <label htmlFor="profile-email-confirmation">Confirma su E-mail</label>
                <input
                    id="profile-email-confirmation"
                    className="profile-input form-control"
                    type="email"
                    autoComplete="email"
                    value={form.data.email_confirmation}
                    onInput={(event) => handleFieldChange('email_confirmation', event.target.value)}
                />

                <label htmlFor="profile-phone">Numero de Telefono</label>
                <PhoneInput
                    containerClass="profile-phone-container"
                    inputClass="profile-input form-control"
                    buttonClass="profile-phone-flag-btn"
                    country={'cl'}
                    preferredCountries={['cl', 'ar', 'pe', 'co', 'mx']}
                    value={form.data.phone}
                    onChange={(phone) => form.setData('phone', phone)}
                    enableSearch
                    searchPlaceholder="Buscar país"
                    inputProps={{
                        id: 'profile-phone',
                        autoComplete: 'tel',
                    }}
                />

                {!hideBirthDate ? (
                    <>
                        <label htmlFor="profile-birth-date">Fecha de Nacimiento</label>
                        <input
                            id="profile-birth-date"
                            className="profile-input form-control"
                            type="date"
                            max={maxBirthDate}
                            value={form.data.birth_date}
                            onInput={(event) => handleFieldChange('birth_date', event.target.value)}
                        />
                    </>
                ) : null}

                {form.recentlySuccessful ? (
                    <div className="feedback-callout alert alert-success" role="alert">
                        <strong>Perfil actualizado</strong>
                        <p className="mb-0">Tus cambios se guardaron correctamente.</p>
                    </div>
                ) : null}

                {formErrorMessage ? (
                    <div className="feedback-callout alert alert-danger" role="alert">
                        <strong>Error al guardar</strong>
                        <p className="mb-0">{formErrorMessage}</p>
                    </div>
                ) : null}

                <button className="block-action btn btn-primary" type="submit" disabled={form.processing}>
                    Guardar cambios
                </button>

                {deleteError ? (
                    <div className="feedback-callout alert alert-danger" role="alert">
                        <strong>No se pudo borrar el perfil</strong>
                        <p className="mb-0">{deleteError}</p>
                    </div>
                ) : null}

                {confirmingDelete ? (
                    <div className="feedback-callout alert alert-warning" role="alert">
                        <strong>Confirmar eliminacion</strong>
                        <p className="mb-0">
                            Estas a punto de borrar tu perfil permanentemente. Se eliminaran tus datos,
                            cupones y redenciones. Esta accion no se puede deshacer.
                        </p>
                        <div className="d-flex gap-2 mt-2">
                            <button className="btn btn-danger btn-sm" type="button" onClick={handleConfirmDelete}>
                                <FontAwesomeIcon icon={faUserSlash} /> Si, borrar mi perfil
                            </button>
                            <button className="btn btn-outline-secondary btn-sm" type="button" onClick={handleCancelDelete}>
                                Cancelar
                            </button>
                        </div>
                    </div>
                ) : (
                    <button className="block-action btn btn-outline-danger" type="button" onClick={handleRequestDelete}>
                        <FontAwesomeIcon icon={faUserSlash} /> Borrar perfil
                    </button>
                )}

                <button className="block-action btn btn-danger" type="button" onClick={onLogout}>
                    Cerrar sesion
                </button>
            </form>
        </BaseCard>
    );
}
