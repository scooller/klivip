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

export default function UserSessionCard({ customer, onLogout }) {
    const [avatarPreview, setAvatarPreview] = useState(null);
    const form = useForm({
        name: customer?.name ?? '',
        email: customer?.email ?? '',
        email_confirmation: customer?.email ?? '',
        phone: customer?.phone ?? '',
        birth_date: customer?.birth_date ?? '',
        avatar: null,
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

    return (
        <BaseCard
            title={(
                <span>
                    <WaBadge variant="success" pill>Editar perfil</WaBadge>
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

                <label htmlFor="profile-birth-date">Fecha de Nacimiento</label>
                <WaInput
                    id="profile-birth-date"
                    className="profile-input"
                    type="date"
                    max={maxBirthDate}
                    value={form.data.birth_date}
                    onInput={(event) => handleFieldChange('birth_date', event.target.value)}
                />

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
