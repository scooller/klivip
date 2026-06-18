import { n as ActionDrawer, t as FrontAppHeader } from "./FrontAppHeader-DRdhF7Zp.js";
import { t as UserBenefitsCard } from "./UserBenefitsCard-DOvtvw-h.js";
import { useEffect, useRef, useState } from "react";
import { Head, router, useForm, usePage } from "@inertiajs/react";
import { Fragment, jsx, jsxs } from "react/jsx-runtime";
//#region resources/js/Components/Front/primitives/BaseCard.jsx
function BaseCard({ title = null, className = "", children }) {
	return /* @__PURE__ */ jsxs("div", {
		className: [
			"casino-card",
			"card",
			className
		].filter(Boolean).join(" "),
		children: [title ? /* @__PURE__ */ jsx("h3", {
			className: "casino-card-title card-header",
			children: title
		}) : null, /* @__PURE__ */ jsx("div", {
			className: "casino-card-body card-body",
			children
		})]
	});
}
//#endregion
//#region resources/js/Components/Front/UserSessionCard.jsx
function formatPhone$1(rawValue) {
	if (rawValue.includes("@") || /[a-zA-Z]/.test(rawValue)) return rawValue;
	const digitsOnly = rawValue.replace(/\D/g, "").slice(0, 11);
	if (digitsOnly.length <= 2) return digitsOnly ? `+${digitsOnly}` : "";
	const country = digitsOnly.slice(0, 2);
	const remainder = digitsOnly.slice(2);
	if (remainder.length <= 1) return `+${country} ${remainder}`;
	if (remainder.length <= 5) return `+${country} ${remainder[0]} ${remainder.slice(1)}`;
	return `+${country} ${remainder[0]} ${remainder.slice(1, 5)} ${remainder.slice(5, 9)}`;
}
function UserSessionCard({ customer, profileUnlock, onLogout }) {
	const isUnlocked = Boolean(profileUnlock?.unlocked);
	const otpEnabled = Boolean(profileUnlock?.otpEnabled);
	const magicLinkEnabled = Boolean(profileUnlock?.magicLinkEnabled);
	const hideBirthDate = Boolean(profileUnlock?.hideBirthDate);
	const [avatarPreview, setAvatarPreview] = useState(null);
	const [unlockFeedback, setUnlockFeedback] = useState(null);
	const form = useForm({
		name: customer?.name ?? "",
		email: customer?.email ?? "",
		email_confirmation: customer?.email ?? "",
		phone: customer?.phone ?? "",
		birth_date: customer?.birth_date ?? "",
		avatar: null
	});
	const unlockForm = useForm({ otp_code: "" });
	const fileInputRef = useRef(null);
	const [maxBirthDate, setMaxBirthDate] = useState("");
	useEffect(() => {
		const date = /* @__PURE__ */ new Date();
		date.setFullYear(date.getFullYear() - 18);
		setMaxBirthDate(`${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`);
	}, []);
	const avatarInitial = form.data.name.trim().charAt(0).toUpperCase() || "U";
	const formErrorMessage = form.errors.name ?? form.errors.email ?? form.errors.email_confirmation ?? form.errors.phone ?? form.errors.birth_date ?? form.errors.avatar ?? form.errors.profile ?? null;
	const handleAvatarPick = () => {
		fileInputRef.current?.click();
	};
	const handleAvatarSelected = (event) => {
		const file = event.target.files?.[0];
		if (!file) return;
		setAvatarPreview(URL.createObjectURL(file));
		form.setData("avatar", file);
	};
	const handleFieldChange = (field, value) => {
		form.setData(field, value);
	};
	const handleSubmit = (event) => {
		event.preventDefault();
		form.post("/usuario/perfil", {
			preserveScroll: true,
			forceFormData: true
		});
	};
	const handleRequestUnlockOtp = () => {
		setUnlockFeedback(null);
		unlockForm.post("/usuario/perfil/unlock/otp/request", {
			preserveScroll: true,
			onSuccess: () => {
				setUnlockFeedback({
					variant: "success",
					title: "Codigo enviado",
					description: "Revisa tu correo e ingresa el codigo para desbloquear la edicion."
				});
			}
		});
	};
	const handleVerifyUnlockOtp = (event) => {
		event.preventDefault();
		setUnlockFeedback(null);
		unlockForm.post("/usuario/perfil/unlock/otp/verify", {
			preserveScroll: true,
			onSuccess: () => {
				unlockForm.reset("otp_code");
				setUnlockFeedback({
					variant: "success",
					title: "Perfil desbloqueado",
					description: "Ya puedes editar tus datos."
				});
			}
		});
	};
	const handleRequestUnlockLink = () => {
		setUnlockFeedback(null);
		unlockForm.post("/usuario/perfil/unlock/link/request", {
			preserveScroll: true,
			onSuccess: () => {
				setUnlockFeedback({
					variant: "success",
					title: "Enlace enviado",
					description: "Revisa tu correo y usa el enlace de un solo uso para desbloquear."
				});
			}
		});
	};
	const unlockErrorMessage = unlockForm.errors.profile_unlock ?? unlockForm.errors.profile_unlock_otp ?? null;
	if (!isUnlocked) return /* @__PURE__ */ jsx(BaseCard, {
		title: /* @__PURE__ */ jsx("span", { children: /* @__PURE__ */ jsx("span", {
			className: "badge rounded-pill bg-secondary",
			children: "Perfil protegido"
		}) }),
		children: /* @__PURE__ */ jsxs("div", {
			className: "profile-editor d-flex flex-column gap-3",
			children: [
				/* @__PURE__ */ jsx("div", {
					className: "profile-editor-avatar d-flex flex-column gap-3",
					children: /* @__PURE__ */ jsx("div", {
						className: "profile-editor-avatar-image",
						children: customer?.avatar_url ? /* @__PURE__ */ jsx("img", {
							src: customer.avatar_url,
							alt: customer?.name || "Avatar",
							className: "rounded-circle",
							style: {
								width: "100px",
								height: "100px",
								objectFit: "cover"
							}
						}) : /* @__PURE__ */ jsx("div", {
							className: "rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white",
							style: {
								width: "100px",
								height: "100px",
								fontSize: "48px"
							},
							children: avatarInitial
						})
					})
				}),
				/* @__PURE__ */ jsx("label", { children: "Nombre Completo" }),
				/* @__PURE__ */ jsx("input", {
					className: "profile-input form-control",
					type: "text",
					value: customer?.name ?? "",
					disabled: true
				}),
				/* @__PURE__ */ jsx("label", { children: "E-mail" }),
				/* @__PURE__ */ jsx("input", {
					className: "profile-input form-control",
					type: "text",
					value: customer?.email ?? "",
					disabled: true
				}),
				/* @__PURE__ */ jsx("label", { children: "Numero de Telefono" }),
				/* @__PURE__ */ jsx("input", {
					className: "profile-input form-control",
					type: "text",
					value: customer?.phone ?? "",
					disabled: true
				}),
				!hideBirthDate && customer?.birth_date ? /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx("label", { children: "Fecha de Nacimiento" }), /* @__PURE__ */ jsx("input", {
					className: "profile-input form-control",
					type: "text",
					value: customer.birth_date,
					disabled: true
				})] }) : null,
				unlockFeedback ? /* @__PURE__ */ jsxs("div", {
					className: `feedback-callout alert alert-${unlockFeedback.variant === "success" ? "success" : unlockFeedback.variant}`,
					role: "alert",
					children: [/* @__PURE__ */ jsx("strong", { children: unlockFeedback.title }), /* @__PURE__ */ jsx("p", {
						className: "mb-0",
						children: unlockFeedback.description
					})]
				}) : null,
				unlockErrorMessage ? /* @__PURE__ */ jsxs("div", {
					className: "feedback-callout alert alert-danger",
					role: "alert",
					children: [/* @__PURE__ */ jsx("strong", { children: "No se pudo desbloquear" }), /* @__PURE__ */ jsx("p", {
						className: "mb-0",
						children: unlockErrorMessage
					})]
				}) : null,
				otpEnabled ? /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx("button", {
					className: "block-action btn btn-primary",
					type: "button",
					onClick: handleRequestUnlockOtp,
					children: "Solicitar codigo"
				}), /* @__PURE__ */ jsxs("form", {
					className: "d-flex flex-column gap-3",
					onSubmit: handleVerifyUnlockOtp,
					children: [
						/* @__PURE__ */ jsx("label", {
							htmlFor: "profile-unlock-otp",
							children: "Codigo de desbloqueo"
						}),
						/* @__PURE__ */ jsx("input", {
							id: "profile-unlock-otp",
							className: "profile-input form-control",
							type: "text",
							inputMode: "text",
							autoComplete: "one-time-code",
							value: unlockForm.data.otp_code,
							onInput: (event) => unlockForm.setData("otp_code", event.target.value)
						}),
						/* @__PURE__ */ jsx("button", {
							className: "block-action btn btn-primary",
							type: "submit",
							disabled: unlockForm.processing,
							children: "Verificar codigo"
						})
					]
				})] }) : null,
				magicLinkEnabled ? /* @__PURE__ */ jsx("button", {
					className: "block-action btn btn-outline-secondary",
					type: "button",
					onClick: handleRequestUnlockLink,
					children: "Enviar link de un solo uso"
				}) : null,
				/* @__PURE__ */ jsx("button", {
					className: "block-action btn btn-danger",
					type: "button",
					onClick: onLogout,
					children: "Cerrar sesion"
				})
			]
		})
	});
	return /* @__PURE__ */ jsx(BaseCard, {
		title: /* @__PURE__ */ jsx("span", { children: /* @__PURE__ */ jsx("span", {
			className: "badge rounded-pill bg-success",
			children: "Editar perfil (desbloqueado)"
		}) }),
		children: /* @__PURE__ */ jsxs("form", {
			className: "profile-editor d-flex flex-column gap-3",
			onSubmit: handleSubmit,
			children: [
				/* @__PURE__ */ jsxs("div", {
					className: "profile-editor-avatar d-flex flex-column gap-3",
					children: [
						/* @__PURE__ */ jsx("div", {
							className: "profile-editor-avatar-image",
							children: avatarPreview || customer?.avatar_url ? /* @__PURE__ */ jsx("img", {
								src: avatarPreview ?? customer?.avatar_url,
								alt: form.data.name || "Avatar",
								className: "rounded-circle",
								style: {
									width: "100px",
									height: "100px",
									objectFit: "cover"
								}
							}) : /* @__PURE__ */ jsx("div", {
								className: "rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white",
								style: {
									width: "100px",
									height: "100px",
									fontSize: "48px"
								},
								children: avatarInitial
							})
						}),
						/* @__PURE__ */ jsx("button", {
							type: "button",
							className: "btn btn-outline-secondary btn-sm",
							onClick: handleAvatarPick,
							children: "Agregar avatar"
						}),
						/* @__PURE__ */ jsx("input", {
							ref: fileInputRef,
							className: "profile-editor-file",
							type: "file",
							accept: "image/*",
							onChange: handleAvatarSelected
						})
					]
				}),
				/* @__PURE__ */ jsx("label", {
					htmlFor: "profile-name",
					children: "Nombre Completo"
				}),
				/* @__PURE__ */ jsx("input", {
					id: "profile-name",
					className: "profile-input form-control",
					type: "text",
					autoComplete: "name",
					value: form.data.name,
					onInput: (event) => handleFieldChange("name", event.target.value)
				}),
				/* @__PURE__ */ jsx("label", {
					htmlFor: "profile-email",
					children: "E-mail"
				}),
				/* @__PURE__ */ jsx("input", {
					id: "profile-email",
					className: "profile-input form-control",
					type: "email",
					autoComplete: "email",
					value: form.data.email,
					onInput: (event) => handleFieldChange("email", event.target.value)
				}),
				/* @__PURE__ */ jsx("label", {
					htmlFor: "profile-email-confirmation",
					children: "Confirma su E-mail"
				}),
				/* @__PURE__ */ jsx("input", {
					id: "profile-email-confirmation",
					className: "profile-input form-control",
					type: "email",
					autoComplete: "email",
					value: form.data.email_confirmation,
					onInput: (event) => handleFieldChange("email_confirmation", event.target.value)
				}),
				/* @__PURE__ */ jsx("label", {
					htmlFor: "profile-phone",
					children: "Numero de Telefono"
				}),
				/* @__PURE__ */ jsx("input", {
					id: "profile-phone",
					className: "profile-input form-control",
					type: "text",
					autoComplete: "tel",
					value: form.data.phone,
					onInput: (event) => handleFieldChange("phone", formatPhone$1(event.target.value))
				}),
				!hideBirthDate ? /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx("label", {
					htmlFor: "profile-birth-date",
					children: "Fecha de Nacimiento"
				}), /* @__PURE__ */ jsx("input", {
					id: "profile-birth-date",
					className: "profile-input form-control",
					type: "date",
					max: maxBirthDate,
					value: form.data.birth_date,
					onInput: (event) => handleFieldChange("birth_date", event.target.value)
				})] }) : null,
				form.recentlySuccessful ? /* @__PURE__ */ jsxs("div", {
					className: "feedback-callout alert alert-success",
					role: "alert",
					children: [/* @__PURE__ */ jsx("strong", { children: "Perfil actualizado" }), /* @__PURE__ */ jsx("p", {
						className: "mb-0",
						children: "Tus cambios se guardaron correctamente."
					})]
				}) : null,
				formErrorMessage ? /* @__PURE__ */ jsxs("div", {
					className: "feedback-callout alert alert-danger",
					role: "alert",
					children: [/* @__PURE__ */ jsx("strong", { children: "Error al guardar" }), /* @__PURE__ */ jsx("p", {
						className: "mb-0",
						children: formErrorMessage
					})]
				}) : null,
				/* @__PURE__ */ jsx("button", {
					className: "block-action btn btn-primary",
					type: "submit",
					disabled: form.processing,
					children: "Guardar cambios"
				}),
				/* @__PURE__ */ jsx("button", {
					className: "block-action btn btn-danger",
					type: "button",
					onClick: onLogout,
					children: "Cerrar sesion"
				})
			]
		})
	});
}
//#endregion
//#region resources/js/Components/Front/UserWelcomeCard.jsx
function UserWelcomeCard({ site, adminPortal = null }) {
	return /* @__PURE__ */ jsx(BaseCard, { children: /* @__PURE__ */ jsxs("div", {
		className: "welcome-copy d-flex flex-column gap-3",
		children: [
			/* @__PURE__ */ jsxs("h2", { children: ["Bienvenido a ", site.name] }),
			/* @__PURE__ */ jsx("p", { children: "Accede o crea tu cuenta para guardar favoritos y recibir promociones." }),
			/* @__PURE__ */ jsxs("p", {
				className: "muted-copy",
				children: [
					site.address ?? "Sitio oficial",
					" · ",
					site.opening_hours ?? "Atencion 24/7"
				]
			}),
			adminPortal?.url && /* @__PURE__ */ jsx("button", {
				className: "btn btn-outline-secondary",
				onClick: () => {
					window.location.href = adminPortal.url;
				},
				children: "Conectarse como administrador"
			})
		]
	}) });
}
//#endregion
//#region resources/js/Pages/User.jsx
function formatPhone(rawValue) {
	if (rawValue.includes("@") || /[a-zA-Z]/.test(rawValue)) return rawValue;
	const digitsOnly = rawValue.replace(/\D/g, "").slice(0, 11);
	if (digitsOnly.length <= 2) return digitsOnly ? `+${digitsOnly}` : "";
	const country = digitsOnly.slice(0, 2);
	const remainder = digitsOnly.slice(2);
	if (remainder.length <= 1) return `+${country} ${remainder}`;
	if (remainder.length <= 5) return `+${country} ${remainder[0]} ${remainder.slice(1)}`;
	return `+${country} ${remainder[0]} ${remainder.slice(1, 5)} ${remainder.slice(5, 9)}`;
}
function User({ site, activeCoupons = [] }) {
	const page = usePage();
	const customer = page.props.auth?.customer ?? null;
	const adminPortal = page.props.auth?.adminPortal ?? null;
	const security = page.props.auth?.security ?? {};
	const otpLogin = page.props.auth?.otpLogin ?? {
		pending: false,
		identifier: null,
		email: null
	};
	const loginRequiresOtp = Boolean(security.loginRequiresOtp ?? true);
	const profileUnlock = security.profileUnlock ?? {
		unlocked: false,
		otpEnabled: true,
		magicLinkEnabled: true,
		hideBirthDate: true
	};
	const [isChangingUser, setIsChangingUser] = useState(false);
	const [isMenuOpen, setIsMenuOpen] = useState(false);
	const [isRegistering, setIsRegistering] = useState(false);
	const isOtpPending = loginRequiresOtp && Boolean(otpLogin.pending) && !isChangingUser;
	const [feedback, setFeedback] = useState(null);
	const [currentTime, setCurrentTime] = useState("");
	useEffect(() => {
		const updateTime = () => {
			setCurrentTime(new Intl.DateTimeFormat("es-CL", {
				hour: "numeric",
				minute: "2-digit",
				hour12: true
			}).format(/* @__PURE__ */ new Date()));
		};
		updateTime();
		const interval = setInterval(updateTime, 60 * 1e3);
		return () => clearInterval(interval);
	}, []);
	const loginForm = useForm({
		identifier: otpLogin.identifier ?? "",
		otp_code: "",
		remember: false
	});
	const registerForm = useForm({
		name: "",
		email: "",
		email_confirmation: "",
		phone: "",
		birth_date: ""
	});
	const [adultMaxBirthDate, setAdultMaxBirthDate] = useState("");
	useEffect(() => {
		const date = /* @__PURE__ */ new Date();
		date.setFullYear(date.getFullYear() - 18);
		setAdultMaxBirthDate(`${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, "0")}-${String(date.getDate()).padStart(2, "0")}`);
	}, []);
	const handleRequestOtp = (event) => {
		event?.preventDefault();
		setFeedback(null);
		setIsChangingUser(false);
		loginForm.post("/usuario/login", {
			preserveScroll: true,
			onSuccess: () => {
				setFeedback({
					variant: "success",
					title: loginRequiresOtp ? "Codigo enviado" : "Acceso concedido",
					description: loginRequiresOtp ? "Revisa tu correo y escribe el codigo de acceso para continuar." : "Ingresaste correctamente con tu cuenta."
				});
			},
			onError: () => {
				setFeedback({
					variant: "danger",
					title: loginRequiresOtp ? "No se pudo enviar el codigo" : "No se pudo iniciar sesion",
					description: loginRequiresOtp ? "Revisa tu correo e intenta nuevamente." : "Revisa tus datos e intenta nuevamente."
				});
			},
			onFinish: () => {
				loginForm.reset("otp_code");
			}
		});
	};
	const handleVerifyOtp = (event) => {
		event?.preventDefault();
		setFeedback(null);
		loginForm.post("/usuario/login/verify", {
			preserveScroll: true,
			onSuccess: () => {
				setFeedback({
					variant: "success",
					title: "Sesion iniciada",
					description: "Has iniciado sesion correctamente."
				});
			},
			onError: () => {
				setFeedback({
					variant: "danger",
					title: "No se pudo verificar el codigo",
					description: "Revisa el codigo e intenta nuevamente."
				});
			},
			onFinish: () => {
				loginForm.reset("otp_code");
			}
		});
	};
	const handleRegisterCustomer = (event) => {
		event?.preventDefault();
		setFeedback(null);
		registerForm.post("/usuario/register", {
			preserveScroll: true,
			onSuccess: () => {
				const registeredEmail = registerForm.data.email;
				setIsRegistering(false);
				setIsChangingUser(false);
				loginForm.setData("identifier", registeredEmail);
				loginForm.setData("otp_code", "");
				registerForm.reset();
				setFeedback({
					variant: "success",
					title: "Registro exitoso",
					description: "Tu cuenta fue creada. Ahora solicita tu codigo para acceder."
				});
			},
			onError: () => {
				setFeedback({
					variant: "danger",
					title: "No se pudo completar el registro",
					description: "Revisa los datos del formulario e intenta nuevamente."
				});
			}
		});
	};
	const handleLogout = () => {
		setFeedback(null);
		router.post("/usuario/logout", {}, {
			preserveScroll: true,
			onSuccess: () => {
				setIsMenuOpen(false);
				setFeedback({
					variant: "success",
					title: "Sesion cerrada",
					description: "Tu sesion fue cerrada correctamente."
				});
			}
		});
	};
	const loginErrorMessage = loginForm.errors.identifier ?? loginForm.errors.otp_code ?? null;
	const registerErrorMessage = registerForm.errors.name ?? registerForm.errors.email ?? registerForm.errors.email_confirmation ?? registerForm.errors.phone ?? registerForm.errors.birth_date ?? null;
	if (!customer) return /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx(Head, { title: `Acceso | ${site.name}` }), /* @__PURE__ */ jsxs("div", {
		className: "user-login-screen",
		children: [
			/* @__PURE__ */ jsx("div", {
				className: "user-login-glow user-login-glow--top",
				"aria-hidden": "true"
			}),
			/* @__PURE__ */ jsx("div", {
				className: "user-login-glow user-login-glow--bottom",
				"aria-hidden": "true"
			}),
			/* @__PURE__ */ jsxs("main", {
				className: "user-login-shell d-flex flex-column gap-3",
				children: [/* @__PURE__ */ jsx("span", {
					className: "user-login-hour",
					children: currentTime
				}), /* @__PURE__ */ jsxs("section", {
					className: "user-login-card d-flex flex-column gap-3",
					"aria-label": "Acceso principal",
					children: [/* @__PURE__ */ jsx("div", {
						className: "user-login-brand",
						"aria-hidden": "true",
						children: /* @__PURE__ */ jsx("h1", { children: site.name })
					}), isRegistering ? /* @__PURE__ */ jsxs("form", {
						className: "user-login-form-shell user-register-form-shell d-flex flex-column gap-3",
						onSubmit: handleRegisterCustomer,
						children: [
							/* @__PURE__ */ jsx("label", {
								htmlFor: "register-name",
								className: "form-label",
								children: "Nombre Completo"
							}),
							/* @__PURE__ */ jsx("input", {
								id: "register-name",
								className: "form-control user-phone-input user-register-input",
								type: "text",
								autoComplete: "name",
								placeholder: "Carlos Silva",
								value: registerForm.data.name,
								onInput: (event) => registerForm.setData("name", event.target.value)
							}),
							/* @__PURE__ */ jsx("label", {
								htmlFor: "register-email",
								className: "form-label",
								children: "E-mail"
							}),
							/* @__PURE__ */ jsx("input", {
								id: "register-email",
								className: "form-control user-phone-input user-register-input",
								type: "email",
								autoComplete: "email",
								placeholder: "correo@ejemplo.com",
								value: registerForm.data.email,
								onInput: (event) => registerForm.setData("email", event.target.value)
							}),
							/* @__PURE__ */ jsx("label", {
								htmlFor: "register-email-confirmation",
								className: "form-label",
								children: "Confirma su E-mail"
							}),
							/* @__PURE__ */ jsx("input", {
								id: "register-email-confirmation",
								className: "form-control user-phone-input user-register-input",
								type: "email",
								autoComplete: "email",
								placeholder: "correo@ejemplo.com",
								value: registerForm.data.email_confirmation,
								onInput: (event) => registerForm.setData("email_confirmation", event.target.value)
							}),
							/* @__PURE__ */ jsx("label", {
								htmlFor: "register-phone",
								className: "form-label",
								children: "Numero de Telefono"
							}),
							/* @__PURE__ */ jsx("input", {
								id: "register-phone",
								className: "form-control user-phone-input user-register-input",
								type: "text",
								autoComplete: "tel",
								placeholder: "+56 9 1548 2685",
								value: registerForm.data.phone,
								onInput: (event) => registerForm.setData("phone", formatPhone(event.target.value))
							}),
							/* @__PURE__ */ jsx("label", {
								htmlFor: "register-birth-date",
								className: "form-label",
								children: "Fecha de Nacimiento"
							}),
							/* @__PURE__ */ jsx("input", {
								id: "register-birth-date",
								className: "form-control user-phone-input user-register-input",
								type: "date",
								max: adultMaxBirthDate,
								value: registerForm.data.birth_date,
								onInput: (event) => registerForm.setData("birth_date", event.target.value)
							}),
							feedback ? /* @__PURE__ */ jsxs("div", {
								className: "alert alert-info feedback-callout",
								role: "alert",
								children: [/* @__PURE__ */ jsx("strong", { children: feedback.title }), /* @__PURE__ */ jsx("p", {
									className: "mb-0",
									children: feedback.description
								})]
							}) : null,
							registerErrorMessage ? /* @__PURE__ */ jsxs("div", {
								className: "alert alert-danger feedback-callout",
								role: "alert",
								children: [/* @__PURE__ */ jsx("strong", { children: "Error de registro" }), /* @__PURE__ */ jsx("p", {
									className: "mb-0",
									children: registerErrorMessage
								})]
							}) : null,
							/* @__PURE__ */ jsx("button", {
								className: "user-login-primary btn btn-primary btn-lg w-100",
								type: "submit",
								disabled: registerForm.processing,
								children: "Registrarme"
							}),
							/* @__PURE__ */ jsx("button", {
								className: "user-login-secondary btn btn-outline-secondary btn-lg w-100",
								type: "button",
								disabled: registerForm.processing,
								onClick: () => {
									setIsRegistering(false);
									setFeedback(null);
								},
								children: "Volver al acceso"
							})
						]
					}) : /* @__PURE__ */ jsxs("form", {
						className: "user-login-form-shell d-flex flex-column gap-3",
						onSubmit: isOtpPending ? handleVerifyOtp : handleRequestOtp,
						children: [
							/* @__PURE__ */ jsx("label", {
								htmlFor: "customer-phone-entry",
								className: "form-label",
								children: "Numero de Telefono / Email:"
							}),
							/* @__PURE__ */ jsx("input", {
								id: "customer-phone-entry",
								className: "form-control user-phone-input",
								type: "text",
								value: loginForm.data.identifier,
								autoComplete: "username",
								placeholder: "Numero de telefono o email",
								disabled: isOtpPending || loginForm.processing,
								onInput: (event) => loginForm.setData("identifier", formatPhone(event.target.value))
							}),
							isOtpPending ? /* @__PURE__ */ jsx("button", {
								className: "user-login-secondary btn btn-outline-secondary btn-lg w-100",
								type: "button",
								onClick: () => {
									setIsChangingUser(true);
									setFeedback(null);
									loginForm.reset("otp_code");
								},
								children: "Cambiar usuario"
							}) : null,
							isOtpPending ? /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx("label", {
								htmlFor: "customer-otp-entry",
								className: "form-label",
								children: "Codigo de acceso:"
							}), /* @__PURE__ */ jsx("input", {
								id: "customer-otp-entry",
								className: "form-control user-phone-input",
								type: "text",
								inputMode: "text",
								autoComplete: "one-time-code",
								placeholder: "ABC123",
								value: loginForm.data.otp_code,
								onInput: (event) => loginForm.setData("otp_code", event.target.value)
							})] }) : null,
							feedback ? /* @__PURE__ */ jsxs("div", {
								className: "alert alert-info feedback-callout",
								role: "alert",
								children: [/* @__PURE__ */ jsx("strong", { children: feedback.title }), /* @__PURE__ */ jsx("p", {
									className: "mb-0",
									children: feedback.description
								})]
							}) : null,
							loginErrorMessage ? /* @__PURE__ */ jsxs("div", {
								className: "alert alert-danger feedback-callout",
								role: "alert",
								children: [/* @__PURE__ */ jsx("strong", { children: "Error de autenticacion" }), /* @__PURE__ */ jsx("p", {
									className: "mb-0",
									children: loginErrorMessage
								})]
							}) : null,
							/* @__PURE__ */ jsx("button", {
								className: "user-login-primary btn btn-primary btn-lg w-100",
								type: "submit",
								disabled: loginForm.processing,
								children: isOtpPending ? "Verificar codigo" : "Acceder"
							}),
							isOtpPending ? /* @__PURE__ */ jsx("button", {
								className: "user-login-secondary btn btn-outline-secondary btn-lg w-100",
								type: "button",
								disabled: loginForm.processing,
								onClick: () => handleRequestOtp(),
								children: "Reenviar codigo"
							}) : /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx("p", {
								className: "user-login-copy",
								children: "Aun no estas registrado?"
							}), /* @__PURE__ */ jsx("button", {
								className: "user-login-secondary btn btn-primary btn-lg w-100",
								type: "button",
								onClick: () => {
									setIsRegistering(true);
									setFeedback(null);
								},
								children: "Registrarme"
							})] })
						]
					})]
				})]
			})
		]
	})] });
	return /* @__PURE__ */ jsxs(Fragment, { children: [/* @__PURE__ */ jsx(Head, { title: `Usuario | ${site.name}` }), /* @__PURE__ */ jsxs("div", {
		className: "casino-layout",
		children: [
			/* @__PURE__ */ jsx(FrontAppHeader, {
				title: "Mi Cuenta",
				currentTime,
				onBack: () => router.visit("/principal"),
				onOpenMenu: () => setIsMenuOpen(true)
			}),
			/* @__PURE__ */ jsxs("main", {
				className: "casino-content d-flex flex-column gap-3",
				children: [
					feedback ? /* @__PURE__ */ jsxs("div", {
						className: "alert alert-info feedback-callout",
						role: "alert",
						children: [/* @__PURE__ */ jsx("strong", { children: feedback.title }), /* @__PURE__ */ jsx("p", {
							className: "mb-0",
							children: feedback.description
						})]
					}) : null,
					/* @__PURE__ */ jsx(UserWelcomeCard, {
						site,
						adminPortal
					}),
					/* @__PURE__ */ jsx("div", {
						className: "user-grid d-flex flex-column gap-3",
						children: /* @__PURE__ */ jsx("div", { children: /* @__PURE__ */ jsx(UserSessionCard, {
							customer,
							profileUnlock,
							onLogout: handleLogout
						}) })
					}),
					/* @__PURE__ */ jsx("section", {
						id: "mis-cupones",
						className: "user-coupons-section d-flex flex-column gap-3",
						children: /* @__PURE__ */ jsx(UserBenefitsCard, {
							activeCoupons: activeCoupons.slice(0, 2),
							onCouponSelect: (coupon) => {
								if (!coupon?.id) return;
								router.visit(`/usuario/cupones/${coupon.id}`);
							},
							actionLabel: "Ver todos los cupones",
							onAction: () => router.visit("/usuario/cupones")
						})
					})
				]
			}),
			/* @__PURE__ */ jsx(ActionDrawer, {
				className: "home-profile-drawer",
				placement: "start",
				label: customer ? customer.name : "Menu principal",
				open: isMenuOpen,
				onClose: () => setIsMenuOpen(false),
				customer,
				onLogout: handleLogout
			})
		]
	})] });
}
//#endregion
export { User as default };

//# sourceMappingURL=User-C0YmJg-D.js.map