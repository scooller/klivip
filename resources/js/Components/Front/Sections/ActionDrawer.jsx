import { WaDrawer } from '../primitives/wa';

export default function ActionDrawer({
    label,
    open,
    onClose,
    children,
    placement = 'end',
    className = '',
}) {
    return (
        <WaDrawer
            className={`casino-drawer ${className}`.trim()}
            placement={placement}
            label={label}
            open={open}
            onWaHide={onClose}
        >
            <div className="casino-drawer-content">{children}</div>
        </WaDrawer>
    );
}
