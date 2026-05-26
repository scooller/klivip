import { useForm } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';
import BaseCard from './primitives/BaseCard';
import { WaAvatar, WaBadge, WaButton, WaCallout, WaInput } from './primitives/wa';

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

export default function UserSessionCard({ customer, profileUnlock, onLogout }) {
    const isUnlocked = Boolean(profileUnlock?.unlocked);
    const otpEnabled = Boolean(profileUnlock?.otpEnabled);
    const magicLinkEnabled = Boolean(profileUnlock?.magicLinkEnabled);
    const hideBirthDate = Boolean(profileUnlock?.hideBirthDate);
    const [avatarPreview, setAvatarPreview] = useState(null);
    const [unlockFeedback, setUnlockFeedback] = useState(null);
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

    const maxBirthDate = useMemo(() => {
        const date = new Date();
        date.setFullYear(date.getFullYear() - 18);

        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return `${year}-${month}-${day}`;
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

    const handleSubmit = (event) => {
        event.preventDefault();

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
                setUnlockFeedback({
                    variant: 'success',
                    title: 'Codigo enviado',
                    description: 'Revisa tu correo e ingresa el codigo para desbloquear la edicion.',
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
                title={(
                    <span>
                        <WaBadge variant="neutral" pill>Perfil protegido</WaBadge>
                    </span>
                )}
            >
                <div className="profile-editor wa-stack">
                    <div className="profile-editor-avatar wa-stack">
                        <WaAvatar
                            className="profile-editor-avatar-image"
                            image={customer?.avatar_url ?? undefined}
                            label={customer?.name || 'Avatar'}
                            initials={avatarInitial}
                        />
                    </div>

                    <label>Nombre Completo</label>
                    <WaInput className="profile-input" type="text" value={customer?.name ?? ''} disabled />

                    <label>E-mail</label>
                    <WaInput className="profile-input" type="text" value={customer?.email ?? ''} disabled />

                    <label>Numero de Telefono</label>
                    <WaInput className="profile-input" type="text" value={customer?.phone ?? ''} disabled />

                    {!hideBirthDate && customer?.birth_date ? (
                        <>
                            <label>Fecha de Nacimiento</label>
                            <WaInput className="profile-input" type="text" value={customer.birth_date} disabled />
                        </>
                    ) : null}

                    {unlockFeedback ? (
                        <WaCallout className="feedback-callout" variant={unlockFeedback.variant}>
                            <strong>{unlockFeedback.title}</strong>
                            <p>{unlockFeedback.description}</p>
                        </WaCallout>
                    ) : null}

                    {unlockErrorMessage ? (
                        <WaCallout className="feedback-callout" variant="danger">
                            <strong>No se pudo desbloquear</strong>
                            <p>{unlockErrorMessage}</p>
                        </WaCallout>
                    ) : null}

                    {otpEnabled ? (
                        <>
                            <WaButton className="block-action" type="button" variant="brand" onClick={handleRequestUnlockOtp}>
                                Solicitar codigo
                            </WaButton>

                            <form className="wa-stack" onSubmit={handleVerifyUnlockOtp}>
                                <label htmlFor="profile-unlock-otp">Codigo de desbloqueo</label>
                                <WaInput
                                    id="profile-unlock-otp"
                                    className="profile-input"
                                    type="text"
                                    inputMode="text"
                                    autoComplete="one-time-code"
                                    value={unlockForm.data.otp_code}
                                    onInput={(event) => unlockForm.setData('otp_code', event.target.value)}
                                />
                                <WaButton className="block-action" type="submit" variant="brand" disabled={unlockForm.processing}>
                                    Verificar codigo
                                </WaButton>
                            </form>
                        </>
                    ) : null}

                    {magicLinkEnabled ? (
                        <WaButton className="block-action" type="button" variant="neutral" onClick={handleRequestUnlockLink}>
                            Enviar link de un solo uso
                        </WaButton>
                    ) : null}

                    <WaButton className="block-action" type="button" variant="danger" onClick={onLogout}>
                        Cerrar sesion
                    </WaButton>
                </div>
            </BaseCard>
        );
    }

    return (
        <BaseCard
            title={(
                <span>
                    <WaBadge variant="success" pill>Editar perfil (desbloqueado)</WaBadge>
                </span>
            )}
        >
            <form className="profile-editor wa-stack" onSubmit={handleSubmit}>
                <div className="profile-editor-avatar wa-stack">
                    <WaAvatar
                        className="profile-editor-avatar-image"
                        image={avatarPreview ?? customer?.avatar_url ?? undefined}
                        label={form.data.name || 'Avatar'}
                        initials={avatarInitial}
                    />
                    <WaButton type="button" size="small" variant="neutral" onClick={handleAvatarPick}>
                        Agregar avatar
                    </WaButton>
                    <input
                        ref={fileInputRef}
                        className="profile-editor-file"
                        type="file"
                        accept="image/*"
                        onChange={handleAvatarSelected}
                    />
                </div>

                <label htmlFor="profile-name">Nombre Completo</label>
                <WaInput
                    id="profile-name"
                    className="profile-input"
                    type="text"
                    autoComplete="name"
                    value={form.data.name}
                    onInput={(event) => handleFieldChange('name', event.target.value)}
                />

                <label htmlFor="profile-email">E-mail</label>
                <WaInput
                    id="profile-email"
                    className="profile-input"
                    type="email"
                    autoComplete="email"
                    value={form.data.email}
                    onInput={(event) => handleFieldChange('email', event.target.value)}
                />

                <label htmlFor="profile-email-confirmation">Confirma su E-mail</label>
                <WaInput
                    id="profile-email-confirmation"
                    className="profile-input"
                    type="email"
                    autoComplete="email"
                    value={form.data.email_confirmation}
                    onInput={(event) => handleFieldChange('email_confirmation', event.target.value)}
                />

                <label htmlFor="profile-phone">Numero de Telefono</label>
                <WaInput
                    id="profile-phone"
                    className="profile-input"
                    type="text"
                    autoComplete="tel"
                    value={form.data.phone}
                    onInput={(event) => handleFieldChange('phone', formatPhone(event.target.value))}
                />

                {!hideBirthDate ? (
                    <>
                        <label htmlFor="profile-birth-date">Fecha de Nacimiento</label>
                        <WaInput
                            id="profile-birth-date"
                            className="profile-input"
                            type="date"
                            max={maxBirthDate}
                            value={form.data.birth_date}
                            onInput={(event) => handleFieldChange('birth_date', event.target.value)}
                        />
                    </>
                ) : null}

                {form.recentlySuccessful ? (
                    <WaCallout className="feedback-callout" variant="success">
                        <strong>Perfil actualizado</strong>
                        <p>Tus cambios se guardaron correctamente.</p>
                    </WaCallout>
                ) : null}

                {formErrorMessage ? (
                    <WaCallout className="feedback-callout" variant="danger">
                        <strong>Error al guardar</strong>
                        <p>{formErrorMessage}</p>
                    </WaCallout>
                ) : null}

                <WaButton className="block-action" type="submit" variant="brand" disabled={form.processing}>
                    Guardar cambios
                </WaButton>

                <WaButton className="block-action" type="button" variant="danger" onClick={onLogout}>
                    Cerrar sesion
                </WaButton>
            </form>
        </BaseCard>
    );
}
