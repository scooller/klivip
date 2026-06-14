export default function ActionDrawer({
    label,
    open,
    onClose,
    children,
    placement = 'end',
    className = '',
}) {
    const offcanvasClass = placement === 'start' ? 'offcanvas-start' : 'offcanvas-end';

    return (
        <>
            {open && <div className="offcanvas-backdrop fade show"></div>}
            <div
                className={`offcanvas ${offcanvasClass} ${open ? 'show' : ''} casino-drawer ${className}`.trim()}
                style={{ visibility: open ? 'visible' : 'hidden' }}
            >
                <div className="offcanvas-header">
                    <h5 className="offcanvas-title">{label}</h5>
                    <button
                        type="button"
                        className="btn-close"
                        onClick={onClose}
                        aria-label="Cerrar"
                    ></button>
                </div>
                <div className="offcanvas-body casino-drawer-content">{children}</div>
            </div>
        </>
    );
}
